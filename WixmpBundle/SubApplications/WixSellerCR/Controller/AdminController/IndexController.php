<?php

namespace Webkul\Modules\Wix\WixmpBundle\SubApplications\WixSellerCR\Controller\AdminController;

use App\Core\BaseController;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Webkul\Modules\Wix\WixmpBundle\Entity\CustomFeilds;
use Webkul\Modules\Wix\WixmpBundle\Utils\SellerHelper;
use Webkul\Modules\Wix\WixmpBundle\Form\Seller\CustomFieldFormType;
use Doctrine\ORM\EntityManagerInterface;
use Respect\Validation\Rules\Lowercase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/", name="wixsellercr_")
 * @Security("has_role('ROLE_WIXMP_ADMIN')")
 */
class IndexController extends BaseController
{
    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->translate = $translator;
    }
    /**
     * @Route("/", name="index")
     * @Route("/dashboard", name="dashboard")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function sellerCRIndex(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper): Response
    {
        $params = $request->query->All();
        $params['company_application'] = $companyApplication->getId();

        list($wix_custom_field_list, $params) = $sellerHelper->wix_get_custom_fields($params);

        return $this->render('@wixsellercr_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'custom/custom_field',
            'title' => 'wix_seller_cr',
            'custom_field_list' => $wix_custom_field_list
        ]);
    }

    /**
     * @Route("/customfield/create", name="customfield_create")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function create(Request $request, SellerHelper $sellerHelper, CompanyApplication $companyApplication)
    {
        $customForm = new CustomFeilds();

        $company = $companyApplication->getCompany();
        $form = $this->createForm(CustomFieldFormType::class, $customForm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $requestData = $request->request->all()['custom_field_form'];
            $requests = $request->request->all();
            $optionsData = [];
            if(isset($requests['options']) && !empty($requests['options'])) {
                $optionsData = $requests['options'];
            }
            
            if(isset($optionsData) && !empty($optionsData)) {
                $option_datas = implode(',', $optionsData);
                $customForm->setOptions($option_datas);
            }

            if(isset($requestData['label']) && !empty($requestData['label'])) {
                $fieldName  = trim($requestData['label']);
                $fieldName = strtolower(str_replace(' ', '_', $requestData['label']));
                $fieldName = preg_replace('/_+/', '_', $fieldName);
                $customForm->setFeildName($fieldName); 
            }

            $_data = array(
                'company_application' => $companyApplication,
            );

            $params = [
                'company_application' => $companyApplication,
                'field_name' => $customForm->getFeildName(),
            ];

            $fieldNameCheck = $sellerHelper->wix_check_custom_filed_name($params);

            if($fieldNameCheck && !blank($fieldNameCheck)) {
                $this->addFlash('danger', $this->translate->trans('message.common.field_name_already_exist'));
            } else {
                $addCustomField = $sellerHelper->wix_update_custom_filed($customForm, $_data);

                if ($addCustomField && $addCustomField->getId()) {
                    $this->addFlash('success', $this->translate->trans('message.common.custom_field_created_successfully'));
                }
            }

            return $this->redirectToRoute('wixsellercr_index', ['storeHash' => $company->getStoreHash()]);
        }

        return $this->render('@wixsellercr_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'custom/custom_field_update',
            'title' => 'wix_custom_field_create',
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/customfield/update/{custom_field_id}", name="customfield_update")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * @ParamConverter("customFields", options={"mapping": {"custom_field_id": "id"}})
     */
    public function update(Request $request, CustomFeilds $customFields, SellerHelper $sellerHelper, CompanyApplication $companyApplication)
    {
        $form = $this->createForm(CustomFieldFormType::class, $customFields);
        $company = $companyApplication->getCompany();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $requestData = $request->request->all()['custom_field_form'];
            $requests = $request->request->all();
            $optionsData = [];
            if(isset($requests['options']) && !empty($requests['options'])) {
                $optionsData = $requests['options'];
            }
            
            if(isset($optionsData) && !empty($optionsData)) {
                $option_datas = implode(',', $optionsData);
                $customFields->setOptions($option_datas);
            }

            if(isset($requestData['label']) && !empty($requestData['label'])) {
                $fieldName  = trim($requestData['label']);
                $fieldName = strtolower(str_replace(' ', '_', $requestData['label']));
                $fieldName = preg_replace('/_+/', '_', $fieldName);
                $customFields->setFeildName($fieldName); 
            }
            
            $params = [
                'company_application' => $companyApplication,
                'field_name' => $fieldName,
                'id' => $customFields->getId(),
            ];

            $fieldNameCheck = $sellerHelper->wix_check_custom_filed_name($params);
            
            if($fieldNameCheck && !blank($fieldNameCheck)) {
                $this->addFlash('danger', $this->translate->trans('message.common.field_name_already_exist'));
            } else {
                $updateCustomField = $sellerHelper->wix_update_custom_filed($customFields);

                if ($updateCustomField && $updateCustomField->getId()) {
                    $this->addFlash('success', $this->translate->trans('message.common.custom_field_updated_successfully'));
                }
            }
        
            return $this->redirectToRoute('wixsellercr_index', ['storeHash' => $company->getStoreHash()]);
        }

        return $this->render('@sellercr_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'custom/custom_field_update',
            'title' => 'wix_custom_field_update',
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/customfield/status-change", name="customfield_update_status")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function status_change(Request $request, CompanyApplication $companyApplication, SellerHelper $sellerHelper)
    {
        $params = $request->query->all();
        $company = $companyApplication->getCompany();
        $response = ['status' => 403, 'message' => null];

        if (isset($params['entity_id']) && isset($params['to_status']) && !empty($params['entity_id']) && !empty($params['to_status'])) {
            $field_data = $sellerHelper->wix_get_custom_field(['id' => $params['entity_id']]);

            if ($field_data) {
                $_field_data = $sellerHelper->wix_update_custom_filed($field_data, ['status' => $params['to_status']]);
                
                if ($_field_data->getStatus() == $params['to_status']) {
                    $response['status'] = 200;
                }
            }
        }
        return new JsonResponse($response);
    }

    /**
     * @Route("/customfield/delete/{custom_field_id}", name="customfield_delete")
     * @ParamConverter("customFields", options={"mapping": {"custom_field_id": "id"}})
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function delete(Request $request, CustomFeilds $customFields)
    {
        $response = array(
            'code' => 200,
            'notification' => []
        );
        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($customFields);
            $em->flush();
            $response['notification'][] = ['type' => 'success', 'message' => $this->translate->trans('deleted_successfully')];
        } catch (DBALException $e) {
            $sql_error_code = $e->getPrevious()->getCode();
            $response['code'] = 405;
            if ($sql_error_code == '23000') {
                $response['notification'][] = ['type' => 'error', 'message' => $this->translate->trans('cannot_delete_this_item_already_in_use')];
            } else {
                $response['notification'][] = ['type' => 'error', 'message' => $this->translate->trans('cannot_delete_this_item')];
            }
        }

        return new JsonResponse($response);
    } 
}
