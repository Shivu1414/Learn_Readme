<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Seller\Security;

use App\Core\BaseController;
use App\Helper\BaseHelper;
use App\Entity\CompanyApplication;
use App\Helper\MediaHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Webkul\Modules\Wix\WixmpBundle\Form\Seller\ChangePasswordType;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerUser;
use Webkul\Modules\Wix\WixmpBundle\Events\SellerEvent;
use Webkul\Modules\Wix\WixmpBundle\Entity\Seller;
use Webkul\Modules\Wix\WixmpBundle\Utils\SellerHelper;
use Webkul\Modules\Wix\WixmpBundle\Form\Seller\SellerFormType;
use ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/secure", name="mp_wix_seller_secure_")
 */
class SecurityController extends BaseController
{
    public function __construct(TranslatorInterface $translator, EventDispatcherInterface $dispatcher, EntityManagerInterface $entityManager)
    {
        $this->translate = $translator;
        $this->dispatcher = $dispatcher;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/login", name="login")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function login(Request $request, AuthenticationUtils $authenticationUtils, CompanyApplication $companyApplication,BaseHelper $baseHelper)
    {
        $user_data = $this->getUser();
        $company = $companyApplication->getCompany();
        $commonHelper = $baseHelper->getHelper('common');

        if ($user_data) {
            return $this->redirectToRoute('wixmp_seller_dashboard', [
                'storeHash' => $company->getStoreHash(),
            ]);
        }

        $settingData = $commonHelper->get_section_setting(['sectionName' => 'seller', 'company' => $company, 'application' => $companyApplication->getApplication()], true);

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        $seller_forgot_pwd = $this->createForm(ChangePasswordType::class);

        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'template_name' => 'security/login',
            'title' => 'login',
            'last_username' => $lastUsername,
            'error' => $error,
            'company' => $company,
            'seller_forgot_pwd' => $seller_forgot_pwd->createView(),
            'setting_data' => $settingData,
        ]);
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout(): void
    {
        throw new \Exception('ADMIN This should never be reached!');
    }

    /**
     * @Route("/forgot_password", name="forgot_password")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function forgot_password(Request $request, CompanyApplication $companyApplication, AuthenticationUtils $authUtils, UserPasswordEncoderInterface $passwordEncoder)
    {
        $company = $companyApplication->getCompany();
        $error = $authUtils->getLastAuthenticationError();
        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('ROLE_WIXMP_SELLER')) {
            // redirect authenticated users to homepage
            return $this->redirectToRoute('wixmp_seller_dashboard', array('storeHash' => $company->getStoreHash()));
        }
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (isset($data['username']) && !empty($data['username'])) {
                $username = $data['username'];
                $UserEM = $this->getDoctrine()->getRepository(SellerUser::class);
                $userDataArry = $UserEM->findBy(['username' => $username, 'company' => $company]);
                //$userDataArry = $UserEM->getUserByUsernnameOrEmail(array('username' => $username, 'company' => $company));
                if (!empty($userDataArry)) {
                    $user_data = reset($userDataArry);
                    $em = $this->getDoctrine()->getManager();
                    $plainsPassword = rand();
                    $plainsPassword = sha1($plainsPassword);
                    $plainsPassword = substr($plainsPassword, 0, 9);
                    $user_data->setPlainPassword($plainsPassword);
                    $password = $passwordEncoder->encodePassword($user_data, $user_data->getPlainPassword());
                    $user_data->setPassword($password);

                    $this->addFlash('success', $this->translate->trans('message.common.password_reset_mail_sent'));

                    $em->persist($user_data);
                    $em->flush();
                    if ($user_data->getId()) {
                        // Dispatch Event for user password update
                        $SellerEvent = new SellerEvent($companyApplication, $user_data->getSeller(), $user_data);
                        $this->dispatcher->dispatch(
                            $SellerEvent, SellerEvent::WIX_SELLER_FORGOT_PASSWORD
                        );
                    }
                } else {
                    $this->addFlash('danger', $this->translate->trans('message.common.user_not_exist'));
                }
            } else {
                $this->addFlash('danger', $this->translate->trans('message.common.username_is_manadatory'));
            }
        }

        return $this->redirectToRoute('mp_wix_seller_secure_login', ['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
    }

    /**
     * @Route("/forgot_username", name="forgot_username")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function forgot_username(Request $request, CompanyApplication $companyApplication, AuthenticationUtils $authUtils, UserPasswordEncoderInterface $passwordEncoder)
    {
        $company = $companyApplication->getCompany();
        $error = $authUtils->getLastAuthenticationError();
        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('ROLE_WIXMP_SELLER')) {
            // redirect authenticated users to homepage
            return $this->redirectToRoute('wixmp_seller_dashboard', array('storeHash' => $company->getStoreHash()));
        }
        $userEmail = $request->request->get('_email');

        if (!empty($userEmail)) {
            $UserEM = $this->getDoctrine()->getRepository(SellerUser::class);
            $userDataArry = $UserEM->findBy(['email' => $userEmail, 'company' => $company]);
            if (!empty($userDataArry)) {
                //as of now seller email is not unique :
                foreach ($userDataArry as $user_data) {
                    if ($user_data->getId()) {
                        // Dispatch Event for user password update
                        $SellerEvent = new SellerEvent($companyApplication, $user_data->getSeller(), $user_data);
                        $this->dispatcher->dispatch(
                            $SellerEvent, 
                            SellerEvent::WIX_SELLER_FORGOT_PASSWORD
                        );
                    }
                }
                $this->addFlash('success', $this->translate->trans('message.common.forgot_username_details_send_successfully'));
            } else {
                $this->addFlash('danger', $this->translate->trans('message.common.user_not_exist'));
            }
        } else {
            $this->addFlash('danger', $this->translate->trans('message.common.email_is_manadatory'));
        }

        return $this->redirectToRoute('mp_wix_seller_secure_login', ['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
    }

    /**
     * @Route("/register", name="register")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function register(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper,BaseHelper $baseHelper)
    {
        $user_data = $this->getUser();
        $company = $companyApplication->getCompany();
        $commonHelper = $baseHelper->getHelper('common');

        /** custom field 27-12-2022 */
        $getCustomFields = $sellerHelper->get_wix_subapp_custom_field($companyApplication);
        /** end custom filed */
        
        if ($user_data) {
            return $this->redirectToRoute('wixmp_seller_dashboard', [
                'storeHash' => $company->getStoreHash(),
            ]);
        }
        $seller = new Seller();

        $settingData = $commonHelper->get_section_setting(['sectionName' => 'seller', 'company' => $company, 'application' => $companyApplication->getApplication()], true);
        
        $company = $companyApplication->getCompany();
        $seller_plans = $sellerHelper->get_plan_list($company, 'A');
        $form = $this->createForm(SellerFormType::class, $seller, ['plan_list' => $seller_plans, 'custom_field_data'=> $getCustomFields]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (
                isset($settingData['enable_google_captcha']) && 
                isset($settingData['google_captcha_site_key']) &&
                isset($settingData['google_captcha_secret_key']) &&
                $settingData['enable_google_captcha']->getValue() == 1 &&
                $settingData['google_captcha_site_key']->getValue() != "" &&
                $settingData['google_captcha_secret_key']->getValue() != ""
            ) {

                $recaptcha = new ReCaptcha($settingData['google_captcha_secret_key']->getValue());
                $resp = $recaptcha->verify($request->request->get('g-recaptcha-response'), $request->getClientIp());
                
                if (!$resp->isSuccess()) {
                    
                    $errorCodes = $resp->getErrorCodes();
            
                    $msg = isset($errorCodes[0]) ? "Google ReCaptcha Error : ".$errorCodes[0]. ". ". $this->translate->trans('contact_to_admin') : $this->translate->trans('something_went_wrong_google_recaptcha_contact_to_admin');
                     
                    if (isset($errorCodes[0]) && $errorCodes[0] != "invalid-input-secret" ) {
                        $msg .= $this->translate->trans("or_refresh_the_page");
                    }

                    $this->addFlash('danger', $msg);
                    return $this->redirectToRoute('mp_wix_seller_secure_register', ['storeHash' => $company->getStoreHash()]);
                }
            }

            $selected_plan = $form['plan']->getData();
            $formData = $form->getData();

            if (!$selected_plan) {
                $this->addFlash('danger', $this->translate->trans('message.seller_register.plan_not_found'));

                return $this->redirectToRoute('mp_wix_seller_secure_register', ['storeHash' => $company->getStoreHash()]);
            }

            $plan_data = $sellerHelper->get_seller_plan(['id' => $selected_plan, 'company' => $company->getId()]);

            if ($plan_data->getCompany()->getId() != $company->getId()) {
                $this->addFlash('success', $this->translate->trans('message.seller_register.unauthorized_access'));

                return $this->redirectToRoute('mp_wix_seller_secure_register', ['storeHash' => $company->getStoreHash()]);
            }

            $isExist = $sellerHelper->get_seller(['company' => $company, 'email' => $seller->getEmail()]) ? TRUE : FALSE ;
            
            if ($isExist) {
                $this->addFlash('danger', $this->translate->trans('message.seller_register.seller_exist'));

                return $this->redirectToRoute('mp_wix_seller_secure_register', ['storeHash' => $company->getStoreHash()]);
            }

            $_data = array(
                'company' => $company,
                'current_plan' => $plan_data,
                'status' => 'N',
                'password' => $formData->getPassword()
            );
            $seller->setIsArchieved(0);
            $seller = $sellerHelper->update_seller($seller, $_data);

            /** Custom field value add */
            $customFieldObj = [];
            foreach ($getCustomFields as $customField) {
                $customFieldId = $customField->getId();
                $fieldName = $customField->getFeildName();
                if (isset($form[$fieldName])) {
                    $customFieldValue = $form[$fieldName]->getData();
                    $customFieldObj[$customFieldId] = $customFieldValue;
                }
            }

            $customFieldJson = '';
            if(isset($customFieldObj) && !blank($customFieldObj)) {
                $customFieldJson = json_encode($customFieldObj);
            }
            $customFieldValues['custom_field_value'] = $customFieldJson;

            $em = $this->entityManager;
            $seller->setCustomFields($customFieldValues);
            $em->persist($seller);
            $em->flush();
            /** End Custom field value add */

            $SellerEvent = new SellerEvent($companyApplication, $seller);
            $this->dispatcher->dispatch(
                $SellerEvent, 
                SellerEvent::WIX_SELLER_ACCOUNT_REGISTER
            );

            $this->addFlash('success', $this->translate->trans('message.seller_register.under_review'));

            return $this->redirectToRoute('mp_wix_seller_secure_login', ['storeHash' => $company->getStoreHash()]);
        }
        
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'template_name' => 'security/register',
            'title' => $this->translate->trans('registration'),
            'company' => $company,
            'form' => $form->createView(),
            'custom_field_list' => $getCustomFields,
            'setting_data' => $settingData,
        ]);
    }

    /**
     * @Route("/verifycaptchacred", name="verify_captcha_cred")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function verifyGoogleCaptchaCredentials(Request $request, CompanyApplication $companyApplication, BaseHelper $baseHelper)
    {
        $captchaResponse = $request->request->get("captchaResponse");
        $company = $companyApplication->getCompany();
        $commonHelper = $baseHelper->getHelper('common');
        $settingData = $commonHelper->get_section_setting([
            'sectionName' => 'seller',
            'company' => $company,
            'application' => $companyApplication->getApplication()
        ], true);
        $secretKey = isset($settingData['google_captcha_secret_key']) ? $settingData['google_captcha_secret_key']->getValue() : "";
        $recaptcha = new ReCaptcha($secretKey);
        $resp = $recaptcha->verify($captchaResponse, $request->getClientIp());
        if (!$resp->isSuccess()) {
            $errorCodes = $resp->getErrorCodes();
            
            $msg = isset($errorCodes[0]) ? "Google ReCaptcha Error : ".$errorCodes[0]. ". ". $this->translate->trans('contact_to_admin') : $this->translate->trans('something_went_wrong_google_recaptcha_contact_to_admin');
             
            if (isset($errorCodes[0]) && $errorCodes[0] != "invalid-input-secret" ) {
                $msg .= $this->translate->trans("or_refresh_the_page");
            }

            return new JsonResponse(['status' => TRUE, 'msg' => $msg], 200,['Access-Control-Allow-Origin' => '*']);
        } else {
            return new JsonResponse(['status' => FALSE], 200,['Access-Control-Allow-Origin' => '*']);
        }
    }
}