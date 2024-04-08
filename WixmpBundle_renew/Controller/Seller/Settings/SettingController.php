<?php

namespace Webkul\Modules\Wix\WixmpBundle\Controller\Seller\Settings;

use Webkul\Modules\Wix\WixmpBundle\Entity\Setting;
use App\Helper\BaseHelper;
use App\Core\BaseController;
use App\Entity\CompanyApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Webkul\Modules\Wix\WixmpBundle\Utils\SellerHelper;
use App\Helper\MediaHelper;
use Webkul\Modules\Wix\WixmpBundle\Form\Seller\SettingFormType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/setting", name="wixmp_seller_settings_")
 * @Security("is_granted('ROLE_WIXMP_SELLER')")
 */
class SettingController extends BaseController
{
    /**
     * @Route("/", name="index")
     * @Route("/general", name="general")
     * @ParamConverter("companyApplication", class="App\Entity\CompanyApplication", converter="company_application_converter")
     */
    public function general(Request $request, CompanyApplication $companyApplication, BaseHelper $helper, SellerHelper $sellerHelper, MediaHelper $mediaHelper)
    {   
        $company = $companyApplication->getCompany();
        $seller = $this->getUser()->getSeller();
        $setting = $sellerHelper->get_seller_settings($companyApplication, $seller);
        // Stripe Config
        $stripeHelper = $helper->getHelper('StripeHelper');
        $stripeConfig = $sellerHelper->seller_stripe_config($company, $companyApplication->getApplication(), $helper);
        
        if (empty($setting)) {
            $setting = new Setting();
        }
        
        $form = $this->createForm(SettingFormType::class, $setting, []);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $params = $form->getData();
            $request_data = $request->request->get('setting_from');
            
            // set required fields
            $setting->setArea('wixmp-seller');
            $setting->setCompanyApplication($companyApplication);
            $setting->setSeller($seller);
            if ($form->get('urlGenerate')->isClicked() && $params->getstripePayoutEmail() != "" && $stripeConfig && empty($setting->getstripePayoutAccount())) {
                $config = [
                    'api_secret_key' => $stripeConfig['stripe_payout_secret_key']->getValue()
                ];
                $accountOpen = $stripeHelper->connectAccountStripe($config,$params);
                if ( isset($accountOpen) && $accountOpen['code'] == 200) {
                    $setting->setStripePayoutAccount($accountOpen['data']['aacountId']);
                } else {
                    $this->addFlash('danger',"Kyc Url Not Generate Please Contact Admin!");
                }
            }
            
            if ($form->get('urlGenerate')->isClicked() && !empty($setting->getstripePayoutAccount()) && $stripeConfig ) {
                $config = [
                    'api_secret_key' => $stripeConfig['stripe_payout_secret_key']->getValue()
                ];
                $params = [
                    'accountId' => $setting->getstripePayoutAccount(),
                    'refresh_url' => 'http://'.$request->server->get('HTTP_HOST').$request->getpathInfo(),
                    'return_url' => 'http://'.$request->server->get('HTTP_HOST').$request->getpathInfo(),
                ];
                $accountOpenLink = $stripeHelper->accountLinkStripe($config,$params);
                if ($accountOpenLink['code'] != 200 ) {
                    $this->addFlash('danger',"Kyc Url Not Generate Please Contact Admin!");
                }
            }
            $this->addFlash(
               'success',
               'Saved Successfully'
            );
            $setting = $sellerHelper->update_seller_settings($setting, $request_data, $helper);
            // return $this->redirectToRoute('wixmp_seller_settings_general', ['storeHash' => $company->getStoreHash(),'accountLink' => $accountLink]);
        }

        return $this->render('@wixmp_twig/view_templates/index.html.twig', [
            'header' => true,
            'menu' => true,
            'breadcrums' => true,
            'template_name' => 'seller/settings_manage',
            'title' => 'settings',
            'form' => $form->createView(),
            'accountLink' => isset($accountOpenLink) ? $accountOpenLink : false,
        ]);
    }
}