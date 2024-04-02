<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\EventSubscriber;

use App\Events\WixWebhookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webkul\Modules\Wix\WixEtsyAppBundle\Utils\HelperClass;
use Doctrine\DBAL\DBALException;

class WebhookSubscriber extends HelperClass implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            WixWebhookEvent::WIX_WEBHOOK_INVENTORY_VARIANTS_CHANGED => 'onWixWebhookInventoryVariantsChanged',
            WixWebhookEvent::WIX_WEBHOOK_PRODUCT_CREATED_EVENT => 'onWixWebhookProductCreate',
            WixWebhookEvent::WIX_WEBHOOK_PRODUCT_UPDATED_EVENT => 'onWixWebhookProductUpdate',
            WixWebhookEvent::WIX_WEBHOOK_PRODUCT_DELETED_EVENT => 'onWixWebhookProductDelete',
            WixWebhookEvent::WIX_WEBHOOK_COLLECTION_CREATED_EVENT => 'onWixWebhookCollectionCreate',
            WixWebhookEvent::WIX_WEBHOOK_COLLECTION_UPDATED_EVENT => 'onWixWebhookCollectionUpdate',
            WixWebhookEvent::WIX_WEBHOOK_COLLECTION_DELETED_EVENT =>  'onWixWebhookCollectionDelete',
        );
    }

    public function onWixWebhookProductUpdate(WixWebhookEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixetsy' || empty($companyApplication->getSubscription()) || $companyApplication->getSubscription()->getStatus() != 'A') {
            return;
        }
        
        $catalogHelper = $this->getAppHelper("catalog");
        $catalogHelper->onWebhookProductUpdate($companyApplication, $event->getData());
    }

    public function onWixWebhookProductCreate(WixWebhookEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixetsy' || empty($companyApplication->getSubscription()) || $companyApplication->getSubscription()->getStatus() != 'A') {
            return;
        }
        
        $catalogHelper = $this->getAppHelper("catalog");
        $catalogHelper->onWebhookProductAdd($companyApplication, $event->getData());
    }

    public function onWixWebhookInventoryVariantsChanged(WixWebhookEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixetsy' || empty($companyApplication->getSubscription()) || $companyApplication->getSubscription()->getStatus() != 'A') {
            return;
        }
        
        $platformHelper = $this->getAppHelper("platform");
        $platformHelper->updateEtsyInventory($companyApplication, $event->getData());
    }

    public function onWixWebhookProductDelete(WixWebhookEvent $event)
    {   
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixetsy') {
            return;
        }
        $catalogHelper = $this->getAppHelper('catalog');
        $product_id = isset($event->getData()->productId) ? $event->getData()->productId : ""; 
        $catalogHelper->onWebhookProductDelete($product_id, $event->getCompanyApplication());
    }

    public function onWixWebhookCollectionCreate(WixWebhookEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixetsy' || empty($companyApplication->getSubscription()) || $companyApplication->getSubscription()->getStatus() != 'A') {
            return;
        }
        
        $catalogHelper = $this->getAppHelper("catalog");
        $catalogHelper->onWebhookCollectionAdd($companyApplication, $event->getData());
    }

    public function onWixWebhookCollectionUpdate(WixWebhookEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixetsy' || empty($companyApplication->getSubscription()) || $companyApplication->getSubscription()->getStatus() != 'A') {
            return;
        }
        
        $catalogHelper = $this->getAppHelper("catalog");
        $catalogHelper->onWebhookCollectionUpdate($companyApplication, $event->getData());
    }

    public function onWixWebhookCollectionDelete(WixWebhookEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixetsy' || empty($companyApplication->getSubscription()) || $companyApplication->getSubscription()->getStatus() != 'A') {
            return;
        }
        
        $catalogHelper = $this->getAppHelper("catalog");
        $catalogHelper->onWebhookCollectionDelete($companyApplication, $event->getData());
    }
}