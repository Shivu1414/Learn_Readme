<?php

namespace Webkul\Modules\Wix\WixmpBundle\Utils;

use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Utils\Platform\Wix\WixClient;

class PlatformHelper
{
    private $wixClient;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $companyApplication = $this->container->get('app.runtime')->get_company_application();

        $this->wixClient = new WixClient($companyApplication);
        
    }

    public function init($companyApplication)
    {
        $this->wixClient = new WixClient($companyApplication);
    }

    public function get_platform_product($platform_product_id, $params = [])
    {
        return $this->wixClient->get_product($platform_product_id, $params);
    }

    public function create_platform_product($params = [])
    {
        return $this->wixClient->create_product($params);
    }

    public function update_platform_product($platform_product_id, $params = [])
    {
        return $this->wixClient->update_product($platform_product_id, $params);
    }

    public function delete_platform_product($platform_product_id)
    {
        return $this->wixClient->delete_product($platform_product_id);
    }

    public function get_platform_orders($params = [])
    {
        return $this->wixClient->ger_orders($params);
    }

    public function get_platform_order_info($storeOrderId)
    {
        return $this->wixClient->ger_order_info($storeOrderId);
    }

    public function get_platform_products($params = [])
    {
        return $this->wixClient->get_products($params);
    }

    public function add_product_media($productId, $params = [])
    {
        return $this->wixClient->add_product_media($productId, $params);
    }

    public function delete_platform_product_media($platform_product_id, $params = [])
    {
        return $this->wixClient->remove_product_media($platform_product_id, $params);
    }

    public function get_platform_categories($params = [])
    {
        return $this->wixClient->get_collections($params);
    }

    public function get_platform_categorie($params = [])
    {
        return $this->wixClient->get_collection($params);
    }

    public function add_products_to_collection($collectionId, $productIds = [])
    {
        return $this->wixClient->addProductsToCollection($collectionId, $productIds);
    }

    public function remove_products_from_collection($collectionId, $productIds = [])
    {
        return $this->wixClient->removeProductsFromCollection($collectionId, $productIds);
    }

    public function create_collections($params = [])
    {
        return $this->wixClient->createCollections($params);
    }

    public function callApi($url, $method = "POST" , $params = [])
    {
        return $this->wixClient->callApi($url, $method, $params);
    }
    public function getInventory($params = [])
    {
        return $this->wixClient->get_InventoryVariants($params);
    }
    public function updateInventory($productId, $params = [])
    {
        return $this->wixClient->update_Inventory($productId, $params);
    }
}