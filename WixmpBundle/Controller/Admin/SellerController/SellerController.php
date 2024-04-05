<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Admin\SellerController;

use App\Helper\BaseHelper;
use App\Core\BaseController;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Webkul\Modules\Wix\WixmpBundle\Utils\SellerHelper;
use Webkul\Modules\Wix\WixmpBundle\Events\SellerEvent;
use Webkul\Modules\Wix\WixmpBundle\Entity\Seller;
use Webkul\Modules\Wix\WixmpBundle\Form\Seller\SellerFormType;
use Webkul\Modules\Wix\WixmpBundle\Utils\WixMpBaseHelper;
use Webkul\Modules\Wix\WixmpBundle\Utils\UserHelper;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerUser;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPlan;
use Webkul\Modules\Wix\WixmpBundle\Form\Seller\BatchActionType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @Route("/seller", name="wixmp_seller_")
 * @Security("is_granted('ROLE_WIXMP_ADMIN')")
 */
class SellerController extends BaseController
{
    public function __construct(TranslatorInterface $translator, EventDispatcherInterface $dispatcher, EntityManagerInterface $entityManager)
    {
        $this->translate = $translator;
        $this->dispatcher = $dispatcher;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/", name="manage")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function manage(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $params = $request->query->All();
        $params['company'] = $company = $companyApplication->getCompany();
        $params['is_archieved'] = 0;

        $form = $this->createForm(BatchActionType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData(); 
            $requestData = $request->request->all();

            if (isset($formData['batch_action'])) {
                $notifications = $sellerHelper->performBatchAction($request, $formData, $companyApplication);
                if (!empty($notifications)) {
                    $notifications = isset($notifications['notifications']) ? $notifications['notifications'] : [];
                    foreach ($notifications as $type => $notification) {
                        if (!is_array($notification)) {
                            $this->addFlash($type, $notification['message']);
                        } else {
                            $message = isset($notification[0]) ? $notification[0] : "";
                            $this->addFlash($type, $message);
                        }
                    }
                }
            }
        }
        
        list($seller_list, $search) = $sellerHelper->get_sellers($params);

        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'breadcrums_template' => 'common/breadcrums',
            'template_name' => 'seller/seller_manage',
            'title' => 'manage_seller_companies',
            'seller_list' => $seller_list,
            'list_count' => $seller_list->getTotalItemCount(),
            'search' => $search,
            'form' => $form->createView(),
            'page' => "seller_manage",
        ]);
    }

    /**
     * @Route("/archive/manage", name="archieve_manage")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function archieveManage(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $params = $request->query->All();
        $params['company'] = $company = $companyApplication->getCompany();
        $params['is_archieved'] = 1;

        $form = $this->createForm(BatchActionType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $requestData = $request->request->all();

            if (isset($formData['batch_action'])) {

                if ($formData['batch_action'] == "unarchive") {
                    
                    $sellerEvent = new SellerEvent($companyApplication, null, null, $request);
                    $this->dispatcher->dispatch(
                        $sellerEvent,
                        SellerEvent::WIX_SELLER_UNARCHIVE_STATUS
                    );

                    if ($sellerEvent->getActionAllowed() == 'N') {
                        
                        return $this->redirectToRoute('wixmp_seller_archieve_manage', ['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
                    }
                }

                $notifications = $sellerHelper->performBatchAction($request, $formData, $companyApplication);
                if (!empty($notifications)) {
                    $notifications = isset($notifications['notifications']) ? $notifications['notifications'] : [];
                    foreach ($notifications as $type => $notification) {
                        if (!is_array($notification)) {
                            $this->addFlash($type, $notification['message']);
                        } else {
                            $message = isset($notification[0]) ? $notification[0] : "";
                            $this->addFlash($type, $message);
                        }
                    }
                }
            }
        }
        
        list($seller_list, $search) = $sellerHelper->get_sellers($params);
        //archivedSellers = $sellerHelper->get_archived_seller_list($company);

        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'breadcrums_template' => 'common/breadcrums',
            'template_name' => 'seller/seller_archieve',
            'title' => 'manage_seller_archieve',
            'list_count' => $seller_list->getTotalItemCount(),
            'seller_list' => $seller_list,
            'search' => $search,
            'form' => $form->createView(),
            'page' => "seller_achived",
            //'archivedSellers' => $archivedSellers
        ]);
    }

    /**
     * @Route("/add", name="add")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function create(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $sellerEvent = new SellerEvent($companyApplication);
        $this->dispatcher->dispatch(
            $sellerEvent, 
            SellerEvent::WIX_SELLER_COMPANY_PRE_ADD
        );
        
        if ($sellerEvent->getActionAllowed() == 'N') {
            return $this->redirectToRoute('wixmp_seller_manage', ['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
        }

        /** custom field 27-12-2022 */
        $getCustomFields = $sellerHelper->get_wix_subapp_custom_field($companyApplication);
        /** end custom filed */

        $seller = new Seller();

        $company = $companyApplication->getCompany();

        $seller_plans = $sellerHelper->get_plan_list($company, 'A');
        $seller->setCompany($company);

        $form = $this->createForm(SellerFormType::class, $seller, ['plan_list' => $seller_plans,'allow_extra_fields' => true, 'custom_field_data'=> $getCustomFields]);
        $form->handleRequest($request);
       
        if ($form->isSubmitted() && $form->isValid()) {
            $params = $request->query->All();
            $params['company'] = $company = $companyApplication->getCompany();
            $params['is_archieved'] = 0;
            $data = $form->getData();
            // dd($data->getseller());
            $existing_seller = $this->entityManager->getRepository(Seller::class);
            $seller_name = $existing_seller->findOneBy(['seller' =>$data->getSeller(), 'company' => $company] );
            if($seller_name) {
                $this->addFlash(
                   'danger',
                   'Seller with this name already exist'
                );
                return $this->redirectToRoute('wixmp_seller_add', ['storeHash' => $company->getStoreHash()]);
            } 
            $selected_plan = $form['plan']->getData();
            if (!$selected_plan) {
                return $this->redirectToRoute('wixmp_seller_manage', ['storeHash' => $company->getStoreHash()]);
            }

            $plan_data = $sellerHelper->get_seller_plan(['id' => $selected_plan, 'company' => $company->getId()]);

            $_data = array(
                'company' => $company,
                'current_plan' => $plan_data,
                'status' => 'N',
            );

            $all_params = $request->request->all()['seller_form'];

            if (isset($all_params['allowed_categories'])) {
                $seller->setAllowedCategories($all_params['allowed_categories']);
            }
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

            if ($seller && $seller->getId()) {
                $this->addFlash('success', $this->translate->trans('message.common.record_created_successfully'));
            }

            $SellerEvent = new SellerEvent($companyApplication, $seller);
            $this->dispatcher->dispatch(
                $SellerEvent,
                SellerEvent::WIX_SELLER_ACCOUNT_REGISTER
            );

            return $this->redirectToRoute('wixmp_seller_manage', ['storeHash' => $company->getStoreHash()]);
        }
        
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'seller/seller_update',
            'title' => 'create_seller',
            'seller' => $seller,
            'form' => $form->createView(),
            'seller_plan' => null,
            'company' => $company,
            'mode' => "add",
            'custom_field_list' => $getCustomFields,
        ]);
    }

    /**
     * @Route("/edit/{seller_id}", name="update")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * @ParamConverter("seller", options={"mapping": {"seller_id": "id"}})
     */
    public function update(
        Request $request, Seller $seller, CompanyApplication $companyApplication, 
        SellerHelper $sellerHelper,BaseHelper $baseHelper,
        UserPasswordEncoderInterface $passwordEncoder, UserHelper $userHelper)
    {
        $company = $companyApplication->getCompany();
        $commonHelper = $baseHelper->getHelper('common');
        $mediaHelper = $baseHelper->getHelper('media');
        $seller_plans = $sellerHelper->get_plan_list($company);
        $customFeilds = $seller->getCustomFields();

        $customFieldValueDatas = [];
        if(isset($customFeilds) && !blank($customFeilds)) {
            $customFieldValue = (isset($customFeilds->custom_field_value))? $customFeilds->custom_field_value : '';
            if(isset($customFieldValue) && !blank($customFieldValue)) {
                $customFieldValueDatas = (array) json_decode($customFieldValue);
            }
        }

        if ($seller->getIsArchieved() == 1) {
            $this->addFlash('warning', $this->translate->trans('message.common.cant_edit_archived_seller'));
            return $this->redirectToRoute('wixmp_seller_archieve_manage', [ 'storeHash' => $company->getStoreHash()]);
        }

        $sellerUser = $userHelper->get_user(['seller' => $seller]);

        /** custom field 27-12-2022 */
        $getCustomFields = $sellerHelper->get_wix_subapp_custom_field($companyApplication, $customFieldValueDatas);
        /** end custom filed */
        
        $form = $this->createForm(SellerFormType::class, $seller, ['plan_list' => $seller_plans,'allow_extra_fields' => true, 'custom_field_data'=> $getCustomFields]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($sellerUser == null) {
                $this->addFlash(
                   'danger',
                   $this->translate->trans('message.seller.make_seller_active')
                );

                return $this->redirectToRoute('wixmp_seller_update', ['seller_id' => $seller->getId(), 'storeHash' => $company->getStoreHash()]);
            }
            $all_params = $request->request->all()['seller_form'];
            
            $plain_password = $form['password']->getData();
            $user_name = $form['username']->getData();
            $email = $form['email']->getData();
            
            if (!empty($plain_password)) {
                $password = $passwordEncoder->encodePassword($sellerUser, $plain_password);
                $userHelper->update_user($sellerUser, ['password' => $password]);
            }

            $userData = [
                'username' => $user_name,
                'email' => $email,
            ];

            $userHelper->update_user($sellerUser, $userData);

            // if (!empty($user_name)) {
            //     $userHelper->update_user($sellerUser, ['username' => $user_name]);
            // }

            if (isset($all_params['allowed_categories'])) {
                $seller->setAllowedCategories($all_params['allowed_categories']);
            }
            
            $seller = $sellerHelper->update_seller($seller, []);

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

            if ($seller && $seller->getId()) {
                $this->addFlash('success', $this->translate->trans('message.common.record_updated_successfully'));
            }

            return $this->redirectToRoute('wixmp_seller_update', ['seller_id' => $seller->getId(), 'storeHash' => $company->getStoreHash()]);
        }

        list($seller_plans) = $sellerHelper->get_seller_plans(['company' => $company->getId()]);
     
        $sellerPlans = [];
        foreach ($seller_plans as $sellerPlan) {
            if ($sellerPlan->getConditions()) {
                $planConditions = $sellerPlan->getConditions();
                
                if (isset($planConditions['commission_type']) && $planConditions['commission_type'] == "commission_per_order") {
                    
                    unset($planConditions['category_comission_rate_type']);
                    $sellerPlan->setConditions($planConditions);

                } elseif (isset($planConditions['commission_type']) && $planConditions['commission_type'] == "commission_per_product") {
                    
                    unset($planConditions['category_comission_rate_type']);
                    unset($planConditions['commission']);
                    $sellerPlan->setConditions($planConditions);
                    
                } elseif (isset($planConditions['commission_type']) && $planConditions['commission_type'] == "commission_per_category") {
                    
                    unset($planConditions['commission']);
                    $sellerPlan->setConditions($planConditions);
                }
            }
            $sellerPlans[] = $sellerPlan;
        }

        $sellerSetting  = $sellerHelper->get_seller_settings($companyApplication, $seller);
        
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'seller/seller_update',
            'title' => 'wix_wixmp_update_seller',
            'seller' => $seller,
            'form' => $form->createView(),
            'seller_plans' => $sellerPlans,
            'company' => $company,
            'mode' => "update",
            "sellerUser" => $sellerUser,
            'custom_field_list' => $getCustomFields,
            'custom_fields' => $customFieldValueDatas,
            'seller_setting' => $sellerSetting
        ]);
    }

    /**
     * @Route("/status-change", name="status_change")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function seller_status_change(Request $request, CompanyApplication $companyApplication, UserPasswordEncoderInterface $passwordEncoder, SellerHelper $sellerHelper, BaseHelper $helper, WixMpBaseHelper $wixMpBaseHelper, UserHelper $userHelper)
    {
        $params = $request->query->all();
        //  we have diferent flow for disable: using ajax based : disable product first
        if (isset($params['status_to']) && $params['status_to'] == 'D') {
            $response = $sellerHelper->disable_seller($request, $companyApplication);

            return new JsonResponse($response);
        }

        // Event Dispatched
        $sellerEvent = new SellerEvent($companyApplication, null, null, $request);
        $this->dispatcher->dispatch(
            $sellerEvent,
            SellerEvent::WIX_SELLER_COMPANY_PRE_STATUS_CHANGE
        );
        
        if ($sellerEvent->getActionAllowed() == 'N') {
            return $this->redirectToRoute('wixmp_seller_manage', ['storeHash' => $companyApplication->getCompany()->getStoreHash()]);
        }

        $commonHelper = $helper->getHelper('common');
        $company = $companyApplication->getCompany();

        if (isset($params['entity_id']) && isset($params['status_to']) && !empty($params['entity_id']) && !empty($params['status_to'])) {
            $seller_data = $sellerHelper->get_seller(['id' => $params['entity_id']]);

            $mp_company_helper = $wixMpBaseHelper->getAppHelper('wixmpCompany');
            $store_admin_plan_data = $mp_company_helper->get_company_plan_validated_data($companyApplication);
            
            // $all_seller = $sellerHelper->get_all_sellers(['company' => $companyApplication->getCompany()->getId()]);
            $allow_change_from_new = true;
            
            if ($seller_data) {
                if ($seller_data->getStatus() == 'N') { 
                    if ($params['status_to'] == 'A') {
                        //CREATE USER AND SET COMPANY STATUS TO ACTIVE
                        $user_data = $this->createCompanyRootUser($params, $seller_data, $commonHelper, $userHelper, $passwordEncoder);
                        if (!empty($user_data) && $user_data->getId()) {
                            $SellerEvent = new SellerEvent($companyApplication, $seller_data, $user_data);
                            $this->dispatcher->dispatch(
                                $SellerEvent, 
                                SellerEvent::WIX_SELLER_ADMIN_CREATE
                            );
                            $seller_data->setPassword($user_data->getPlainPassword());
                            $_seller_data = $sellerHelper->update_seller($seller_data, ['status' => $params['status_to']]);
                            
                        } else {
                            $this->addFlash('danger', $this->translate->trans('message.common.unable_to_change_status'));
                        }
                    } else {
                        // change company status
                        $_seller_data = $seller_data;
                        $_seller_data = $sellerHelper->update_seller($seller_data, ['status' => $params['status_to']]);
                    }
                } else {
                    if ($params['status_to'] == 'A') {
                        //check for root user if not create
                        $companyId = $seller_data->getId();
                        $rootSeller = $userHelper->get_user(array('seller' => $seller_data, 'company' => $seller_data->getCompany(), 'isRoot' => 'Y'));
                        if (empty($rootSeller)) {
                            //create root user
                            $user_data = $this->createCompanyRootUser($params, $seller_data, $commonHelper, $userHelper, $passwordEncoder);
                            if (!empty($user_data) && $user_data->getId()) {
                                $SellerEvent = new SellerEvent($companyApplication, $seller_data, $user_data);
                                $this->dispatcher->dispatch(
                                    $SellerEvent,
                                    SellerEvent::WIX_SELLER_ADMIN_CREATE
                                );
                            } else {
                                $this->addFlash('danger', $this->translate->trans('message.common.unable_to_change_status'));
                            }
                        }
                    }
                    //change company status
                    $_seller_data = $sellerHelper->update_seller($seller_data, ['status' => $params['status_to']]);
                }

                if ($_seller_data->getStatus() == $params['status_to']) {
                    $this->addFlash('success', $this->translate->trans('message.common.status_changed_successfully'));
                    //trigger status change event
                    $SellerEvent = new SellerEvent($companyApplication, $_seller_data);
                    $this->dispatcher->dispatch(
                        $SellerEvent,
                        SellerEvent::WIX_SELLER_STATUS_CHANGE
                    );
                } else {
                    $this->addFlash('danger', $this->translate->trans('message.common.unable_to_change_status'));
                }
            }
        }
        if ($request->get('current_url') != null) {
            return $this->redirect(base64_decode($request->get('current_url')));
        }

        return $this->redirectToRoute('wixmp_seller_manage', ['storeHash' => $company->getStoreHash()]);
    }

    /**
     * create seller root user.
     */
    public function createCompanyRootUser($params, $seller_data, $commonHelper, $userHelper, $passwordEncoder)
    {
        if (!empty($seller_data)) {
            $user_name = explode('@', $seller_data->getEmail());
            $user = new SellerUser();
            //$password = $commonHelper->generate_password();
            $password = !empty($seller_data->getPassword()) ? $seller_data->getPassword(): $commonHelper->generate_password();
            $user_params = array(
                'username' => strtolower($user_name[0]).rand(1000, 9999),
                'email' => $seller_data->getEmail(),
                'seller' => $seller_data,
                'company' => $seller_data->getCompany(),
                'isRoot' => 'Y',
                'plain_password' => $password,
                'password' => $passwordEncoder->encodePassword($user, $password),
                'salt' => null,
                'status' => $params['status_to'],
                'firstName' => $seller_data->getSeller(),
                'lastName' => 'seller',
                'phone' => $seller_data->getPhone(),
            );
            $rootSeller = $userHelper->update_user($user, $user_params);
            $rootSeller->setPlainPassword($password); //do not send password in mail : new user

            return $rootSeller;
        }
    }

     /**
     * @Route("/delete/", name="delete")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function delete(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $getUserIds = !empty($request->query->get('seller_ids')) ? $request->query->get('seller_ids') : [];
        $requestUserIds = !empty($request->request->get('seller_ids')) ? $request->request->get('seller_ids') : [];
        $seller_ids = array_merge($getUserIds, $requestUserIds);

        $response = array(
            'code' => 200,
            'notification' => [],
        );

        if (empty($seller_ids)) {
            $response = array(
                'code' => 400,
                'notification' => ['type' => 'error', 'message' => $this->translate->trans('no_data_found')],
            );
        } else {
            list($notifications, $succeed) = $sellerHelper->delete_sellers($seller_ids);
            if (!$succeed) {
                $response['code'] = 400;
            }
            $response['notification'] = $notifications;
        }

        return new JsonResponse($response);
    }


    /**
     * @Route("/buy-plan/{seller_id}/{plan_id}", name="buy_plan")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * @ParamConverter("seller", options={"mapping": {"seller_id": "id"}})
     * @ParamConverter("plan", options={"mapping": {"plan_id": "id"}})
     */
    public function buy_plan(Request $request, Seller $seller, SellerPlan $plan, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $company = $companyApplication->getCompany();

        if ($plan->getCompany()->getId() == $company->getId()) {
            $sellerHelper->update_seller($seller, ['current_plan' => $plan, 'company' => $company]);

            $SellerEvent = new SellerEvent($companyApplication, $seller);
            $this->dispatcher->dispatch(
                $SellerEvent,
                SellerEvent::WIX_SELLER_PLAN_BUY
            );

            $this->addFlash('success', $this->translate->trans('message.common.plan_assigned_successfully'));
        }

        return $this->redirectToRoute('wixmp_seller_update', ['storeHash' => $company->getStoreHash(), 'seller_id' => $seller->getId(), 'selected_section' => 'plan']);
    }

    /**
     * @Route("/filter", name="filter")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * [used to provide dynamic result for filters]
     */
    public function filter(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $params = $request->query->all();
        $params['company'] = $companyApplication->getCompany();
        $returnData = [];
        if (isset($params['filter_name']) && !empty($params['filter_name'])) {
            switch ($params['filter_name']) {
            case 'seller_id':
            case 'seller':

                if (isset($params['q'])) {
                    $params['name'] = $params['q'];
                }
                list($sellers, $params) = $sellerHelper->getSellersAsOption($params);
                $returnData = array(
                    'results' => $sellers->getItems(),
                    'page' => $params['page'],
                    'itemsPerPage' => $params['items_per_page'],
                    'totalCount' => $sellers->getTotalItemCount(),
                );
                break;
            }
        }

        return new JsonResponse($returnData);
    }

    /**
     * @Route("/archive", name="archieve")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function archieve(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {   
        $getUserIds = !empty($request->query->get('seller_ids')) ? $request->query->get('seller_ids') : []; 
        $requestUserIds = !empty($request->request->get('seller_ids')) ? $request->request->get('seller_ids') : [];
        $seller_ids = array_merge($getUserIds, $requestUserIds);
        
        $response = array(
            'code' => 200,
            'notification' => [],
            'totalCount' => 0,
            'items' => 0,
        );

        if (empty($seller_ids)) {
            $response = array(
                'code' => 400,
                'notification' => ['type' => 'error', 'message' => $this->translate->trans('no_data_found')],
            );
        } else {
            list($notifications, $succeed) = $sellerHelper->archieveSellers($request, $companyApplication, $seller_ids);
            if (!$succeed) {
                $response['code'] = 400;
            }
            $response['notification'] = $notifications;
        }
        
        return new JsonResponse($response);
    }

    /**
     * @Route("/unarchieve", name="unarchieve")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function unarchieve(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {   
        $getUserIds = !empty($request->query->get('seller_ids')) ? $request->query->get('seller_ids') : []; 
        $requestUserIds = !empty($request->request->get('seller_ids')) ? $request->request->get('seller_ids') : [];
        $seller_ids = array_merge($getUserIds, $requestUserIds);

        // Event Dispatched
        $sellerEvent = new SellerEvent($companyApplication, null, null, $request);
        $this->dispatcher->dispatch(
            $sellerEvent,
            SellerEvent::WIX_SELLER_UNARCHIVE_STATUS
        );

        if ($sellerEvent->getActionAllowed() == 'N') {
            
            $response = array(
                'code' => 400,
                'notification' => ['type' => 'error', 'message' => $this->translate->trans('no_data_found')],
            );
            
            return new JsonResponse($response);
        }
        
        // return array(
        //     'totalCount' => 0,
        //     'items' => 0,
        //     'notifications' => [],
        // );
        $response = array(
            'code' => 200,
            'notification' => [],
            'totalCount' => 0,
            'items' => 0,
        );

        if (empty($seller_ids)) {
            $response = array(
                'code' => 400,
                'notification' => ['type' => 'error', 'message' => $this->translate->trans('no_data_found')],
            );
        } else {
            list($notifications, $succeed) = $sellerHelper->unArchieveSellers($companyApplication, $seller_ids);
            if (!$succeed) {
                $response['code'] = 400;
            }
            $response['notification'] = $notifications;
        }

        return new JsonResponse($response);
    }
}