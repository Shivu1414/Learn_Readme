<?php

namespace Webkul\Modules\Wix\WixmpBundle\Utils;

use App\Helper\BaseHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

class WixMpBaseHelper extends BaseHelper
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
    }

    public function getAppHelper($helper, $app_code = 'wixmp', $platform = null)
    {
        if ($app_code == 'wixmp') {
            switch ($helper) {
                case 'platform':
                case 'PlatformHelper':
                    $this->_helpers['user'] = new PlatformHelper($this->container);
                    return $this->_helpers['user'];
                break;

                case 'wixmpCompany':
                case 'WixMpCompanyHelper':
                    $this->_helpers['user'] = new WixMpCompanyHelper($this->entityManager, $this->container, $this->logger);
                    return $this->_helpers['user'];
                break;

                case 'seller':
                case 'SellerHelper':
                    $this->_helpers['user'] = new SellerHelper($this->entityManager, $this->container, $this->logger);
                    return $this->_helpers['user'];
                break;

                case 'catalog':
                case 'CatalogHelper':
                    $this->_helpers['user'] = new CatalogHelper($this->entityManager, $this->container, $this->logger);
                    
                    // dd($this->_helpers['user']);
                    return $this->_helpers['user'];
                break;
                
                case 'user':
                case 'UserHelper':
                    $this->_helpers['user'] = new UserHelper($this->entityManager, $this->container, $this->logger);
                    return $this->_helpers['user'];
                break;

                case 'sales':
                case 'SalesHelper':
                    $this->_helpers['user'] = new SalesHelper($this->entityManager, $this->container, $this->logger);

                    //what does this SalesHelper function Explore.
                    // dd($this->_helpers);
                    return $this->_helpers['user'];
                break;

                case 'commission':
                case 'CommissionHelper':
                    $this->_helpers['user'] = new CommissionHelper($this->entityManager, $this->container, $this->logger);
                    return $this->_helpers['user'];
                break;

                case 'import_export':
                case 'ImportExportHelper':
                    $this->_helpers['user'] = new ImportExportHelper($this->entityManager, $this->container, $this->logger);
                    return $this->_helpers['user'];
                break;

                default:
                    return null;
            }
        }

        return null;
    }
}