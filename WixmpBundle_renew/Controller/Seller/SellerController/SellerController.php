<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Seller\SellerController;

use App\Helper\BaseHelper;
use App\Core\BaseController;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Webkul\Modules\Wix\WixmpBundle\Entity\Seller;
use Symfony\Component\Translation\TranslatorInterface;
use Webkul\Modules\Wix\WixmpBundle\Utils\UserHelper;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPlan;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerUser;
use Webkul\Modules\Wix\WixmpBundle\Events\SellerEvent;
use Webkul\Modules\Wix\WixmpBundle\Utils\SellerHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Webkul\Modules\Wix\WixmpBundle\Form\Seller\SellerFormType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Webkul\Modules\Wix\WixmpBundle\Utils\PlatformHelper;
use Webkul\Modules\Wix\WixmpBundle\Utils\CatalogHelper;
use App\Entity\Media;
use App\Entity\MediaMap;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Webkul\Modules\Wix\WixmpBundle\Form\Seller\BatchActionType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @Route("/seller", name="wixmp_seller_seller_")
 * @Security("is_granted('ROLE_WIXMP_SELLER')")
 */
class SellerController extends BaseController
{
    function __construct(TranslatorInterface $translator, EventDispatcherInterface $dispatcher, ContainerInterface $container, KernelInterface $kernel, EntityManagerInterface $entityManager) {
        $this->translate = $translator;
        $this->dispatcher = $dispatcher;
        $this->container = $container;
        $this->projectDir = $kernel->getProjectDir();
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("", name="manage")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function manage(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $params = $request->query->All();
        $params['company'] = $company = $companyApplication->getCompany();
        $params['seller'] = $seller = $this->getUser()->getSeller();

        $form = $this->createForm(BatchActionType::class);
        $form->handleRequest($request);

        list($seller_list, $search) = $sellerHelper->get_sellers($params);

        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'seller/seller_manage',
            'title' => 'seller',
            'seller_list' => $seller_list,
            'list_count' => $seller_list->getTotalItemCount(),
            'search' => $search,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit/{seller_id}", name="update")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * @ParamConverter("seller", options={"mapping": {"seller_id": "id"}})
     */
    public function update(Request $request,Seller $seller, CompanyApplication $companyApplication, SellerHelper $sellerHelper,PlatformHelper $PlatformHelper,BaseHelper $baseHelper,CatalogHelper $catalogHelper, UserHelper $userHelper)
    {  
        $company = $companyApplication->getCompany();
        $commonHelper = $baseHelper->getHelper('common');
        $customFeilds = $seller->getCustomFields();

        $customFieldValueDatas = [];
        if(isset($customFeilds) && !blank($customFeilds)) {
            $customFieldValue = (isset($customFeilds->custom_field_value))? $customFeilds->custom_field_value : '';
            if(isset($customFieldValue) && !blank($customFieldValue)) {
                $customFieldValueDatas = (array) json_decode($customFieldValue);
            }
        }

        list($seller_plans,) = $sellerHelper->get_seller_plans(['company' => $company]);

        if($this->getUser()->getSeller()->getId() != $seller->getId()) {
            return $this->redirectToRoute('mp_seller_seller_manage',['storeHash' => $company->getStoreHash()]);
        }

        $sellerUser = $userHelper->get_user(['seller' => $seller]);

        /** custom field 27-12-2022 */
        $getCustomFields = $sellerHelper->get_wix_subapp_custom_field($companyApplication, $customFieldValueDatas);
        /** end custom filed */

        $form = $this->createForm(SellerFormType::class, $seller, ['plan_list' => $seller_plans,'allow_extra_fields' => true, 'custom_field_data'=> $getCustomFields]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $seller = $sellerHelper->update_seller($seller, []);
            $customFieldValues = $request->request->get('custom_form');

            if (!empty($seller->getEmail())) {
                $userHelper->update_user($sellerUser, ['email' => $seller->getEmail()]);
            }

            if ($seller && $seller->getId()) {
                $this->addFlash('success', $this->translate->trans('message.common.record_updated_successfully'));
            }

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
                SellerEvent::WIX_SELLER_COMPANY_UPDATE
            );

            return $this->redirectToRoute('wixmp_seller_seller_update',['seller_id' => $seller->getId(),'storeHash' => $company->getStoreHash()]);
        }

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
                    
                }  elseif (isset($planConditions['commission_type']) && $planConditions['commission_type'] == "commission_per_category") {
                    
                    unset($planConditions['commission']);
                    $sellerPlan->setConditions($planConditions);
                }
            }
            $sellerPlans[] = $sellerPlan;
        }

        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'seller/seller_update',
            'title' => 'update_seller',
            'seller' => $seller,
            'form' => $form->createView(),
            'seller_plans' => $sellerPlans,
            'company'=>$company,
            'custom_field_list' => $getCustomFields,
            'custom_fields' => $customFieldValueDatas,
        ]);
    }

    /**
     * @Route("/buy-plan/{seller_id}/{plan_id}", name="buy_plan")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * @ParamConverter("seller", options={"mapping": {"seller_id": "id"}})
     * @ParamConverter("plan", options={"mapping": {"plan_id": "id"}})
     */
    public function buy_plan(Request $request,Seller $seller, SellerPlan $plan, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $company = $companyApplication->getCompany();
        $user_seller = $this->getUser()->getSeller();

        if($this->getUser()->getIsRoot() == 'Y' && $user_seller->getId() == $seller->getId() && $plan->getCompany()->getId() == $company->getId()) {
            $sellerHelper->update_seller($seller,['current_plan' => $plan,'company' => $company]);

            $SellerEvent = new SellerEvent($companyApplication, $seller);
            $this->dispatcher->dispatch(
                $SellerEvent, SellerEvent::WIX_SELLER_PLAN_BUY
            );

            $this->addFlash('success', $this->translate->trans("message.common.plan_assigned_successfully"));
        }

        return $this->redirectToRoute('wixmp_seller_seller_update',['storeHash' => $company->getStoreHash(),'seller_id' => $seller->getId(),'selected_section' => 'plan']);
    }
}