<?php

namespace Webkul\Modules\Wix\WixmpBundle\EventSubscriber;

use App\Events\WixWebhookEvent;
use Webkul\Modules\Wix\WixmpBundle\Events\SellerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webkul\Modules\Wix\WixmpBundle\Utils\WixMpBaseHelper;

class OrderSubscriber extends WixMpBaseHelper implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        // return the subscribed events, their methods and priorities
        return array(
            WixWebhookEvent::WIX_WEBHOOK_ORDER_CREATED_EVENT => 'onWixWebhookOrderCreatedEvent',
            WixWebhookEvent::WIX_WEBHOOK_ORDER_UPDATED_EVENT => 'onWixWebhookOrderUpdatedEvent',
            SellerEvent::WIX_SELLER_ORDER_CREATE => 'onWixSellerOrderCreate',
        );
    }

    public function onWixWebhookOrderCreatedEvent(WixWebhookEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }

        $salesHelper = $this->getAppHelper('sales');
        $platformHelper = $this->getAppHelper('platform');
        $common_helper = $this->getHelper('common');
        $platformHelper->init($companyApplication); // Very IMPORTANT
        
        $item_id = isset($event->getData()->orderId) ? $event->getData()->orderId : "";
        $event->temp = $item_id;

        $result = $salesHelper->create_bc_order($item_id, $companyApplication, $event->getData(), $platformHelper);
    }

    public function onWixWebhookOrderUpdatedEvent(WixWebhookEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $salesHelper = $this->getAppHelper('sales');
        $platformHelper = $this->getAppHelper('platform');
        $common_helper = $this->getHelper('common');
        $platformHelper->init($companyApplication); // Very IMPORTANT
   
        // $item_id = isset($event->getData()->entityId) ? $event->getData()->entityId : "";
        $item_id = isset($event->getData()->order) && isset($event->getData()->order->id) ? $event->getData()->order->id : "";
        $event->temp = $item_id;
        $result = $salesHelper->create_bc_order($item_id, $companyApplication, $event->getData(), $platformHelper);

    }

    public function onWixSellerOrderCreate (SellerEvent $event) 
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $seller = $event->getSeller();        
        $order = $event->getOrder();
        
        if (empty($seller) || empty($order)) {
            return;
        }
     
        // processPayout
        $commissionHelper = $this->getAppHelper('commission');
        $commissionHelper->processOrderAutoPay($event);
    }
}