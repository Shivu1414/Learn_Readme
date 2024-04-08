<?php

namespace Webkul\Modules\Wix\WixmpBundle\Events;

use App\Entity\Company;
use Symfony\Contracts\EventDispatcher\Event;
use Webkul\Modules\Wix\WixmpBundle\Entity\Seller;
use Webkul\Modules\Wix\WixmpBundle\Entity\Products;

class CatalogEvent extends Event
{
    const CATALOG_PRODUCT_MANAGE_POST = 'catalog.product.manage.post';
    const CATALOG_PRODUCT_VISIBILITY_CHANGE = 'catalog.product.visibility.change';
    const CATALOG_PRODUCT_SELLER_ASSIGN = 'catalog.product.seller.assign';
    const WIX_CATALOG_PRODUCT_ADD = 'wix.catalog.product.admin.add';
    const CATALOG_PRODUCT_UPDATE = 'catalog.product.admin.update';
    const CATALOG_PRODUCT_WIX_SELLER_ADD = 'catalog.product.wix.seller.add';
    const CATALOG_PRODUCT_WIX_SELLER_UPDATE = 'catalog.product.wix.seller.update';

    // PRODUCT SKU EVENT
    const CATALOG_PRODUCT_SKU_UPDATE = 'catalog.product.sku.update';

    /**
     * @var Array
     */
    private $params;

    private $platformProductData;

    private $product;

    private $productData;

    private $productParams;

    // Product SKU
    private $skuId;

    private $skuObject;

    /**
     * @var Array
     */
    private $products;

    /**
     * @var Company
     */
    private $company;

    /**
     * @var Seller
     */
    private $seller;

    private $bodyText;

    protected $companyApplication;

    private $actionAllowed;

    public function __construct($companyApplication = null, Products $product = null)
    {
        $this->companyApplication = $companyApplication;
        if($product != null){
            $this->product = $product;
            $this->company = $product->getCompany();
            $this->seller = $product->getSeller();
        }
    }

    /**
     * Get the value of bodyText
     */
    public function getBodyText()
    {
        return $this->bodyText;
    }

    /**
     * Set the value of bodyText
     *
     * @return  self
     */
    public function setBodyText($bodyText)
    {
        $this->bodyText = $bodyText;

        return $this;
    }

    /**
     * Get the value of company
     *
     * @return  Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set the value of company
     *
     * @param  Company  $company
     *
     * @return  self
     */
    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get the value of seller
     *
     * @return  Seller
     */
    public function getSeller()
    {
        return $this->seller;
    }

    /**
     * Set the value of seller
     *
     * @param  Seller  $seller
     *
     * @return  self
     */
    public function setSeller($seller)
    {
        $this->seller = $seller;

        return $this;
    }

    /**
     * Get the value of actionAllowed
     */
    public function getActionAllowed()
    {
        return $this->actionAllowed;
    }

    /**
     * Set the value of actionAllowed
     *
     * @return  self
     */
    public function setActionAllowed($actionAllowed)
    {
        $this->actionAllowed = $actionAllowed;

        return $this;
    }

    /**
     * Get the value of products
     *
     * @return  Array
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Set the value of products
     *
     * @param  Array  $products
     *
     * @return  self
     */
    public function setProducts($products)
    {
        $this->products = $products;

        return $this;
    }

    /**
     * Get the value of params
     *
     * @return  Array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set the value of params
     *
     * @param  Array  $params
     *
     * @return  self
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get the value of platformProductData
     */
    public function getPlatformProductData()
    {
        return $this->platformProductData;
    }

    /**
     * Set the value of platformProductData
     *
     * @return  self
     */
    public function setPlatformProductData($platformProductData)
    {
        $this->platformProductData = $platformProductData;

        return $this;
    }

    /**
     * Get the value of companyApplication
     */
    public function getCompanyApplication()
    {
        return $this->companyApplication;
    }

    /**
     * Set the value of companyApplication
     *
     * @return  self
     */
    public function setCompanyApplication($companyApplication)
    {
        $this->companyApplication = $companyApplication;

        return $this;
    }

    /**
     * Get the value of product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set the value of product
     *
     * @return  self
     */
    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get the value of productData
     */
    public function getProductData()
    {
        return $this->productData;
    }

    /**
     * Set the value of productData
     *
     * @return  self
     */
    public function setProductData($productData)
    {
        $this->productData = $productData;

        return $this;
    }

    /**
     * Get the value of productParams
     */
    public function getProductParams()
    {
        return $this->productParams;
    }

    /**
     * Set the value of productParams
     *
     * @return  self
     */
    public function setProductParams($productParams)
    {
        $this->productParams = $productParams;

        return $this;
    }

    /**
     * Get the value of skuId
     */
    public function getSkuId()
    {
        return $this->skuId;
    }

    /**
     * Set the value of skuId
     *
     * @return  self
     */
    public function setSkuId($skuId)
    {
        $this->skuId = $skuId;

        return $this;
    }

    /**
     * Get the value of skuObject
     */
    public function getSkuObject()
    {
        return $this->skuObject;
    }

    /**
     * Set the value of skuObject
     *
     * @return  self
     */
    public function setSkuObject($skuObject)
    {
        $this->skuObject = $skuObject;

        return $this;
    }
}