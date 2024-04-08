<?php

namespace Webkul\Modules\Wix\WixmpBundle\EventSubscriber;

use Webkul\Modules\Wix\WixmpBundle\Events\SellerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webkul\Modules\Wix\WixmpBundle\Utils\WixMpBaseHelper;

class EmailSubscriber extends WixMpBaseHelper implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array (
            SellerEvent::WIX_SELLER_ACCOUNT_REGISTER => 'wixSellerRegisterMail',
            SellerEvent::WIX_SELLER_ADMIN_CREATE => 'wixSellerAdminDetailsMail',
            SellerEvent::WIX_SELLER_STATUS_CHANGE => 'wixSellerStatusChangeMail',
            SellerEvent::WIX_SELLER_COMPANY_UPDATE => 'wixSellerProfileMail',
            SellerEvent::WIX_SELLER_ORDER_CREATE => 'wixSellerOrderCreateMail',
            SellerEvent::WIX_SELLER_ORDER_STATUS_CHANGE => 'wixSellerOrderStatusChangeMail',
            SellerEvent::WIX_SELLER_ACCOUNT_PAYOUT_STATUS_CHANGE => 'wixSellerPayoutStatusChangeMail',
            SellerEvent::WIX_SELLER_ACCOUNT_PAYOUT_CREATE => 'wixSellerPayoutCreateMail',
            SellerEvent::WIX_SELLER_PLAN_BUY => 'wixSellerPlanChangeMail',
            SellerEvent::WIX_SELLER_ACCOUNT_WITHDRAWAL_REQUEST => 'wixSellerWithdrawRequestMail',
            SellerEvent::WIX_SELLER_FORGOT_PASSWORD => 'wixSellerAdminDetailsMail',
        );
    }

    public function wixSellerRegisterMail(SellerEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $seller = $event->getSeller();
        if (empty($seller)) {
            return;
        }
        $company = $companyApplication->getCompany();

        $email_helper = $this->getHelper('email');
        $email_helper->send_mail(
            $company->getEmail(),
            [],
            'seller/registration_to_admin',
            'application',
            array(
                'company' => $company,
                'seller' => $seller,
                'application' => $companyApplication->getApplication(),
            )
        );

        $email_helper->send_mail(
            $seller->getEmail(),
            [],
            'seller/registration_to_seller',
            'application',
            array(
                'company' => $company,
                'seller' => $seller,
                'application' => $companyApplication->getApplication(),
            )
        );
    }

    public function wixSellerAdminDetailsMail(SellerEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $seller = $event->getSeller();
        $company = $companyApplication->getCompany();
        $user = $event->getUser();
        if (empty($seller) || empty($user)) {
            return;
        }

        $commonHelper = $this->getHelper('common');
        $data = $commonHelper->get_section_setting(['sectionName' => 'domain', 'company' => $company, 'application' => $companyApplication->getApplication()], true);
        $domain = ""; # $_SERVER['APP_DOMAIN'];
        if(!empty($data) && !is_null($data['domain_mapping']->getValue())){
            $domain  = "https://".$data['domain_mapping']->getValue();
        }

        $email_helper = $this->getHelper('email');
        $email_helper->send_mail(
            $user->getEmail(),
            [],
            'user/user',
            'application',
            array(
                'company' => $company,
                'seller' => $seller,
                'user' => $user,
                'application' => $companyApplication->getApplication(),
                'domain' => $domain
            )
        );
    }

    public function wixSellerStatusChangeMail(SellerEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $seller = $event->getSeller();
        if (empty($seller)) {
            return;
        }
        $company = $companyApplication->getCompany();

        $email_helper = $this->getHelper('email');
        $email_helper->send_mail(
            $seller->getEmail(),
            [],
            'seller/status_change',
            'application',
            array(
                'company' => $company,
                'seller' => $seller,
                'application' => $companyApplication->getApplication(),
            )
        );
    }

    public function wixSellerProfileMail(SellerEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $seller = $event->getSeller();
        if (empty($seller)) {
            return;
        }
        $company = $companyApplication->getCompany();

        $email_helper = $this->getHelper('email');
        $email_helper->send_mail(
            $seller->getEmail(),
            [],
            'seller/seller',
            'application',
            array(
                'company' => $company,
                'seller' => $seller,
                'application' => $companyApplication->getApplication(),
            )
        );
    }

    public function wixSellerOrderCreateMail(SellerEvent $event)
    {   
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $seller = $event->getSeller();
        $payout = $event->getPayout();
        $order = $event->getOrder();
        $product_list = $event->getOrderProducts();
        $shippingAddress = $event->getShippingAddress();
        $wixOrder = $event->getWixOrder();
        //$product_list = isset($wixOrder->lineItems) ? $wixOrder->lineItems : [];
        
        if (empty($seller) || empty($order) || empty($product_list) || empty($shippingAddress) || empty($wixOrder)) {
            return;
        }
        $email_helper = $this->getHelper('email');
        $recipentEmail = $seller->getEmail();
        // if ($companyApplication->getCompany()->getStoreHash() == '1b4zlf0gsh' ) {
        //     $recipentEmail = 'arjun.singh732@webkul.com';
        // }
        
        if ($order->getStatus() != "") {
            $email_helper->send_mail(
                $recipentEmail,
                [],
                'seller/order_create',
                'application',
                array(
                    'company' => $companyApplication->getCompany(),
                    'seller' => $seller,
                    'payout' => $payout,
                    'order' => $order,
                    'product_list' => $product_list,
                    'shipping_address' => $shippingAddress,
                    'bcOrder' => $wixOrder,
                    'application' => $companyApplication->getApplication(),
                    'storeHash' => $companyApplication->getCompany()->getStoreHash()
                    //'sellerAllowedCustomerDetail' => $allowedCustomerDetails,
                )
            );
        }
    }

    public function wixSellerOrderStatusChangeMail(SellerEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $seller = $event->getSeller();
        //$payout = $event->getPayout();
        $order = $event->getOrder();
        if (empty($seller) || empty($order)) {
            return;
        }

        $company = $companyApplication->getCompany();

        $email_helper = $this->getHelper('email');
        
        $email_helper->send_mail(
            $seller->getEmail(),
            [],
            'seller/order_create',
            'application',
            array(
                'company' => $company,
                'seller' => $seller,
                'order' => $order,
                'application' => $companyApplication->getApplication(),
            )
        );
    }

    public function wixSellerPayoutStatusChangeMail(SellerEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $seller = $event->getSeller();
        $payout = $event->getPayout();
        if (empty($seller) || empty($payout)) {
            return;
        }
        $company = $companyApplication->getCompany();

        $email_helper = $this->getHelper('email');

        $email_helper->send_mail(
            $seller->getEmail(),
            [],
            'seller/payout_status_change',
            'application',
            array(
                'company' => $company,
                'seller' => $seller,
                'payout' => $payout,
                'application' => $companyApplication->getApplication(),
            )
        );
    }

    public function wixSellerPayoutCreateMail(SellerEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $seller = $event->getSeller();
        $payout = $event->getPayout();
        if (empty($seller) || empty($payout)) {
            return;
        }
        $company = $companyApplication->getCompany();

        $email_helper = $this->getHelper('email');

        $email_helper->send_mail(
            $seller->getEmail(),
            [],
            'seller/payout_create',
            'application',
            array(
                'company' => $company,
                'seller' => $seller,
                'payout' => $payout,
                'application' => $companyApplication->getApplication(),
            )
        );
    }

    public function wixSellerPlanChangeMail(SellerEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $seller = $event->getSeller();
        if (empty($seller)) {
            return;
        }
        $company = $companyApplication->getCompany();

        $email_helper = $this->getHelper('email');
        $email_helper->send_mail(
            $company->getEmail(),
            [],
            'seller/plan_buy',
            'application',
            array(
                'company' => $company,
                'seller' => $seller,
                'application' => $companyApplication->getApplication(),
            )
        );

        $email_helper->send_mail(
            $seller->getEmail(),
            [],
            'seller/plan_buy',
            'application',
            array(
                'company' => $company,
                'seller' => $seller,
                'application' => $companyApplication->getApplication(),
            )
        );
    }

    public function wixSellerWithdrawRequestMail(SellerEvent $event)
    {
        $companyApplication = $event->getCompanyApplication();
        if ($companyApplication->getApplication()->getCode() != 'wixmp') {
            return;
        }
        $seller = $event->getSeller();
        $payout = $event->getPayout();
        if (empty($seller) || empty($payout)) {
            return;
        }
        $company = $companyApplication->getCompany();

        $email_helper = $this->getHelper('email');
        $email_helper->send_mail(
            $company->getEmail(),
            [],
            'seller/withdraw_create',
            'application',
            array(
                'company' => $company,
                'seller' => $seller,
                'payout' => $payout,
                'application' => $companyApplication->getApplication(),
            )
        );
    }
}