<?php
namespace Webkul\Modules\Wix\WixEtsyAppBundle\Utils;

use App\Helper\BaseHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use App\Utils\Platform\Wix\WixClient;
use App\Utils\Platform\Etsy\EtsyClient;

class HelperClass extends BaseHelper
{
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->container = $container;
        $this->translate = $this->container->get('translator');
        $this->cache = $this->container->get('cache.app');
        $this->session = $this->container->get('session');
        $this->request = $this->container->get('request_stack')->getCurrentRequest();
        $this->dispatcher = $this->container->get('event_dispatcher');
        $this->logger = $logger;
        $this->container = $container;
        $companyApplication = $this->container->get('app.runtime')->get_company_application();

        $this->wixClient = new WixClient($companyApplication);
        $this->etsyClient = new EtsyClient($companyApplication);
    }

    public function getAppHelper($helper, $app_code = 'wixetsy', $platform = null)
    {   
        if ($app_code == 'wixetsy') {
            switch ($helper) {
                case 'platform':
                case 'PlatformHelper':
                    $this->_helpers['platform'] = new PlatformHelper($this->entityManager, $this->container, $this->logger);
                    return $this->_helpers['platform'];
                break;

                case 'catalog':
                case 'CatalogHelper':
                    $this->_helpers['catalog'] = new CatalogHelper($this->entityManager, $this->container, $this->logger);
                    return $this->_helpers['catalog'];
                break;

                default:
                    return null;
            }
        }

        return null;
    }
}