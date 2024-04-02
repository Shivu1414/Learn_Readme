<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Controller\IndexController;

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
use Doctrine\ORM\EntityManagerInterface;
use Webkul\Modules\Wix\WixEtsyAppBundle\Entity\EtsyAuth;

/**
 * DefaultController
 * 
 * @Route("/",name="wixetsy_")
 * @Security("is_granted('ROLE_WIXETSY_ADMIN')")
 */
class DefaultController extends BaseController
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
     * Function for index action
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/dashboard",name="dashboard")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function indexAction(
        Request $request,
        CompanyApplication $companyApplication
    ) { 
        return $this->render(
            'application/index.html.twig',
            [
                'header' => true,
                'menu' => true,
                'breadcrums' => true,
                'template_name' => 'dashboard/dashboard',
                'title' => 'wixetsy_integration_dashboard'
            ]
        );
    }

    /**
     * Function for app settings
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/",name="index")
     * @Route("/app-setting",name="app_setting")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function appSettings(
        Request $request,
        CompanyApplication $companyApplication,
        EntityManagerInterface $entityManager
    ) { 
        $etsyAuth = $entityManager->getRepository(EtsyAuth::class);
        $authData = $etsyAuth->findOneBy(['company' => $companyApplication->getCompany(),]);
        // dd($authData);
        $company = $companyApplication->getCompany();
        $application = $companyApplication->getApplication();

        return $this->render(
            '@wixetsy_twig/view_templates/index.html.twig',
            [
                'header' => true,
                'menu' => true,
                'breadcrums' => true,
                'template_name' => 'settings/setting',
                'title' => 'general_setting',
                'company' => $company,
                'authData' => $authData,
            ]
        );
    }

    /**
     * Function for app settings
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/app-setting/connect/{event}",name="app_setting_connect")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function establishEtsyConnection(
        Request $request, $event,
        CompanyApplication $companyApplication
    ) {    
        
        $company = $companyApplication->getCompany();  
        $application = $companyApplication->getApplication();
        $section = 'wixetsy_config';
        $commonHelper = $this->_helper->getHelper('common'); 
        $platformHelper = $this->HelperClass->getAppHelper("platform");
        
        if ($event == "wixetsy_connect") {
            
            // $data = $commonHelper->get_section_setting([
            //     'sectionName' => $section,
            //     'company' => $company,
            //     'application' => $application
            // ],true);
            $data['wixetsy_keystring'] = getenv('WIX_ETSY_KEY_STRING');

            if (empty($data) || !isset($data['wixetsy_keystring']) || $data['wixetsy_keystring'] == null) {
                
                $this->_helper->add_notification(
                    'danger',
                    $this->_helper->_trans('wixetsy_setting_save_first')
                );

                return $this->redirectToRoute('wixetsy_app_setting',['storeHash' => $company->getStoreHash()]);

            } elseif (isset($data['wixetsy_keystring']) && $data['wixetsy_keystring'] != null) {
                
                if ($request->server->get("APP_ENV") == "prod") {

                    $appDomain = $request->server->get('REQUEST_SCHEME')."://".$request->server->get("HTTP_HOST");

                } elseif (strpos($request->server->get("HTTP_HOST"), 'dev.webkul.com') !== false) {
                    
                    $appDomain = $request->server->get('REQUEST_SCHEME')."://".$request->server->get("HTTP_HOST");

                } else {

                    $appDomain = $request->server->get('APP_DOMAIN'); 
                }
                
                $authorizationUrl = $platformHelper->etsyAuthentication(
                    $companyApplication,[ 
                        "keyString" => $data['wixetsy_keystring'],
                        "redirectUri" =>  $appDomain . $this->generateUrl('wix_endpoint_etsy_redirect_uri', ['platform' => 'wix'])
                    ]
                );
            
                return $this->redirect($authorizationUrl);

            } else {

                $this->_helper->add_notification(
                    'danger',
                    $this->_helper->_trans('wixetsy_setting_data_miss')
                );

                return $this->redirectToRoute('wixetsy_app_setting',['storeHash' => $company->getStoreHash()]);
            }

            return $this->redirectToRoute('wixetsy_app_setting',['storeHash' => $company->getStoreHash()]);
        }

        return $this->redirectToRoute('wixetsy_app_setting',['storeHash' => $company->getStoreHash()]);
    }
       /**
     * Function for app settings
     *
     * @param Request            $request            Request Object
     * @param CompanyApplication $companyApplication Company Application
     * 
     * @Route("/app-setting/disconnect/{event}",name="app_setting_disconnect")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     * @return void
     */
    public function DisConnect(Request $request, CompanyApplication $companyApplication,EntityManagerInterface $entityManager, $event)
    {
        $company = $companyApplication->getCompany();
        
        if($event == "wixetsy_disconnect"){
            $platformHelper = $this->HelperClass->getAppHelper('platform');

            $response =  $platformHelper->DisconnectApp($companyApplication);

            return $this->redirectToRoute('wixetsy_app_setting',['storeHash' => $company->getStoreHash()]);
        }        
    }

     /**
     * @Route("support", name="support")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function support(Request $request, CompanyApplication $companyApplication)
    {
        return $this->render('@wixetsy_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'settings/support',
            'title' => 'settings_support',
        ]);
    }

    /**
     * @Route("/redirect-blog/", name="userguide_blog")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function redirectToBlogPage(Request $request, CompanyApplication $companyApplication){
        return $this->redirect("https://webkul.com/blog/user-guide-for-etsy-integration-for-wix/");
    }

    /**
     * @Route("/redirect-video/", name="userguide_video")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function redirectToVideo(Request $request, CompanyApplication $companyApplication){
        //return $this->redirect("https://www.youtube.com/watch?v=qfKIoKBm34c&list=PL8h9hTFOactZquJ0okaApRcJfyaGWM0H");
    }
}
