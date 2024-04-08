<?php

namespace Webkul\Modules\Wix\WixmpBundle\EventSubscriber;

use App\Events\CronEvent;
use Webkul\Modules\Wix\WixmpBundle\Events\SellerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webkul\Modules\Wix\WixmpBundle\Utils\WixMpBaseHelper;

class SellerSubscriber extends WixMpBaseHelper implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        // return the subscribed events, their methods and priorities
        return array(
            SellerEvent::WIX_SELLER_COMPANY_PRE_ADD => 'onWixMpSellerCompanyPreAdd',
            SellerEvent::WIX_SELLER_COMPANY_PRE_STATUS_CHANGE => 'onWixSellerCompanyStateChange',
            SellerEvent::WIX_SELLER_UNARCHIVE_STATUS => "onWixSellerUnarchive",
        );
    }

    public function onWixMpSellerCompanyPreAdd(SellerEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        
        $wixmpCompanyHelper = $this->getAppHelper('wixmpCompany');
        $company_validation = $wixmpCompanyHelper->get_company_plan_validated_data($companyApplication);
        if (!$company_validation['allow']['seller']) {
            $admin_plan_link = $this->generateUrl('app_subscription_plan_upgrade', ['app_code' => $companyApplication->getApplication()->getCode(), 'storeHash' => $companyApplication->getCompany()->getStoreHash(), 'id' => $companyApplication->getSubscription()->getId()]);
            $this->add_notification('warning', $this->container->get('translator')->trans('sorry_cannot_add_more_sellers', ['plan_link' => $admin_plan_link]));
            $event->setActionAllowed('N');
        }
    }

    public function onWixSellerCompanyStateChange(SellerEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $request = $event->getRequest();
        $params = $request->query->all();
        if (isset($params['status_to']) && isset($params['status_from'])) {
            if ($params['status_to'] == 'A' && $params['status_from'] == "N") {
                $wixmpCompanyHelper = $this->getAppHelper('wixmpCompany');
                $company_validation = $wixmpCompanyHelper->get_company_plan_validated_data($companyApplication);
                if (!$company_validation['allow']['seller']) {
                    $admin_plan_link = $this->generateUrl('app_subscription_plan_upgrade', ['app_code' => $companyApplication->getApplication()->getCode(), 'storeHash' => $companyApplication->getCompany()->getStoreHash(), 'id' => $companyApplication->getSubscription()->getId()]);
                    $this->add_notification('warning', $this->container->get('translator')->trans('sorry_cannot_add_more_sellers', ['plan_link' => $admin_plan_link]));
                    $event->setActionAllowed('N');
                }
            }
        } else {
            $admin_plan_link = $this->generateUrl('app_subscription_plan_upgrade', ['app_code' => $companyApplication->getApplication()->getCode(), 'storeHash' => $companyApplication->getCompany()->getStoreHash(), 'id' => $companyApplication->getSubscription()->getId()]);
            $this->add_notification('warning', $this->container->get('translator')->trans('sorry_cannot_add_more_sellers', ['plan_link' => $admin_plan_link]));
            $event->setActionAllowed('N');
        }
    }

    public function onWixSellerUnarchive(SellerEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $request = $event->getRequest();

        $wixmpCompanyHelper = $this->getAppHelper('wixmpCompany');
        $company_validation = $wixmpCompanyHelper->getUnarchivedSellerValidCount($companyApplication);
        if (!$company_validation['allow']['seller']) {
           
            $this->add_notification('warning', $this->container->get('translator')->trans('sorry_cannot_unarchive_more_sellers'));
            $event->setActionAllowed('N');
        }
    }
}