<?php

namespace Webkul\Modules\Wix\WixChatgptBundle\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Utils\Platform\Wix\WixClient;

class PlatformHelper {
    
    private $wixClient;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $companyApplication = $this->container->get('app.runtime')->get_company_application();
        $this->wixClient = new WixClient($companyApplication);
    }

    public function get_platform_products ($params = null)  {
        if ( isset($params) ) {
            return $this->wixClient->get_products($params);
        }
        return false;
    }

    public function update_product($productId = null, $params = null) {
        if ( !empty($params) && !empty($productId) ) {
            return $this->wixClient->update_product($productId, $params);
        }
        return false;
    }

    public function get_platform_product ($prod_id)  {
        if ( isset($prod_id) ) {
            return $this->wixClient->get_product($prod_id);
        }
        return false;
    }

}