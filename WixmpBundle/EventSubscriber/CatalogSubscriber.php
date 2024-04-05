<?php

namespace Webkul\Modules\Wix\WixmpBundle\EventSubscriber;

use App\Events\CronEvent;
use App\Events\WixWebhookEvent;
use Webkul\Modules\Wix\WixmpBundle\Events\CatalogEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webkul\Modules\Wix\WixmpBundle\Utils\WixMpBaseHelper;
use Webkul\Modules\Wix\WixmpBundle\Entity\Products;
use Doctrine\DBAL\DBALException;

class CatalogSubscriber extends WixMpBaseHelper implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        // return the subscribed events, their methods and priorities
        return array(
            //CatalogEvent::WIX_CATALOG_PRODUCT_VISIBILITY_CHANGE => 'onProductVisibilityChange',
            //CatalogEvent::WIX_CATALOG_PRODUCT_SELLER_ASSIGN => 'onProductSellerAssign',
            CatalogEvent::WIX_CATALOG_PRODUCT_ADD => 'onProductWixAdminAdd',
            CatalogEvent::CATALOG_PRODUCT_WIX_SELLER_ADD => 'onProductWixSellerAdd',
            WixWebhookEvent::WIX_WEBHOOK_PRODUCT_CREATED_EVENT => 'onWixWebhookProductCreatedEvent',
            WixWebhookEvent::WIX_WEBHOOK_PRODUCT_UPDATED_EVENT => 'onWixWebhookProductUpdatedEvent',
            WixWebhookEvent::WIX_WEBHOOK_PRODUCT_DELETED_EVENT => 'onWixWebhookProductDeletedEvent',
            //CronEvent::CRON_HIT_EVENT => 'OnCronHitEvent',
        );
    }

    public function onWixWebhookProductUpdatedEvent(WixWebhookEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }

        $catalogHelper = $this->getAppHelper('catalog');
        $common_helper = $this->getHelper('common');

        //$catalogHelper->init_bc_app($event->getCompanyApplication()); // Very IMPORTANT
        $product_id = isset($event->getData()->productId) ? $event->getData()->productId : "";
        $catalogHelper->update_wix_product($product_id, $event->getCompanyApplication());

    }

    public function onWixWebhookProductCreatedEvent(WixWebhookEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $marketplaceCompanyHelper = $this->getAppHelper('wixmpCompany');
        $company_validation = $marketplaceCompanyHelper->get_company_plan_validated_data($companyApplication);
        if (!$company_validation['allow']['product']) {
            return false;
        }
        $catalogHelper = $this->getAppHelper('catalog');
        $common_helper = $this->getHelper('common');
        //$platformHelper = $this->getAppHelper('platform');
        //$platformHelper->init($event->getCompanyApplication()); // Very IMPORTANT
        
        $product_id = isset($event->getData()->productId) ? $event->getData()->productId : "";
        //sleep(5); // In case order created by seller : We create order in BC and MP but at the same time webhook trigger due to which duplicate product gets created.
        // another workaround is to set product metafield in BC product ex: is_mp_product = 1 , seller_id = something.

        $catalogHelper->add_bc_product($product_id, $event->getCompanyApplication());
    }

    public function onProductWixSellerAdd(CatalogEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }

        $marketplaceCompanyHelper = $this->getAppHelper('WixMpCompanyHelper');
        $company_validation = $marketplaceCompanyHelper->get_company_plan_validated_data($companyApplication);
        if (!$company_validation['allow']['product']) {
            $admin_plan_link = $this->generateUrl('app_subscription_plan_upgrade', ['app_code' => $companyApplication->getApplication()->getCode(), 'storeHash' => $companyApplication->getCompany()->getStoreHash(), 'id' => $companyApplication->getSubscription()->getId()]);
            $this->add_notification('warning', $this->container->get('translator')->trans('sorry_admin_cannot_add_more_products', ['plan_link' => $admin_plan_link]));
            $event->setActionAllowed('N');

            return true;
        }

        $companyApplication = $event->getCompanyApplication();
        $token_storage = $this->container->get('security.token_storage');
        $seller = $token_storage->getToken()->getUser()->getSeller();
        $sellerHelper = $this->getAppHelper('seller');
        $seller_plan_validation = $sellerHelper->get_seller_plan_validated_data($seller);
        if (!$seller_plan_validation['allow']['product']) {
            $admin_plan_link = $this->generateUrl('app_subscription_plan_upgrade', ['app_code' => $companyApplication->getApplication()->getCode(), 'storeHash' => $companyApplication->getCompany()->getStoreHash(), 'id' => $companyApplication->getSubscription()->getId()]);
            $this->add_notification('warning', $this->container->get('translator')->trans('sorry_seller_cannot_add_more_products', ['plan_link' => $admin_plan_link]));
            $event->setActionAllowed('N');
        }
    }

    public function onProductWixAdminAdd(CatalogEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $marketplaceCompanyHelper = $this->getAppHelper('WixMpCompanyHelper');
        $company_validation = $marketplaceCompanyHelper->get_company_plan_validated_data($companyApplication);
        if (!$company_validation['allow']['product']) {
            $admin_plan_link = $this->generateUrl('app_subscription_plan_upgrade', ['app_code' => $companyApplication->getApplication()->getCode(), 'storeHash' => $companyApplication->getCompany()->getStoreHash(), 'id' => $companyApplication->getSubscription()->getId()]);
            $this->add_notification('warning', $this->container->get('translator')->trans('sorry_cannot_add_more_products', ['plan_link' => $admin_plan_link]));

            $event->setActionAllowed('N');
            // $admin_plan_link = $this->generateUrl('app_subscription_plan_choose', [ 'storeHash' => $storeHash]);
            // throw new \Exception($this->container->get('translator')->trans('sorry_cannot_add_more_products', ['plan_link' => $admin_plan_link]), 10);
        }
    }

    public function onProductVisibilityChange(CatalogEvent $catalogEvent)
    {
        $companyApplication = $catalogEvent->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'marketplace') {
            return;
        }
        $product = $catalogEvent->getProduct();
        $company = $catalogEvent->getCompanyApplication()->getCompany();

        $company_seller = $catalogEvent->getSeller();
        $receiver_mail = '';
        $receiver_name = '';
        if ($company_seller != null) {
            $receiver_mail = $company_seller->getEmail();
            $receiver_name = $company_seller->getSeller();
        } else {
            $receiver_mail = $company->getEmail();
            $receiver_name = $company->getName();
        }
        if (!empty($receiver_mail)) {
            // $this->add_notification('success', 'send mail');
            // $emailHelper = $this->getEmailHelper();
            // // Send Mail to Seller Company : Product Status Changed
            // $status = $this->getStatusLabel($product->getStatus());
            // $emailHelper->send_mail($company->getCompany() . ': Product Status Change', 'store_admin_mail', $receiver_mail, $this->renderView(
            //     'common/emails/body.html.twig',
            //     array(
            //         'name' => $receiver_name,
            //         'body_text' => '<i>'.$product->getName() .'</i> status has changed to <b>' . $status . '</b>',
            //     )
            // ), 'text/html');
        }
    }

    public function onProductSellerAssign(CatalogEvent $catalogEvent)
    {
        $company = $catalogEvent->getCompany();
        $company_seller = $catalogEvent->getSeller();
        $receiver_mail = '';
        $receiver_name = '';
        if ($company_seller != null) {
            $receiver_mail = $company_seller->getEmail();
            $receiver_name = $company_seller->getSeller();
            $message = $catalogEvent->getBodyText();
            if (!empty($receiver_mail)) {
                // $this->add_notification('success', 'send mail');

                // $emailHelper = $this->getEmailHelper();
                // Send Mail to Seller Company : Product Status Changed
                // $emailHelper->send_mail($company->getCompany() . ': Seller Assigned', 'store_admin_mail', $receiver_mail, $this->renderView(
            //     'common/emails/body.html.twig',
            //     array(
            //         'name' => $receiver_name,
            //         'body_text' => $message,
            //     )
            // ), 'text/html');
            }
        }
    }

    public function OnCronHitEvent(CronEvent $event)
    {
        $catalogHelper = $this->getAppHelper('CatalogHelper', 'marketplace');
        // get all products need to be published
        list($products) = $catalogHelper->getAllProductsToPublish();
        if (!empty($products)) {
            $companyApplicationHelper = $this->getHelper('CompanyApplicationHelper');
            $product_repo = $this->entityManager->getRepository(Products::class);
            $isUpdated = false;
            foreach ($products as $product) {

                $availableOn = $product->getAvailableOn();
                $availableOnDate = strtotime(date('Y-m-d',$availableOn));

                if ($availableOnDate == strtotime(date('Y-m-d'))) {
                    
                    $data = [];
                    // get company application for this product
                    $companyApplication = $companyApplicationHelper->getCompanyApplication('marketplace', $product->getCompany()->getStoreHash());
                    if (!empty($companyApplication)) {
                        try {
                            $bigCommerceAPIV3 = new BigCommerceV3($companyApplication);
                            // update status
                            $data['is_visible'] = true;
                            $response = $bigCommerceAPIV3->updateProduct($product->getProdId(), $data);
                            if ($response['status'] == 200) {
                                $product->setStatus('A');
                                $this->entityManager->persist($product);
                                $isUpdated = true;
                            }
                        } catch (DBALException $e) {
                        } catch (\Exception $e) {
                        }
                    }
                }
            }
            if ($isUpdated) {
                $this->entityManager->flush();
            }
        }
    }

    public function onWixWebhookProductDeletedEvent(WixWebhookEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $catalogHelper = $this->getAppHelper('catalog');
        $product_id = isset($event->getData()->productId) ? $event->getData()->productId : "";
        $catalogHelper->soft_delete_product($product_id, $event->getCompanyApplication());
    }
}
