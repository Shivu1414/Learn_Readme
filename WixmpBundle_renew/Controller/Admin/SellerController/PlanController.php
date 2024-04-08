<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Admin\SellerController;

use App\Core\BaseController;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Translation\TranslatorInterface;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPlan;
use Webkul\Modules\Wix\WixmpBundle\Form\Plan\SellerPlanFormType;
use Webkul\Modules\Wix\WixmpBundle\Utils\SellerHelper;
use Webkul\Modules\Wix\WixmpBundle\Utils\SalesHelper;

/**
 * @Route("/seller/plan", name="wixmp_seller_plan_")
 * @Security("is_granted('ROLE_WIXMP_ADMIN')")
 */
class PlanController extends BaseController
{
    public function __construct(TranslatorInterface $translator)
    {
        $this->translate = $translator;
    }

    /**
     * @Route("", name="manage")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function manage(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $params = $request->query->All();
        $params['company'] = $company = $companyApplication->getCompany();

        list($plan_list, $search) = $sellerHelper->get_seller_plans($params);

        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'breadcrums_template' => 'common/breadcrums',
            'list_count' => $plan_list->getTotalItemCount(),
            'template_name' => 'seller/plan_manage',
            'title' => 'seller_plans',
            'plan_list' => $plan_list,
            'search' => $search,
        ]);
    }

    /**
     * @Route("/add", name="add")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function create(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $plan = new SellerPlan();
        $company = $companyApplication->getCompany();

        $planApplicationData = $companyApplication->getSubscription()->getPlanApplication()->getFeatures();

        $plan_conditions = $sellerHelper->get_seller_plan_condition();

        $form = $this->createForm(SellerPlanFormType::class, $plan); 

        $sellerPlanForm = $request->request->get('seller_plan_form');

        if (isset($sellerPlanForm['conditions'])) {
            $sellerPlanForm['conditions'] = serialize($sellerPlanForm['conditions']);
            $request->request->set('seller_plan_form', $sellerPlanForm);
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            
            $_data = array(
                'company' => $company,
            );

            $plan = $sellerHelper->update_seller_plan($plan, $_data);

            if ($plan && $plan->getId()) {
                $this->addFlash('success', $this->translate->trans('message.common.record_created_successfully'));
            }

            return $this->redirectToRoute('wixmp_seller_plan_manage', ['storeHash' => $company->getStoreHash()]);
        }

        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'seller/plan_update',
            'title' => 'create_plan',
            'plan' => null,
            'form' => $form->createView(),
            'company' => $company,
            'plan_conditions' => $plan_conditions,
            'plan_application_data' => $planApplicationData,
        ]);
    }

    /**
     * @Route("/status-change", name="status_change")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function plan_status_change(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $params = $request->query->all();
        $company = $companyApplication->getCompany();

        if (isset($params['entity_id']) && isset($params['status_to']) && !empty($params['entity_id']) && !empty($params['status_to'])) {
            $plan_data = $sellerHelper->get_seller_plan(['id' => $params['entity_id']]);

            if ($plan_data) {
                $_plan_data = $sellerHelper->update_seller_plan($plan_data, ['status' => $params['status_to']]);

                if ($_plan_data->getStatus() == $params['status_to']) {
                    $this->addFlash('success', $this->translate->trans('wix_wixmp_status_changed_successfully'));
                } else {
                    $this->addFlash('danger', $this->translate->trans('wix_wixmp_unable_to_change_status'));
                }
            }
        }

        if ($request->get('current_url') != null) {
            return $this->redirect($request->get('current_url'));
        }

        return $this->redirectToRoute('wixmp_seller_plan_manage', ['storeHash' => $company->getStoreHash()]);
    }

    /**
     * @Route("/edit/{id}", name="update")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function update(Request $request, SellerPlan $plan, CompanyApplication $companyApplication, SellerHelper $sellerHelper,SalesHelper $salesHelper)
    {   
        $company = $companyApplication->getCompany();
                
        $plan_conditions = $sellerHelper->get_seller_plan_condition();

        $form = $this->createForm(SellerPlanFormType::class, $plan);

        // Plan Feature
        $planApplicationData = $companyApplication->getSubscription()->getPlanApplication()->getFeatures();
        // End Plan Feature

        $sellerPlanForm = $request->request->get('seller_plan_form');
        if (isset($sellerPlanForm['conditions'])) {

            if (isset($sellerPlanForm['conditions']['commission_type']) && $sellerPlanForm['conditions']['commission_type'] != "commission_per_category") {
                unset($sellerPlanForm['conditions']['category_comission_rate_type']);
            }
            
            $sellerPlanForm['conditions'] = serialize($sellerPlanForm['conditions']);
            $request->request->set('seller_plan_form', $sellerPlanForm);
        }
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $sellerHelper->update_seller_plan($plan, []);

            $this->addFlash('success', $this->translate->trans('message.common.record_updated_successfully'));

            return $this->redirectToRoute('wixmp_seller_plan_update', ['id' => $plan->getId(), 'storeHash' => $company->getStoreHash()]);
        }

       
        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'seller/plan_update',
            'title' => 'update_plan',
            'plan' => $plan,
            'plan_conditions' => $plan_conditions,
            'form' => $form->createView(),
            'company' => $company,
            'plan_application_data' => $planApplicationData,
        ]);
    }

    /**
     * @Route("/delete/{plan_id}", name="delete")
     * @ParamConverter("plan", options={"mapping": {"plan_id": "id"}})
     * @Method("DELETE")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function delete(CompanyApplication $companyApplication, SellerPlan $plan, Request $request, SellerHelper $sellerHelper)
    {
        $response = array(
            'code' => 200,
        );
        $result = $sellerHelper->delete_seller_plan($plan);
        if (isset($result['error']) && !empty($result['error'])) {
            $response['notification'] = ['type' => 'error', 'message' => $result['error']];
            $response['code'] = isset($result['error_code']) ? $result['error_code'] : 405;
        } else {
            $response['notification'] = ['type' => 'success', 'message' => $this->translate->trans('deleted_successfully')];
        }

        return new JsonResponse($response);
    }
}