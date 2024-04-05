<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Admin\SettingController;

use App\Helper\BaseHelper;
use App\Core\BaseController;
use App\Entity\CompanyApplication;
use App\Form\Custom\CustomFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Webkul\Modules\Wix\WixmpBundle\Utils\WixMpCompanyHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Bridge\Google\Transport\GmailSmtpTransport;
use App\Helper\EmailHelper;


/**
 * @Route("/setting", name="wixmp_setting_")
 * @Security("is_granted('ROLE_WIXMP_ADMIN')")
 */
class SettingController extends BaseController
{
    public function __construct(TranslatorInterface $translator, ContainerInterface $container)
    {
        $this->translate = $translator;
        $this->serviceContainer = $container;
    }

    /**
     * @Route("/email/", name="email")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function email(Request $request, CompanyApplication $companyApplication, EmailHelper $EmailHelper)
    {
        $helper = new BaseHelper($this->getDoctrine()->getManager(), $this->serviceContainer);
        $company = $companyApplication->getCompany();
        $application = $companyApplication->getApplication();

        $section = 'email';
        $commonHelper = $helper->getHelper('common');

        $general_setting_data = $commonHelper->get_section_settings($section, 'application', $application->getCode());
        $data = $commonHelper->get_section_setting(['sectionName' => $section, 'company' => $company, 'application' => $application], true);
        
        // bind data
        $formBindData = [];
        foreach ($data as $fieldName => $fieldValue) {
            $val = $fieldValue->getValue();
            if (empty($val)) {
                $val = false;
            }
            if ($val == '1' || $val == '0') {
                $val = (bool) $val;
            }
            $formBindData[$fieldName] = $val;
        }
        
        $form = $this->createForm(
            CustomFormType::class, $formBindData, [
                'setting_data' => array(
                    'fields' => $general_setting_data['fields'], 'values' => $data,
                ),
                'translation_domain' => 'messages'
            ]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            
            $form_values = $form->getData();
            foreach ($form_values as $setting_name => $value) {
                $commonHelper->update_section_setting($value, $setting_name, $section, $company, $application);
            }
            $this->addFlash('success', $this->translate->trans('message.common.record_created_successfully'));
            return $this->redirectToRoute('wixmp_setting_email', ['storeHash' => $company->getStoreHash()]);
        }
        //Test Mail
        if(!empty($request->get('testmail'))){
            
            if(!isset($data['email_from']) || $data['email_from']->getValue()==''){
                $this->addFlash('danger', $this->translate
                ->trans('message.common.required_fields'));
                return $this->redirectToRoute('wixmp_setting_email', ['storeHash' => $company->getStoreHash()]);    
            } else {
                
                $sendTestmail =   $EmailHelper->sendMail($data['email_from']->getValue(),'','seller/emailtest','application', array(
                    'company' => $company,
                    'application' => $application,
                
                ),$data['email_from']->getValue());
            
                if($sendTestmail==true){
                    $this->addFlash('success', $this->translate
                        ->trans('message.common.testmail_sent'));
                } else {
                    $this->addFlash('danger', $this->translate->trans('message.common.testmail_not_sent'));
                }
                return $this->redirectToRoute('wixmp_setting_email', ['storeHash' => $company->getStoreHash()]);
            }
        }
        //End Test Mail
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'settings/setting',
            'title' => 'setting_email',
            'form' => $form->createView(),
            'section' => $section,
            'company' => $company,
            'application' => $application,
        ]);
    }

    /**
     * @Route("/general/", name="general")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function general(Request $request, CompanyApplication $companyApplication, BaseHelper $helper)
    {
        $company = $companyApplication->getCompany();
        $application = $companyApplication->getApplication();

        $section = 'general';
        $commonHelper = $helper->getHelper('common');
        
        $general_setting_data = $commonHelper->get_section_settings($section, 'application', $application->getCode());
        $data = $commonHelper->get_section_setting(['sectionName' => $section, 'company' => $company, 'application' => $application], true);
        // bind data
        $formBindData = [];
        if (!empty($data)) {
            foreach ($data as $fieldName => $fieldValue) {
                $val = $fieldValue->getValue();
                if (empty($val)) {
                    $val = false;
                }
                if ($val == '1' || $val == '0') {
                    $val = (bool) $val;
                }
                $formBindData[$fieldName] = $val;
            }
        }
        
        $form = $this->createForm(
            CustomFormType::class,
            $formBindData,
            [
                'setting_data' => array('fields' => $general_setting_data['fields'], 'values' => $data),
            ]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $form_values = $form->getData();
            $settingsLength = count($form_values);
            $flushCounter = 0;
            foreach ($form_values as $setting_name => $value) {
                ++$flushCounter;
                $flush = 0;
                // TODOl: Improve code : we need to flush only after all settings persists for performance: one alternate is flush using entity manager after foreach
                if ($settingsLength == $flushCounter) {
                    $flush = 1;
                }
                $commonHelper->update_section_setting($value, $setting_name, $section, $company, $application, $flush);
            }
            $this->addFlash('success', $this->translate->trans('message.common.seller_setting_updated_successfully'));

            return $this->redirectToRoute('wixmp_setting_general', ['storeHash' => $company->getStoreHash()]);
        }

        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'settings/setting',
            'title' => 'setting_general',
            'form' => $form->createView(),
            'section' => $section,
            'company' => $company,
            'application' => $application,
        ]);
    }

    /**
     * @Route("/setup/", name="setup")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function setup(Request $request, CompanyApplication $companyApplication)
    {
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'settings/setup',
            'title' => 'settings_setup',
        ]);
    }

    /**
     * @Route("/support/", name="support")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function support(Request $request, CompanyApplication $companyApplication)
    {
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'settings/support',
            'title' => 'settings_support',
        ]);
    }

    /**
     * @Route("/seller/", name="seller")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function seller(Request $request, CompanyApplication $companyApplication)
    {
        $helper = new BaseHelper($this->getDoctrine()->getManager(), $this->serviceContainer);
        $company = $companyApplication->getCompany();
        $application = $companyApplication->getApplication();
        $section = 'seller';

        // Plan Feature
        $planApplications = $companyApplication->getSubscription()->getPlanApplication()->getFeatures();
        if($planApplications) {
            $planApplicationData = $planApplications;
        } else {
            $planApplicationData = [];
        }

        $commonHelper = $helper->getHelper('common');

        $general_setting_data = $commonHelper->get_section_settings($section, 'application', $application->getCode());
        $data = $commonHelper->get_section_setting(['sectionName' => $section, 'company' => $company, 'application' => $application], true);

        // bind data
        $formBindData = [];
        foreach ($data as $fieldName => $fieldValue) {
            $val = $fieldValue->getValue();
            if (empty($val)) {
                $val = false;
            }
            if (($val == '1' || $val == '0') && $fieldName != 'seller_allowed_customer_details') {
                $val = (bool) $val;
            }
            $formBindData[$fieldName] = $val;
        }
        
        $form = $this->createForm(
            CustomFormType::class,
            $formBindData,
            [
                'setting_data' => array('fields' => $general_setting_data['fields'], 'values' => $data),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $form_values = $form->getData();
            $requestData = $request->request->get('custom_form');

            if (isset($requestData['seller_allowed_categories'])) {
                $form_values['seller_allowed_categories'] = implode(',', $requestData['seller_allowed_categories']);
            } else {
                $form_values['seller_allowed_categories'] = '';
            }

            $settingsLength = count($form_values);
            $flushCounter = 0;
            
            foreach ($form_values as $setting_name => $value) {
                ++$flushCounter;
                $flush = 0;
                // TODOl: Improve code : we need to flush only after all settings persists for performance: one alternate is flush using entity manager after foreach
                if ($settingsLength == $flushCounter) {
                    $flush = 1;
                }
                $commonHelper->update_section_setting($value, $setting_name, $section, $company, $application, $flush);
            }

            $this->addFlash('success', $this->translate->trans('message.common.seller_setting_updated_successfully'));

            return $this->redirectToRoute('wixmp_setting_seller', ['storeHash' => $company->getStoreHash()]);
        }

        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'settings/setting',
            'title' => 'setting_seller',
            'form' => $form->createView(),
            'section' => $section,
            'company' => $company,
            'application' => $application,
            'plan_application_data' => $planApplicationData,
        ]);
    }

    /**
     * @Route("/domain/", name="domain")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function domain(Request $request, CompanyApplication $companyApplication)
    {
        
        $helper = new BaseHelper(
            $this->getDoctrine()->getManager(), $this->serviceContainer
        );
        
        $company = $companyApplication->getCompany();
        
        $application = $companyApplication->getApplication();
        
        $section = 'domain';
        $commonHelper = $helper->getHelper('common');
        
        $general_setting_data = $commonHelper->get_section_settings(
            $section, 'application', $application->getCode()
        );

        $data = $commonHelper->get_section_setting(
            ['sectionName' => $section, 'company' => $company, 'application' => $application], true
        );
       
        $formBindData = [];
        foreach ($data as $fieldName => $fieldValue) {
            $val = $fieldValue->getValue();
            if (empty($val)) {
                $val = false;
            }
            if ($val == '1' || $val == '0') {
                $val = (bool) $val;
            }
            $formBindData[$fieldName] = $val;
        }
       
        $form = $this->createForm(
            CustomFormType::class, $formBindData, [
                'setting_data' => array(
                    'fields' => $general_setting_data['fields'], 'values' => $data,
                ),
            ]
        );
       
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $form_values = $form->getData(); 
            foreach ($form_values as $setting_name => $value) {
                //if value blank
                if($value==null){
                    $commonHelper->update_section_setting(
                        $value, $setting_name, $section, $company, $application
                    );
                    $this->addFlash(
                        'danger', $this->translate
                            ->trans('message.common.domain_setting_cannot_blank')
                    );
        
                    return $this->redirectToRoute(
                        'wixmp_setting_domain', [
                            'storeHash' => $company->getStoreHash(),
                        ]
                    );
                }
                //Domain validate code start
                $pattern = "/^(.*)\.[^.]+\.[a-zA-Z]{2,}$/";   
                
                if (!preg_match($pattern, $value))
                {
                    $this->addFlash('danger', $this->translate
                                ->trans('message.common.domain_not_valid'));
                    return $this->redirectToRoute(
                        'wixmp_setting_domain', [
                            'storeHash' => $company->getStoreHash(),
                        ]
                    );
                } else {
                    // create a new cURL resource
                    $ch = curl_init();
                    // set URL and other appropriate options
                    curl_setopt($ch, CURLOPT_URL, $value);
                    curl_setopt($ch, CURLOPT_HEADER, false);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_NOBODY, true);
                    

                    // grab URL and pass it to the browser
                    $response = curl_exec($ch);
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $all_error_page_code = array('302','401','404');
                    if(in_array($http_code, $all_error_page_code)){         
                        $response = false;
                    }
                    // close cURL resource, and free up system resources
                    curl_close($ch);
                    // dd($response);
                    if($response!=false){
                        $commonHelper->update_section_setting(
                            $value, $setting_name, $section, $company, $application
                        );
                        $this->addFlash(
                            'success', $this->translate
                                ->trans('message.common.domain_setting_updated_successfully')
                        );
            
                        return $this->redirectToRoute(
                            'wixmp_setting_domain', [
                                'storeHash' => $company->getStoreHash(),
                            ]
                        );
                    }else{
                        $this->addFlash('danger', $this->translate
                                ->trans('message.common.domain_not_exist'));
                        return $this->redirectToRoute(
                            'wixmp_setting_domain', [
                                'storeHash' => $company->getStoreHash(),
                            ]
                        );
                    }
                }
                //Domain validate End    
            }
        }

        return $this->render(
            '@wixmp_twig/view_templates/index.html.twig', [
                'header' => true,
                'menu' => true,
                'breadcrums' => true,
                'template_name' => 'settings/setting',
                'title' => 'settings_domain',
                'form' => $form->createView(),
                'section' => $section,
                'company' => $company,
                'application' => $application,
            ]
        );
    }

    /**
     * @Route("/redirect-blog/", name="userguide_blog")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function redirectToBlogPage(Request $request, CompanyApplication $companyApplication){
        return $this->redirect("https://webkul.com/blog/user-guide-for-wix-multivendor-marketplace/");
    }

    /**
     * @Route("/redirect-video//", name="userguide_video")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function redirectToVideo(Request $request, CompanyApplication $companyApplication){
        return $this->redirect("https://www.youtube.com/watch?v=qfKIoKBm34c&list=PL8h9hTFOactZquJ0okaApRcJfyaGWM0HF");
    }
}