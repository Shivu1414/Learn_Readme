<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Admin\SellerController;

use App\Core\BaseController;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Webkul\Modules\Wix\WixmpBundle\Events\SellerEvent;
use Webkul\Modules\Wix\WixmpBundle\Utils\CommissionHelper;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPayout;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Webkul\Modules\Wix\WixmpBundle\Form\Commission\CommissionType;
/**
 * @Route("/seller/commission", name="wixmp_seller_commission_")
 * @Security("is_granted('ROLE_WIXMP_ADMIN')")
 */

class CommissionController extends BaseController
{
    const showLimitRange = [10, 20, 50, 100, 200, 500];

    public function __construct(TranslatorInterface $translator, EventDispatcherInterface $dispatcher)
    {
        $this->translate = $translator;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @Route("", name="manage")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function manage(Request $request, CompanyApplication $companyApplication, CommissionHelper $commissionHelper)
    {  
        $form = $this->createForm(CommissionType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $notifications = [];
            $formData = $form->getData();        
            if ($companyApplication->getCompany()->getStoreHash() == "Giverb3de9") {    
                if ($request->request->get('payment_type') == "paypal" || $formData['batch_action'] == "paypal") {
                    $notifications = $commissionHelper->performBatchAction($request, $formData, $companyApplication);
                } elseif ($request->request->get('payment_type') == "stripe" || $formData['batch_action'] == "stripe") {  
                    $notifications = $commissionHelper->performBatchActionStripe($request, $formData, $companyApplication);
                }else { 
                    $notifications[] = [
                        'type' => 'danger',
                        'message' => 'Invalid Payment Type'
                    ];
                }
            } else {
                if (isset($formData['batch_action'])) { 
                    $notifications = $commissionHelper->performBatchAction($request, $formData, $companyApplication);
                    if (!empty($notifications)) {
                        foreach ($notifications as $notification) {
                            $this->addFlash($notification['type'], $notification['message']);
                            $this->get('session')->getFlashBag()->clear();
                        }
                    }
                }    
            }

            if (!empty($notifications)) {
                foreach ($notifications as $notification) {
                    $this->addFlash($notification['type'], $notification['message']);
                }
            }
            $referer = $request->headers->get('referer'); 
            return $this->redirect($referer);
        }
        $params = $request->query->All();
        $params['company'] = $company = $companyApplication->getCompany();
        if (empty($params['limit'])) {
            $params['limit'] = 10;
        }
        if (empty($params['sort'])) {
            $params['sort'] = 'id';
        }
        if (empty($params['order_by'])) {
            $params['order_by'] = 'desc';
        }
        if (empty($params['page'])) {
            $params['page'] = 1;
        }
        
        list($seller_list, $search) = $commissionHelper->get_seller_commissions($params); //dd($seller_list);
        list($accountingIds, $search) = $commissionHelper->getSellerAccountingIds($params);
        
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'seller/commission_manage',
            'breadcrums_template' => 'common/breadcrums',
            'list_count' => $seller_list->getTotalItemCount(),
            'title' => 'manage_seller_commission',
            'seller_list' => $seller_list,
            'filter' => $params,
            'search' => $request->query->all(),
            'show_limits' => self::showLimitRange,
            'payout_ids_by_seller' => $accountingIds,
            'companyApplication' => $companyApplication,
            'form' => $form->createView(),
        ]); 
    }

    /**
     * @Route("/pay", name="pay")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function pay(Request $request, CompanyApplication $companyApplication, CommissionHelper $commissionHelper)
    { 
        // submiting manage form instead of link : no use remove in future
        $this->addFlash('danger', $this->translate->trans('wixmp_js_not_working_error'));

        $referer = $request->headers->get('referer'); 
        return $this->redirect($referer); 
    }
}