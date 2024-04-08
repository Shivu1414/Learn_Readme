<?php

namespace Webkul\Modules\Wix\WixmpBundle\EventSubscriber;

use App\Events\PayoutEvent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webkul\Modules\Wix\WixmpBundle\Utils\WixMpBaseHelper;

class PayoutSubscriber extends WixMpBaseHelper implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        
        return array(
            PayoutEvent::PAYOUT_NOTIFY => 'onPayoutNotifyEvent',
        );
    }

    public function onPayoutNotifyEvent(PayoutEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $payoutInfo = $event->getPayoutInfo();
        if (empty($payoutInfo)) {
            return;
        }
        $commissionHelper = $this->getAppHelper('commission');
        $commissionHelper->processWebhook($payoutInfo, $companyApplication);
        
    }

}
