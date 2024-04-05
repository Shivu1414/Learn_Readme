<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Seller\SellerController;

use App\Core\BaseController;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Webkul\Modules\Wix\WixmpBundle\Events\SellerEvent;
use Webkul\Modules\Wix\WixmpBundle\Utils\SellerHelper;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPayout;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @Route("/seller/accounting", name="wix_mp_seller_accounting_")
 * @Security("is_granted('ROLE_WIXMP_SELLER')")
 */
class AccountingController extends BaseController
{
    public function __construct(TranslatorInterface $translator, EventDispatcherInterface $dispatcher)
    {
        $this->translate = $translator;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @Route("", name="manage")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function manage(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $params = $request->query->All();
        $params['company'] = $company = $companyApplication->getCompany();
        $params['seller'] = $seller = $this->getUser()->getSeller();
        $accounting_total = $sellerHelper->get_seller_payout_calculation($company, $seller);

        $seller_list = [$seller->getSeller() => $seller->getId()];

        list($payout_list, $search) = $sellerHelper->get_seller_payouts($params);
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'seller/accounting_manage',
            'title' => 'accounting',
            'payout_list' => $payout_list,
            'list_count' => $payout_list->getTotalItemCount(),
            'search' => $search,
            'accounting_total' => $accounting_total,
            'seller_list' => $seller_list,
        ]);
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
                    'payout_type' => 'W',
                    'comment' => isset($params['seller_payout']['comment']) ? $params['seller_payout']['comment'] : null,
                    'order_amount' => 0.00,
                    'payout_amount' => $params['seller_payout']['amount'],
                    'commission' => 0.00,
                    'commission_amount' => 0.00,
                    'commission_type' => 'P',
                    'status' => 'P',
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
                        SellerEvent::WIX_SELLER_ACCOUNT_WITHDRAWAL_REQUEST
                    );

                    $this->addFlash('success', $this->translate->trans('message.common.record_created_successfully'));
                }
            }
        }

        return $this->redirectToRoute('wix_mp_seller_accounting_manage', ['storeHash' => $company->getStoreHash()]);
    }

    /**
     * @Route("/payout/transactions", name="payout_item_transactions")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function payoutItemTransactions(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $params = $request->query->All();
        $params['company'] = $company = $companyApplication->getCompany();
        $params['seller'] = $seller = $this->getUser()->getSeller();
        $commissionHelper = $sellerHelper->getAppHelper('commission');
        list($orders, $search) = $commissionHelper->get_payout_item_transactions($params);

        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'seller/payout_item_transactions',
            'title' => 'transactions',
            'transactions' => $orders,
            'list_count' => $orders->getTotalItemCount(),
            'search' => $search,
        ]);
    }
}