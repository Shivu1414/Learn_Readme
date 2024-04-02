<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Controller\CatalogController;

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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
/**
 * DefaultController
 * 
 * @Route("/products",name="wixetsy_products_")
 * @Security("is_granted('ROLE_WIXETSY_ADMIN')")
 */
class ProductController extends BaseController
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
     * Function for Manage Products.
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
        CompanyApplication $companyApplication,
        SessionInterface $session
    ) { 
        $company = $companyApplication->getCompany();
        $catalogHelper = $this->HelperClass->getAppHelper("catalog");

        $platformHelper = $this->HelperClass->getAppHelper('platform');
    
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
            'company_application' => $companyApplication ,
            'setting_name' => "shop"
        ]);

        $defaultShopId = !empty($settingData) ? $settingData->getSettingValue() : "";
        

        $params = $request->query->all();
        
        # temp
        //$platformHelper = $this->HelperClass->getAppHelper("platform");
        //$platformHelper->updateEtsyInventory($companyApplication);

        $form = $this->createForm(ProductsType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) { 
            $formData = $form->getData(); 
            $requestData = $request->request->all();
           
            if (isset($formData['batch_action']) && $formData['batch_action'] == "import_to_etsy") {
                $notifications = $catalogHelper->performBatchAction($request, $formData, $companyApplication);
                if (!empty($notifications)) {
                    foreach ($notifications as $notification) {
                        $this->addFlash($notification['type'], $notification['message']);
                    }
                }
            } 
        }

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
        if(empty($params['shop_id'])) {
            $params['shop_id'] = $defaultShopId;
        }
        // dd($params);
        list($products, $params) = $catalogHelper->get_products($params);

        return $this->render(
            '@wixetsy_twig/view_templates/index.html.twig',
            [
                'header' => true,
                'menu' => true,
                'breadcrums' => true,
                'template_name' => 'catalog/product_manage',
                'title' => 'products',
                'form' => $form->createView(),
                'company' => $company,
                'products' => $products,
                'search' => $request->query->all(),
                'filter' => $params,
            ]
        );
    }

    /**
     * Function for Sync Products.
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/sync",name="sync")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function syncProducts(
        Request $request,
        CompanyApplication $companyApplication
    ) {
        $catalogHelper = $this->HelperClass->getAppHelper("catalog");
        $response = $catalogHelper->sync_products($request, $companyApplication);

        return new JsonResponse($response);
    }

    /**
     * Function for Import Products on Etsy.
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/import-to-etsy",name="import")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function importProductsOnEtsy(
        Request $request,
        CompanyApplication $companyApplication
    ) { 
        $platformHelper = $this->HelperClass->getAppHelper("platform");
        $productIds = $request->request->get('productIds');
        $productIds = explode(",",$productIds);
        $response = $platformHelper->importProductsOnEtsy($companyApplication, $request, ["prodIds" => $productIds]);

        return new JsonResponse($response);
    }

    /**
     * Function for Re-sync Products on Etsy.
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/re-import-to-etsy/{productId}",name="re_import")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function reSyncProductsOnEtsy(
        Request $request, $productId,
        CompanyApplication $companyApplication
    ) {
        $platformHelper = $this->HelperClass->getAppHelper("platform");
        $response = $platformHelper->importProductsOnEtsy($companyApplication, $request, ["prodIds" => [$productId]]);

        $notifications = isset($response['notifications']) ? $response['notifications'] : [];

        if (!empty($notifications)) {
            foreach ($notifications as $notification) {
                $this->addFlash($notification['type'], $notification['message']);
            }
        }

        return $this->redirectToRoute('wixetsy_products_manage',['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
    } 
    
    /**
     * Function for Get Products From Etsy. (But its only for PAM admin)
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/get-etsy-products",name="get_etsy_products")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function getProductsFromEtsy(
        Request $request,
        CompanyApplication $companyApplication
    ) {
        $platformHelper = $this->HelperClass->getAppHelper("platform");
        $platformHelper->getProductsFromEtsy($companyApplication);
    }
}