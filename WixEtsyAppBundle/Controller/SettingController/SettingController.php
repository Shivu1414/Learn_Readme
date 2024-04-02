<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Controller\SettingController;

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
use Webkul\Modules\Wix\WixEtsyAppBundle\Form\Setting\SettingType;

/**
 * SettingController
 * 
 * @Route("/setting",name="wixetsy_setting_")
 * @Security("is_granted('ROLE_WIXETSY_ADMIN')")
 */
class SettingController extends BaseController
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
     * Function for other Setting
     * 
     * @param Request
     * @param Companyapplication
     * 
     * @Route("/other",name="other")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function otherSetting(
        Request $request,
        CompanyApplication $companyApplication
    ){  
        $company = $companyApplication->getCompany();
        $platformHelper = $this->HelperClass->getAppHelper("platform");
        $catalogHelper = $this->HelperClass->getAppHelper("catalog");

        $etsyShops = [];
        $etsyShippingProfiles = [];
        $etsyCategories = [];

        $settingData = $platformHelper->getSettingValue([
            'company_application' => $companyApplication ,
            'setting_name' => "shop"
        ]);
        $connectionData  = $platformHelper->checkConnection($companyApplication);
        if(!empty($connectionData)) {
            $etsyShopDatas = $platformHelper->getEtsyShopDatas([
                "company_application" => $companyApplication,
                "user_id" => $connectionData->getEtsyUserId()
            ]);
            if(is_null($etsyShopDatas) || empty($etsyShopDatas)) {
                $this->addFlash('danger', $this->translate->trans('set_shop_data'));
                return $this->redirectToRoute('wixetsy_setting_shop',['storeHash' => $companyApplication->getCompany()->getStoreHash()]); 
            }
         
        } else {
            $this->addFlash('danger', $this->translate->trans('etsy_not_connected'));
            return $this->redirectToRoute('wixetsy_app_setting',['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
        }
        $defaultShopId = !empty($settingData) ? $settingData->getSettingValue() : "";
    
        $etsyCategories = $catalogHelper->getEtsyCategories([
            "company_application" => $companyApplication,
            "shop_id" => $defaultShopId
        ]);
        
        $etsyAuthData = $platformHelper->reRequestAccessToken($companyApplication);
        
        if (!empty($etsyAuthData)) {

            $etsyShippingProfilesDatas = $platformHelper->getEtsyShippingProfileDatas([
                "company_application" => $companyApplication,
                "shop_id" => $defaultShopId
            ]);
        
            foreach($etsyShippingProfilesDatas as $etsyShippingProfile) {
                    $etsyShippingProfiles[$etsyShippingProfile->getShippingProfileId()] = $etsyShippingProfile->getTitle(); 
            }
        } 

        $form = $this->createForm(SettingType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) { 
            
            $formData = $form->getData();

            foreach ($formData as $settingName => $value) {
                if ($settingName == 'auto_sync_prots_etsy') {
                    if ($value) {
                        $value = 1;
                    } else {
                        $value = 0;
                    }
                }
                $settingValue = $platformHelper->getSettingValue([
                    "company_application" => $companyApplication,
                    "setting_name" => $settingName
                ]);
                
                $settingValue = $platformHelper->updateSettingValue($settingValue,[
                    "companyApplication" => $companyApplication,
                    "company" => $company,
                    "settingName" => $settingName,
                    "settingValue" => $value
                ]);
            }
            $this->addFlash('success', $this->translate->trans('settings_updated_successfully'));
        }

        $settingValues = $platformHelper->getSettingValues([
            "company_application" => $companyApplication
        ]);

        $settingValueData = [];

        foreach($settingValues as $settingData) {
            $settingValueData[$settingData->getSettingName()] = $settingData->getSettingValue();
        } 

        return $this->render(
            '@wixetsy_twig/view_templates/index.html.twig',
            [
                'header' => true,
                'menu' => true,
                'breadcrums' => true,
                'template_name' => 'settings/other',
                'title' => 'other_setting',
                'form' => $form->createView(),
                'company' => $company,
                'etsyShippingProfiles' => $etsyShippingProfiles,
                "selectedData" => $settingValueData,
                "etsyCategories" => $etsyCategories
            ]
        );       
    }
    /**
     * Function for shop Setting action
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/shop",name="shop")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function shopSetting(
        Request $request,
        CompanyApplication $companyApplication
    ) { 
        
        $company = $companyApplication->getCompany();
        $platformHelper = $this->HelperClass->getAppHelper("platform");
        $catalogHelper = $this->HelperClass->getAppHelper("catalog");

        $connectionData  = $platformHelper->checkConnection($companyApplication);

        if(is_null($connectionData) || empty($connectionData)) {
            $this->addFlash('danger', $this->translate->trans('etsy_not_connected'));
            return $this->redirectToRoute('wixetsy_app_setting',['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
        }


        $etsyAuthData = $platformHelper->reRequestAccessToken($companyApplication);
        // dd($etsyAuthData);

        $etsyShops = [];
        $etsyShippingProfiles = [];
        $etsyCategories = [];

        $settingData = $platformHelper->getSettingValue([
            'company_application' => $companyApplication ,
            'setting_name' => "shop"
        ]);
        
        $defaultShopId = !empty($settingData) ? $settingData->getSettingValue() : "";
   
        $etsyAuthData = $platformHelper->reRequestAccessToken($companyApplication);



        if (!empty($etsyAuthData)) {

            # Getting Shop Data from Our DB
            $etsyShopDatas = $platformHelper->getEtsyShopDatas([
                "company_application" => $companyApplication,
                "user_id" => $etsyAuthData->getEtsyUserId()
            ]);
        
            foreach($etsyShopDatas as $etsyShop) {
                $etsyShops[$etsyShop->getShopId()] = $etsyShop->getShopName(); 
            }
                    
        } 

        $form = $this->createForm(SettingType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) { 
            
            $formData = $form->getData();

            foreach ($formData as $settingName => $value) {
                
                $settingValue = $platformHelper->getSettingValue([
                    "company_application" => $companyApplication,
                    "setting_name" => $settingName
                ]);
                
                $settingValue = $platformHelper->updateSettingValue($settingValue,[
                    "companyApplication" => $companyApplication,
                    "company" => $company,
                    "settingName" => $settingName,
                    "settingValue" => $value
                ]);
            }
            $this->addFlash('success', $this->translate->trans('settings_updated_successfully'));
        }

        $settingValues = $platformHelper->getSettingValues([
            "company_application" => $companyApplication
        ]);

        $settingValueData = [];

        foreach($settingValues as $settingData) {
            $settingValueData[$settingData->getSettingName()] = $settingData->getSettingValue();
        } 
        
        return $this->render(
            '@wixetsy_twig/view_templates/index.html.twig',
            [
                'header' => true,
                'menu' => true,
                'breadcrums' => true,
                'template_name' => 'settings/shop',
                'title' => 'shop_setting',
                'form' => $form->createView(),
                'company' => $company,
                'etsyShops' => $etsyShops,
                "selectedData" => $settingValueData,
            ]
        );
    }

    /**
     * Function for Sync Shop Data
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/shop/sync/{entity}",name="shop_sync")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function syncShopData(
        Request $request, $entity,
        CompanyApplication $companyApplication
    ) {

        $platformHelper = $this->HelperClass->getAppHelper("platform");
        $etsyAuthData = $platformHelper->reRequestAccessToken($companyApplication);
        
        return $this->redirectToRoute('wixetsy_setting_shop',['storeHash' => $companyApplication->getCompany()->getStoreHash()]); 
    }

    /**
     * Function for Sync Data
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/sync/setting/",name="sync_setting")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function syncSetting(
        Request $request,
        CompanyApplication $companyApplication
    ) { 
        $company = $companyApplication->getCompany();
        $platformHelper = $this->HelperClass->getAppHelper("platform");
        
        return $this->render(
            '@wixetsy_twig/view_templates/index.html.twig',
            [
                'header' => true,
                'menu' => true,
                'breadcrums' => true,
                'template_name' => 'settings/sync_data',
                'title' => 'sync_data',
                'company' => $company,
            ]
        );
    }

    /**
     * Function for Sync Data
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/sync/data/{object}",name="sync_data")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function syncData(
        Request $request, $object,
        CompanyApplication $companyApplication
    ) {
        $company = $companyApplication->getCompany();
        
        $platformHelper = $this->HelperClass->getAppHelper("platform");
        $catalogHelper = $this->HelperClass->getAppHelper("catalog");
        
        switch ($object) {

            case "etsy_categories":
                
                list($etsyCategoryData, $error) = $platformHelper->getCategoriesFromEtsy($companyApplication);
                if(is_object($etsyCategoryData)) {
                    $this->addFlash('danger', $this->translate->trans('etsy_not_connected'));
                    return $this->redirectToRoute('wixetsy_setting_other',['storeHash' => $companyApplication->getCompany()->getStoreHash()]); 
                }
                $response = $catalogHelper->updateEtsyCategories($companyApplication, $etsyCategoryData);

                if (isset($response['type']) && isset($response['message'])) {
                    $this->addFlash($response['type'], $this->translate->trans($response['message']));
                }
                return $this->redirectToRoute('wixetsy_setting_other',['storeHash' => $companyApplication->getCompany()->getStoreHash()]); 
            break;

            case "wix_categories":

                $wixCategories = $platformHelper->getCategoriesFromWix($companyApplication);
                $response = $catalogHelper->updateWixCategories($companyApplication, $wixCategories);

                if (isset($response['type']) && isset($response['message'])) {
                    $this->addFlash($response['type'], $this->translate->trans($response['message']));
                }
                return $this->redirectToRoute('wixetsy_setting_other',['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
            break;

            case 'etsy_shops':

                $etsyAuthData = $platformHelper->reRequestAccessToken($companyApplication);
               
                if (!empty($etsyAuthData)) {

                    $etsyShopData = $platformHelper->getEtsyShopByUserId($companyApplication,[
                        "keyString" => $etsyAuthData->getClientId(),
                        "userId" => $etsyAuthData->getEtsyUserId(),
                        "accessToken" => $etsyAuthData->getAccessToken()
                    ]);
                    if(isset($etsyShopData->error)) {
                        $this->addFlash('danger', $this->translate->trans('etsy_not_connected'));
                    }
                    elseif(!empty($etsyShopData)) {
                        $etsyShop = $platformHelper->getEtsyShopData([
                            "company_application" => $companyApplication,
                            "shop_id" => property_exists($etsyShopData, "shop_id") ? $etsyShopData->shop_id : "",
                            "user_id" => property_exists($etsyShopData, "user_id") ? $etsyShopData->user_id : "",
                        ]);
        
                        $etsyShop = $platformHelper->updateEtsyShopData($companyApplication, $etsyShop, [
                            "shopId" => property_exists($etsyShopData, "shop_id") ? $etsyShopData->shop_id : "",
                            "userId" => property_exists($etsyShopData, "user_id") ? $etsyShopData->user_id : "",
                            "shopName" => property_exists($etsyShopData, "shop_name") ? $etsyShopData->shop_name : "",
                            "currencyCode" => property_exists($etsyShopData, "currency_code") ? $etsyShopData->currency_code : "",
                            "shopUrl" => property_exists($etsyShopData, "url") ? $etsyShopData->url : "",
                        ]);
                        
                        if ($etsyShop->getId() != null) {
                            $this->addFlash('success', $this->translate->trans('shops_synced_successfully'));
                        }

                    } else {
                        $this->addFlash('danger', $this->translate->trans('shop_not_found'));
                    }
                } else {
                    $this->addFlash('danger', $this->translate->trans('etsy_not_connected'));
                }
                return $this->redirectToRoute('wixetsy_setting_shop',['storeHash' => $companyApplication->getCompany()->getStoreHash()]); 

            break;

            case 'etsy_shipping_profiles':
                
                $etsyAuthData = $platformHelper->reRequestAccessToken($companyApplication);

                if (!empty($etsyAuthData)) {

                    // $etsyDefaultShop = $platformHelper->getEtsyShopData([
                    //     "company_application" => $companyApplication,
                    //     "is_default" => 1
                    // ]);

                    $settingData = $platformHelper->getSettingValue([
                        "company_application" => $companyApplication,
                        "setting_name" => "shop"
                    ]);
        
                    $etsyDefaultShop = !empty($settingData) ? $settingData->getSettingValue() : "";
                    
                    if (!empty($etsyDefaultShop)) {
                        
                        list($etsyShippingProfiles, $error) = $platformHelper->getEtsyShippingProfile($companyApplication,[
                            "client_id" => $etsyAuthData->getClientId(),
                            "shopId" => $etsyDefaultShop,
                            "AccessToken" => $etsyAuthData->getAccessToken()
                        ]);

                        $etsyShippingProfiles = json_decode($etsyShippingProfiles); 

                        if (property_exists($etsyShippingProfiles, "count") && $etsyShippingProfiles->count > 0) {
                            if (property_exists($etsyShippingProfiles, "results") && !empty($etsyShippingProfiles->results)) {
                                foreach ($etsyShippingProfiles->results as $shippingProfile) {
                                    
                                    $shippingProfileData = $platformHelper->getEtsyShippingProfileData([
                                        "company_application" => $companyApplication,
                                        "shop_id" =>$etsyDefaultShop,
                                        "shipping_profile_id" => property_exists($shippingProfile, "shipping_profile_id") ? $shippingProfile->shipping_profile_id : ""
                                    ]);
                                    
                                    $etsyShippingProfileData = $platformHelper->updateEtsyShippingProfileData($companyApplication, $shippingProfileData,[
                                        "shopId" => $etsyDefaultShop,
                                        "shippingProfileId" => property_exists($shippingProfile, "shipping_profile_id") ? $shippingProfile->shipping_profile_id : "",
                                        "title" => property_exists($shippingProfile, "title") ? $shippingProfile->title : "",
                                        "displayLabel" => property_exists($shippingProfile, "processing_days_display_label") ? $shippingProfile->processing_days_display_label : ""
                                    ]);
                                }
                                if ($etsyShippingProfileData->getId() != null) {
                                    $this->addFlash('success', $this->translate->trans('shipping_profiles_synced_successfully'));
                                }
                                return $this->redirectToRoute('wixetsy_setting_other',['storeHash' => $companyApplication->getCompany()->getStoreHash()]); 
                            }
                        } else {
                            $this->addFlash('danger', $this->translate->trans('shipping_profiles_not_found_for_selected_shop'));
                        }
                    } else {
                        $this->addFlash('danger', $this->translate->trans('set_default_shop_first'));
                        return $this->redirectToRoute('wixetsy_setting_shop',['storeHash' => $companyApplication->getCompany()->getStoreHash()]); 
                    }
                } else {

                    $this->addFlash('danger', $this->translate->trans('etsy_not_connected'));
                }
               

            break;
        }

        return $this->redirectToRoute('wixetsy_setting_shop',['storeHash' => $companyApplication->getCompany()->getStoreHash()]); 
    }
}