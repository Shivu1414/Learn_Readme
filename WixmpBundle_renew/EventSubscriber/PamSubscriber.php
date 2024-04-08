<?php

namespace Webkul\Modules\Wix\WixmpBundle\EventSubscriber;

use App\Events\PamEvent;
use App\Events\UserEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PamSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        // return the subscribed events, their methods and priorities
        return array(
            UserEvent::WIX_SELLER_LOGIN => 'onSellerLogin'
        );
    }

    // public function onLandingPageEvent(PamEvent $event)
    // {
    //     $host = $this->request->getHttpHost();
    //     if ($host != $this->request->server->get('APP_DOMAIN')) {
    //         // domain mapping enabled: redirect to seller login/registration
    //         $companyAppHelper = $this->getHelper('company_application');
    //         $companyApplication = $companyAppHelper->getCompanyApplication('','',$this->request->getHost());
    //         if (!empty($companyApplication)) {
    //             // domain mappinng proceed
    //             $company = $companyApplication->getCompany();
    //             $event->setLoadRedirect($this->generateUrl(
    //                 'mp_seller_secure_login', 
    //                 array(
    //                     'storeHash' => $company->getStoreHash()
    //                 )
    //             ));
    //         }
    //     }
    // }

    public function onSellerLogin(UserEvent $event)
    {
        if (!empty($event->getCompany())) {

        }
    }
}
