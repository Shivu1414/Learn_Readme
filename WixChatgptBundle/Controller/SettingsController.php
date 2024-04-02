<?php

namespace Webkul\Modules\Wix\WixChatgptBundle\Controller;

use App\Helper\BaseHelper;
use App\Core\BaseController;
use App\Entity\CompanyApplication;
use App\Form\Custom\CustomFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Webkul\Modules\Wix\WixChatgptBundle\Utils\WixChatGPTHelper;

/**
 * @Route("/settings", name="wixchatgptcontent_settings_")
 * @Security("is_granted('ROLE_WIXCHATGPTCONTENT_ADMIN')")
 */
class SettingsController extends BaseController {

    public function __construct(BaseHelper $helper, TranslatorInterface $translator, EventDispatcherInterface $eventDispatcher, EntityManagerInterface $entityManager, WixChatGPTHelper $wixChatGPTHelper ) {
        $this->event_dispatcher = $eventDispatcher;
        $this->translate = $translator;
        $this->entityManager = $entityManager;
        $this->helper = $helper;
        $this->wixChatGPTHelper = $wixChatGPTHelper;
    }
    
    /**
     * @Route("/general", name="general")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     * 
     */
    public function general( Request $request, CompanyApplication $companyApplication ) {

        $requestParams = $request->request->all();
        $company = $companyApplication->getCompany();
        $application = $companyApplication->getApplication();
        $commonHelper = $this->helper->getHelper('common');
        $section = 'wixchatgptcontent_general';
        
        $general_setting_data = $commonHelper->get_section_settings(
            $section, 
            'application', 
            $application->getCode()
        );

        $data = $this->wixChatGPTHelper->getGeneralConfigData($companyApplication);
        $formBindData = [];

        foreach ($data as $fieldName => $fieldValue) {
            if (empty($fieldValue)) {
                $fieldValue = false;
            }
            if ($fieldValue == '1' || $fieldValue == '0') {
                $fieldValue = (bool) $fieldValue;
            }
            $formBindData[$fieldName] = $fieldValue;
        }

        $form = $this->createForm(CustomFormType::class, $formBindData, [
            'setting_data' => array('fields' => $general_setting_data['fields'] , 'value' => $data ),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $form_values = $form->getData();
            foreach ($form_values as $setting_name => $value) {
                if (empty($value)) {
                    $this->helper->add_notification( 'danger' , $this->translate->trans('wixchatgptcontent_general_settings_value_empty'));
                    return $this->redirectToRoute('wixchatgptcontent_settings_general', ['storeHash' => $company->getStoreHash()]);
                }
                $commonHelper->update_section_setting($value, $setting_name, $section, $company, $application);
            }
            $this->helper->add_notification( 'success' , $this->translate->trans('wixchatgptcontent_general_settings_saved_successfully'));
            return $this->redirectToRoute('wixchatgptcontent_settings_general', ['storeHash' => $company->getStoreHash()]);
        }
            
        return $this->render('@wixchatgptcontent_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'settings/general',
            'title' => 'General Setting',
            'form' => $form->createView(),
            'section' => $section,
            'company' => $company,
            'application' => $application,
            'settingValues' => $data
        ]);
    }

    /**
     * @Route("/support/", name="support")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function support(Request $request, CompanyApplication $companyApplication)
    {
        return $this->render('@wixchatgptcontent_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'settings/support',
            'title' => 'settings_support',
        ]);
    }

}