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
use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\CategoryMapping;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * DefaultController
 * 
 * @Route("/category",name="wixetsy_category_")
 * @Security("is_granted('ROLE_WIXETSY_ADMIN')")
 */
class CategoryController extends BaseController
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
     * Function for Category Mapping.
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/mapping",name="mapping")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function mapping(
        Request $request,
        CompanyApplication $companyApplication
    ) {  
        $company = $companyApplication->getCompany();
        $platformHelper = $this->HelperClass->getAppHelper("platform");
        $catalogHelper = $this->HelperClass->getAppHelper("catalog");

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
            }
         
        } else {
            $this->addFlash('danger', $this->translate->trans('etsy_not_connected'));
            return $this->redirectToRoute('wixetsy_app_setting',['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
        }

        $settingData = $platformHelper->getSettingValue([
            'company_application' => $companyApplication,
            'setting_name' => 'shop'
        ]);

        $defaultShopId = !empty($settingData) ?  $settingData->getSettingValue() : "";
    
        $etsyCategoryData = $catalogHelper->getEtsyCategories([
            "company_application" => $companyApplication,
            "shop_id" => $defaultShopId
        ]);

        $wixCategory = $catalogHelper->getWixCategories([
            "company_application" => $companyApplication,
        ]);
        
        //list($etsyCategoryData, $error) = $platformHelper->getCategoriesFromEtsy($companyApplication);
        //$wixCategory = $platformHelper->getCategoriesFromWix($companyApplication);

        $requestData = $request->request->all();

        if (isset($requestData['etsyWixCategorySave']) && !empty($requestData['etsyWixCategorySave'])) {

            $wixCategorySelected = isset($requestData['wixEtsyCategoryMapping']['wixCategory']) ? $requestData['wixEtsyCategoryMapping']['wixCategory'] : [];
            $etsyCategorySelected = isset($requestData['wixEtsyCategoryMapping']['etsyCategory']) ? $requestData['wixEtsyCategoryMapping']['etsyCategory'] : [];

            if (empty($wixCategorySelected) || empty($etsyCategorySelected)) {
                $this->addFlash('danger', $this->translate->trans('please_select_categories_first'));
                return $this->redirectToRoute('wixetsy_category_mapping',['storeHash' => $companyApplication->getCompany()->getStoreHash()]); 
            }
            
            $arrangedCategoryMapping = $catalogHelper->arrangeCategoryMapping($wixCategorySelected, $etsyCategorySelected);
            
            $categoryMapping = $catalogHelper->addCategoryMapping($companyApplication, $arrangedCategoryMapping);

            $this->addFlash($categoryMapping['type'], $this->translate->trans($categoryMapping['message']));
            return $this->redirectToRoute('wixetsy_category_manage',['storeHash' => $companyApplication->getCompany()->getStoreHash()]); 
        }

        return $this->render(
            '@wixetsy_twig/view_templates/index.html.twig',
            [
                'header' => true,
                'menu' => true,
                'breadcrums' => true,
                'template_name' => 'catalog/category_mapping',
                'title' => 'category_mapping',
                'company' => $company,
                'wixCategories' => $wixCategory,
                "etsyCategories" => $etsyCategoryData,
                'search' => $request->query->all(),
                //'form' => $form->createView(),
            ]
        );
        
    }

    /**
     * Function for Category mapping manage.
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
        $catalogHelper = $this->HelperClass->getAppHelper("catalog");
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
            }
         
        } else {
            $this->addFlash('danger', $this->translate->trans('etsy_not_connected'));
            return $this->redirectToRoute('wixetsy_app_setting',['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
        }

        $params = $request->query->all();

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
        
        list($categoryMappings, $params) = $catalogHelper->getCategoryMappings($params);

        return $this->render(
            '@wixetsy_twig/view_templates/index.html.twig',
            [
                'header' => true,
                'menu' => true,
                'breadcrums' => true,
                'template_name' => 'catalog/category_mapping_manage',
                'title' => 'category_mappings',
                //'form' => $form->createView(),
                'company' => $company,
                'categoryMappings' => $categoryMappings,
                'search' => $request->query->all(),
                'filter' => $params,
            ]
        );
    }

    /**
     * Function for Category mapping delete.
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/delete/{id}",name="delete")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * @Method("POST")
     **/
    public function delete(
        Request $request, $id, 
        CompanyApplication $companyApplication
    ) {
        
        $catalogHelper = $this->HelperClass->getAppHelper("catalog");

        $response = array(
            'code' => 200,
            'notifications' => [], 
        );

        $notifications = $catalogHelper->deleteCategoryMapping($id);

        $response['notification'][] = ['type' => $notifications['type'], 'message' => $this->translate->trans($notifications['message'])];

        return new JsonResponse($response); 
    }
}