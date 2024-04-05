<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Admin\SellerController;

use App\Core\BaseController;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Webkul\Modules\Wix\WixmpBundle\Events\SellerEvent;
use Webkul\Modules\Wix\WixmpBundle\Utils\SellerHelper;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPayout;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPayoutTransactions;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Webkul\Modules\Wix\WixmpBundle\Form\Seller\AccountStatusType;
use Webkul\Modules\Wix\WixmpBundle\Utils\WixMpBaseHelper;

/**
 * @Route("/seller/accounting", name="wixmp_seller_accounting_")
 * @Security("is_granted('ROLE_WIXMP_ADMIN')")
 */
class AccountingController extends BaseController
{
    const showLimitRange = [10, 20, 50, 100, 200, 500];

    public function __construct(TranslatorInterface $translator, EventDispatcherInterface $dispatcher, WixMpBaseHelper $marketplaceBaseHelper)
    {
        $this->translate = $translator;
        $this->dispatcher = $dispatcher;
        $this->marketplaceBaseHelper = $marketplaceBaseHelper;
    }

    /**
     * @Route("", name="manage")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function manage(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $form = $this->createForm(AccountStatusType::class);
        $params = $request->query->All();
        $params['company'] = $company = $companyApplication->getCompany();
        if (!isset($params['seller_id'])) {
            $params['seller_id'] = null;
        }
        $accounting_total = $sellerHelper->get_seller_payout_calculation($company, $params['seller_id']);
        $seller_list = $sellerHelper->get_seller_list($company);
        list($payout_list, $search) = $sellerHelper->get_seller_payouts($params);

        $orderIds = [];
        foreach ($payout_list->getItems() as $payout) {
            $orderIds[] = $payout->getOrderId();
        }

        $salesHelper = $this->marketplaceBaseHelper->getAppHelper('sales');
        $orders = $salesHelper->getOrdersByIds($orderIds);

        $sellerHelper = $this->marketplaceBaseHelper->getAppHelper('seller');

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $notifications = [];
            $formData = $form->getData();
            $requestData = $request->request->all();

            if (isset($formData['batch_action'])) {
                $notifications = $sellerHelper->performBatchActionForPayoutStatus($request, $formData, $companyApplication);
                if (!empty($notifications)) {
                    foreach ($notifications as $notification) {
                        $this->addFlash($notification['type'], $notification['message']);
                    }
                }
            }

            $referer = $request->headers->get('referer');

            return $this->redirect($referer);
        }
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'seller/accounting_manage',
            'breadcrums_template' => 'common/breadcrums',
            'list_count' => $payout_list->getTotalItemCount(),
            'title' => 'accounting',
            'payout_list' => $payout_list,
            'search' => $search,
            'accounting_total' => $accounting_total,
            'seller_list' => $seller_list,
            'form' => $form->createView(),
            'orders' => $orders,
            'company' => $company,
        ]);
    }

    /**
     * @Route("/status-change", name="status_change")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function status_change(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $params = $request->query->all();
        $company = $companyApplication->getCompany();
        
        if (isset($params['entity_id']) && isset($params['status_to']) && !empty($params['entity_id']) && !empty($params['status_to'])) {
            $payout_data = $sellerHelper->get_seller_payout(['id' => $params['entity_id']]);

            if ($payout_data) {
                $_payout_data = $sellerHelper->update_seller_payout($payout_data, ['status' => $params['status_to']]);

                if ($_payout_data->getStatus() == $params['status_to']) {
                    if (!empty($_payout_data->getSeller())) {
                        $SellerEvent = new SellerEvent($companyApplication, $_payout_data->getSeller());
                        $SellerEvent->setPayout($_payout_data);
                        $this->dispatcher->dispatch(
                            $SellerEvent,
                            SellerEvent::WIX_SELLER_ACCOUNT_PAYOUT_STATUS_CHANGE
                        );
                    }
                    $this->addFlash('success', $this->translate->trans('message.common.status_changed_successfully'));
                } else {
                    $this->addFlash('danger', $this->translate->trans('message.common.unable_to_change_status'));
                }
            }
        }
        if ($request->get('current_url') != null) {
            return $this->redirect($request->get('current_url'));
        }

        return $this->redirectToRoute('wixmp_seller_accounting_manage', ['storeHash' => $company->getStoreHash()]);
    }

    /**
     * @Route("/payout/add", name="payout_add")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function payout_add(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $params = $request->request->All();
        $company = $companyApplication->getCompany();

        if (isset($params['seller_payout']['seller']) && isset($params['seller_payout']['amount']) && !empty($params['seller_payout']['seller']) && !empty($params['seller_payout']['amount'])) {
            $payout = new SellerPayout();
            $seller_data = $sellerHelper->get_seller(['id' => $params['seller_payout']['seller']]);
            if ($seller_data) {
                $data = array(
                    'payout_type' => 'P',
                    'comment' => isset($params['seller_payout']['comment']) ? $params['seller_payout']['comment'] : null,
                    'order_amount' => 0.00,
                    'payout_amount' => $params['seller_payout']['amount'],
                    'commission' => 0.00,
                    'commission_amount' => 0.00,
                    'commission_type' => 'P',
                    'status' => 'A',
                    'plan' => null,
                    'company' => $company,
                    'seller' => $seller_data,
                    'order_id' => null,
                );
                $payout = $sellerHelper->update_seller_payout($payout, $data);
                if ($payout) {
                    $SellerEvent = new SellerEvent($companyApplication, $seller_data);
                    $SellerEvent->setPayout($payout);
                    $this->dispatcher->dispatch(
                        $SellerEvent,
                        SellerEvent::WIX_SELLER_ACCOUNT_PAYOUT_CREATE
                    );

                    $this->addFlash('success', $this->translate->trans('message.common.record_created_successfully'));
                }
            }
        }

        return $this->redirectToRoute('wixmp_seller_accounting_manage', ['storeHash' => $company->getStoreHash()]);
    }

    /**
     * @Route("/transactions", name="payout_transactions")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function payout_transactions_list(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $params = $request->query->All();
        $params['company'] = $company = $companyApplication->getCompany();
        $commissionHelper = $sellerHelper->getAppHelper('commission');
        list($orders, $search) = $commissionHelper->get_payout_transactions($params);
        // dd($orders);
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'seller/payout_transactions_manage',
            'breadcrums_template' => 'common/breadcrums',
            'list_count' => $orders->getTotalItemCount(),
            'title' => 'transactions',
            'transactions' => $orders,
            'search' => $search,
            'show_limits' => self::showLimitRange,
        ]);
    }

    /**
     * @Route("/payout/sync/{id}", name="payout_sync")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function payoutSync(Request $request, CompanyApplication $companyApplication, SellerPayoutTransactions $transaction, SellerHelper $sellerHelper)
    {
        $params = $request->query->All();        
        $batch_id = $transaction->getBatchId();
        $paymentPlatform = $request->get('payment_platform');
        $commissionHelper = $sellerHelper->getAppHelper('commission');
        $company = $companyApplication->getCompany();
        if (!empty($batch_id)) {            
            switch ($paymentPlatform) {
                case 'paypal':
                    $response =  $commissionHelper->syncPayout($batch_id, $transaction, $companyApplication);
                    if ($response) {
                        $this->addFlash('success', $this->translate->trans('message.payout.sync_success'));
                    }
                    break;
                    
                case 'stripe':
                    $response =  $commissionHelper->syncStripePayout($batch_id, $transaction, $companyApplication);
                    if ($response) {
                        $this->addFlash('success', $this->translate->trans('message.payout.sync_success'));
                    }
                    break;
                
                default:
                    # code...
                    break;
            }
        }
        if ($request->get('current_url') != null) {
            return $this->redirect($request->get('current_url'));
        }

        return $this->redirectToRoute('marketplace_seller_accounting_payout_transactions',['storeHash' => $company->getStoreHash()]);
    }
}