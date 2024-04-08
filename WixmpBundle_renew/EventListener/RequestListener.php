<?php

namespace Webkul\Modules\Wix\WixmpBundle\EventListener;

use App\Entity\CompanyApplication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Helper\BaseHelper;

class RequestListener
{
    protected $container;

    protected $twig;

    protected $entityManager;

    protected $baseHelper;

    public function __construct(ContainerInterface $container, \Twig_Environment $twig, EntityManagerInterface $entityManager, BaseHelper $baseHelper)
    {
        $this->container = $container;
        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->baseHelper = $baseHelper;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        if ($request->get('platform') == "wix") {
            $area = $request->get('area');

            $this->twig->addGlobal('newTheme', false);
            $commonHelper = $this->baseHelper->getHelper('common');
            $companyApplicationHelper = $this->baseHelper->getHelper('CompanyApplicationHelper');
            $companyApplication = $companyApplicationHelper->getCompanyApplication($request->get('app_code'), $request->get('storeHash'));
            if (!empty($companyApplication)) {
                $_params = [
                    'sectionName' => 'general',
                    'settingName' => 'theme',
                    'company' => $companyApplication->getCompany(),
                    'application' => $companyApplication->getApplication(),
                ];
                $settingInfo = $commonHelper->get_section_setting($_params);
                if (!empty($settingInfo)) {
                    $this->twig->addGlobal('settingInfo', $settingInfo);
                }
            }

            if (isset($settingInfo) && $settingInfo->getValue() == "UPDATEFEB10") {
                $this->twig->addGlobal('newTheme', true);
            }

            $customizationStoreHash = ['RishabhStore-SAAS727a', 'VILLUMIb6f3'];
            $this->twig->addGlobal('locale', $request->get('_locale'));
            $this->twig->addGlobal('villumi', false);
            if (in_array($request->get('storeHash'), $customizationStoreHash)) {
                if ($request->get('_locale') == "de") {
                    $this->twig->addGlobal('villumi', true);
                }
            }

            if ($area == 'mp-wix-seller') {
                $this->twig->addGlobal('area', 'mp-wix-seller');
                if ($this->container->get('security.token_storage')->getToken()->getUser() != 'anon.') {
                    $user = $this->container->get('security.token_storage')->getToken()->getUser();
                    $redirect = false;
                    $notifications = [];
                    $storeHash = $request->get('storeHash');
                    if ($user->getStatus() != null && $user->getStatus() != 'A') {
                        $redirect = true;
                        $notifications['danger'][] = 'Cannot Login, User account is not Active';
                    }
                    if ($user->getSeller() != null && $user->getSeller()->getCompany()->getId() != $user->getCompany()->getId()) {
                        $redirect = true;
                        $notifications['danger'][] = 'Cannot Login, User not allowed to login';
                    }
                    if ($user->getSeller() != null && $user->getSeller()->getStatus() != 'A') {
                        $redirect = true;
                        $notifications['danger'][] = 'Cannot Login, Seller account not Active';
                    }
                    if ($user->getSeller() != null && $user->getSeller()->getIsArchieved() == 1) {
                        $redirect = true;
                        $notifications['danger'][] = 'Cannot Login, Seller account not Active';
                    }
                    if (!$redirect) {
                        $app_code = $request->get('app_code');
                        $em = $this->entityManager;
                        $CompanyAppplicationRepository = $em->getRepository(CompanyApplication::class);
                        $companyApplication = $CompanyAppplicationRepository->findByStoreHashAndAppCode($storeHash, $app_code);
                        $currentSubscription = $companyApplication->getSubscription();
                        if ($currentSubscription == null) {
                            $redirect = true;
                        } elseif ($currentSubscription->getStatus() == 'X') {
                            $redirect = true;
                        } else {
                            if ($currentSubscription->getNextBillingDate() <= time()) {
                                $redirect = true;
                            } else {
                                $redirect = false;
                            }
                        }
                        if ($redirect) {
                            $notifications['danger'][] = 'Cannot Login, Please contact Store Admin';
                        }
                    }
                    
                    if (!$redirect) {
                        if (!in_array('ROLE_WIXMP_SELLER', $user->getRoles())) {
                            $redirect = true;
                            $notifications['danger'][] = 'Cannot Login, Only Seller allowed to login';
                        }
                        $request = $this->container->get('request_stack')->getCurrentRequest();
                        $storeHash = $request->get('storeHash');
                        if ($storeHash == null || $storeHash == '') {
                            $redirect = true;
                            $notifications['danger'][] = 'Cannot Login, Company Invalid';
                        } else {
                            if ($user->getCompany() == null) {
                                $redirect = true;
                                $notifications['danger'][] = 'Cannot Login, Company cannot be empty';
                            } elseif ($user->getCompany()->getStoreHash() != $storeHash) {
                                $redirect = true;
                                $notifications['danger'][] = 'Cannot Login, Company not allowed';
                            } elseif ($user->getSeller() == null) {
                                $redirect = true;
                                $notifications['danger'][] = 'Cannot Login, Seller account cannot be null';
                            }
                        }
                    }
                    
                    if ($redirect) {
                        $router = $this->container->get('router');
                        $response = new RedirectResponse($router->generate('mp_wix_seller_secure_logout', ['storeHash' => $storeHash, 'notifications' => $notifications]));
                        $event->setResponse($response);

                        return $event;
                    }
                }
            } else {
                $this->twig->addGlobal('area', $area);
            }
        }
    }
}
