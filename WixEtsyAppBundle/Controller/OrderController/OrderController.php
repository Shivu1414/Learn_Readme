<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Controller\OrderController;

use App\Helper\BaseHelper;
use App\Core\BaseController;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Translation\TranslatorInterface;
use App\Form\Custom\CustomFormType;
use Webkul\Modules\Wix\WixEtsyAppBundle\Utils\HelperClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Webkul\Modules\Wix\WixEtsyAppBundle\Form\Catalog\ProductsType;

/**
 * DefaultController
 * 
 * @Route("/orders",name="wixetsy_orders_")
 * @Security("is_granted('ROLE_WIXETSY_ADMIN')")
 */
class OrderController extends BaseController
{
    /**
     * Constructor.
     *
     * @param BaseHelper $helper basehelper
     */
    public function __construct(TranslatorInterface $translator,BaseHelper $helper, HelperClass $HelperClass)
    {
        $this->_helper = $helper;
        $this->translate = $translator;
        $this->HelperClass = $HelperClass;
    }

    /**
     * Function for Manage Orders.
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/manage",name="manage")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function manage(
        Request $request,
        CompanyApplication $companyApplication
    ) {
        $company = $companyApplication->getCompany();
        $params = $request->query->all();
        $platformHelper = $this->HelperClass->getAppHelper("platform");

        $connectionData  = $platformHelper->checkConnection($companyApplication);
        
        if(!empty($connectionData)) {
            $etsyShopDatas = $platformHelper->getEtsyShopDatas([
                "company_application" => $companyApplication,
                "user_id" => $connectionData->getEtsyUserId()
            ]);
            if(is_null($etsyShopDatas) || empty($etsyShopDatas)) {
                $this->addFlash('danger', $this->translate->trans('set_shop_data'));
                return $this->redirectToRoute('wixetsy_setting_shop',['storeHash' => $companyApplication->getCompany()->getStoreHash()]); 
            } else{
                $settingData = $platformHelper->getSettingValue([
                    'company_application' => $companyApplication,
                    'setting_name' => 'shipping_profile'
                ]);
                if(is_null($settingData) || empty($settingData->getSettingValue())) {
                    $this->addFlash('danger', $this->translate->trans('set_other_setting'));
                    return $this->redirectToRoute('wixetsy_setting_other',['storeHash' => $companyApplication->getCompany()->getStoreHash()]); 
                }
                $categorySettingData = $platformHelper->getSettingValue([
                    'company_application' => $companyApplication,
                    'setting_name' => 'etsy_category'
                ]); 
                if(is_null($categorySettingData) || empty($categorySettingData->getSettingValue())) {
                    $this->addFlash('danger', $this->translate->trans('set_other_setting_category'));
                    return $this->redirectToRoute('wixetsy_setting_other',['storeHash' => $companyApplication->getCompany()->getStoreHash()]); 
                }
            }
         
        } else {
            $this->addFlash('danger', $this->translate->trans('etsy_not_connected'));
            return $this->redirectToRoute('wixetsy_app_setting',['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
        }
        
        $settingData = $platformHelper->getSettingValue([
            'company_application' => $companyApplication,
            'setting_name' => 'shop'
        ]);

        $defaultShopId = !empty($settingData) ? $settingData->getSettingValue() : "";
    
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
        if (empty($params['shop_id'])) {
            $params['shop_id'] = $defaultShopId;
        }
        list($orders, $params) = $platformHelper->get_orders($params); 
        
        return $this->render(
            '@wixetsy_twig/view_templates/index.html.twig',
            [
                'header' => true,
                'menu' => true,
                'breadcrums' => true,
                'template_name' => 'order/order_manage',
                'title' => 'etsy_orders',
                // 'form' => $form->createView(),
                'company' => $company,
                'orders' => $orders,
                'search' => $request->query->all(),
                'filter' => $params,
            ]
        );
    }

    /**
     * Function for Order Sync From Etsy.
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/sync",name="sync")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function syncOrders(
        Request $request,
        CompanyApplication $companyApplication
    ) {
        $platformHelper = $this->HelperClass->getAppHelper("platform");
        $response = $platformHelper->sync_order($request, $companyApplication);

        return new JsonResponse($response);
    }

    /**
     * Function for Re-sync Orders on Wix.
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/re-import-to-wix/{orderId}",name="re_import")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function reSyncOrdersOnWix(
        Request $request, $orderId,
        CompanyApplication $companyApplication
    ) {
        $platformHelper = $this->HelperClass->getAppHelper("platform");
        $response = $platformHelper->sync_order($request, $companyApplication, ["orderIds" => [$orderId]]);

        $notifications = isset($response['notifications']) ? $response['notifications'] : [];
        
        if (!empty($notifications)) {
            foreach ($notifications as $notification) {
                $this->addFlash($notification['type'], $this->translate->trans($notification['message']));
            }
        }

        return $this->redirectToRoute('wixetsy_orders_manage',['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
    }

    /**
     * Function for Import Orders on Wix.
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/import-to-wix",name="import")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function importOrdersOnWix(
        Request $request,
        CompanyApplication $companyApplication
    ) {
        $platformHelper = $this->HelperClass->getAppHelper("platform");
        $orderIds = $request->request->get('orderIds');
        $orderIds = explode(",",$orderIds);
        
        $response = $platformHelper->sync_order($request, $companyApplication, ["orderIds" => $orderIds]);

        return new JsonResponse($response);
    }
}