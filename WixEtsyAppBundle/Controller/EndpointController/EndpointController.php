<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Controller\EndpointController;

use App\Helper\BaseHelper;
use App\Core\BaseController;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Translation\TranslatorInterface;
use Webkul\Modules\Wix\WixEtsyAppBundle\Utils\HelperClass;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * EndpointController
 * 
 * @Route("/",name="wixetsy_endpoint_")
 */
class EndpointController extends BaseController 
{
    public function __construct(TranslatorInterface $translator,BaseHelper $helper, HelperClass $HelperClass)
    {
        $this->_helper = $helper;
        $this->translate = $translator;
        $this->HelperClass = $HelperClass;
    }
    /**
     * Function for Etsy Redirect URI
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/redirect/{userId}",name="redirect_uri")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function etsyRedirectUri(
        Request $request, $userId,
        CompanyApplication $companyApplication
    ) {
        $queryParams = $request->query->all();
        $platformHelper = $this->HelperClass->getAppHelper("platform");
        $commonHelper = $this->_helper->getHelper('common');
        $section = 'wixetsy_config';

        $data = $commonHelper->get_section_setting([
            'sectionName' => $section, 'company' => $companyApplication->getCompany(),
            'application' => $companyApplication->getApplication()
        ],true);

        $keyString = (isset($data['wixetsy_keystring']) && !empty($data['wixetsy_keystring'])) ? $data['wixetsy_keystring']->getValue() : "";

        if ($request->server->get("APP_ENV") == "prod") {

            $appDomain = $request->server->get('REQUEST_SCHEME')."://".$request->server->get("HTTP_HOST");

        } elseif (strpos($request->server->get("HTTP_HOST"), 'dev.webkul.com') !== false) {
            
            $appDomain = $request->server->get('REQUEST_SCHEME')."://".$request->server->get("HTTP_HOST");

        } else {

            $appDomain = $request->server->get('APP_DOMAIN'); 
        }
        
        $etsyAuthData = $platformHelper->requestEtsyAccessToken($companyApplication,[
            "code" => isset($queryParams['code']) ? $queryParams['code'] : "",
            "keyString" => $keyString,
            "redirectUri" => $appDomain.$this->generateUrl('wixetsy_endpoint_redirect_uri', array('storeHash' => $companyApplication->getCompany()->getStoreHash(), "userId" => $userId))
        ]);
        
        if (!empty($etsyAuthData)) {

            $userShopData = $platformHelper->getEtsyShopByUserId($companyApplication,[
                "keyString" => $keyString,
                "userId" => $etsyAuthData->getEtsyUserId(),
                "accessToken" => $etsyAuthData->getAccessToken()
            ]);
            
            $etsyShopData = $platformHelper->getEtsyShopData([
                "company_application" => $companyApplication
            ]);

            $etsyShopData = $platformHelper->updateEtsyShopData($companyApplication, $etsyShopData,[
                "shopId" => property_exists($userShopData, "shop_id") ? $userShopData->shop_id : "",
                "shopName" => property_exists($userShopData, "shop_name") ? $userShopData->shop_name : "",
                "userId" => property_exists($userShopData, "user_id") ? $userShopData->user_id : "",
                "currencyCode" => property_exists($userShopData, "currency_code") ? $userShopData->currency_code : "",
                "shopUrl" => property_exists($userShopData, "url") ? $userShopData->url : "",
            ]);

            if (!empty($etsyShopData)) {
                $this->_helper->add_notification(
                    'success',
                    $this->_helper->_trans('wixetsy_shop_data_synced_successfully')
                );  
            }

            
            // $platformHelper->requestEtsyUserShopData($companyApplication,[
            //     "keyString" => $keyString,
            //     "accessToken" => $etsyAuthData->getAccessToken()
            // ]);
        }
        
        $this->_helper->add_notification(
            'success',
            $this->_helper->_trans('wixetsy_authenticated_successfully')
        );

        
        $userHelper = $this->_helper->getHelper('user');

        $user = $userHelper->get_user([
            "id" => $userId,
        ]); 
        
        $token = new UsernamePasswordToken(
            $user,
            null,
            'app_area',
            $user->getRoles()
        );
        $this->get('security.token_storage')->setToken($token);
        $this->get('session')->set('_security_app_area', serialize($token));

        //return new RedirectResponse($this->generateUrl('wixetsy_app_setting',['storeHash' => $companyApplication->getCompany()->getStoreHash()]));
        return $this->redirectToRoute('wixetsy_app_setting',['storeHash' => $companyApplication->getCompany()->getStoreHash()]);

    }

    /**
     * Function for Etsy Redirect URI
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/test",name="test")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function test(Request $request,CompanyApplication $companyApplication)
    {

        $response = $this->forward('Webkul\Modules\Wix\WixEtsyAppBundle\Controller\IndexController\DefaultController::appSettings', [
            'storeHash' => $companyApplication->getCompany()->getStoreHash(),
            "app_code" => $companyApplication->getApplication()->getCode()
        ]);

        return $response;
        return $this->redirectToRoute('wixetsy_app_setting',['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
    }
}