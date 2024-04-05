<?php

namespace Webkul\Modules\Wix\WixmpBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Webkul\Modules\Wix\WixmpBundle\Repository\SellerOrdersRepository")
 * @ORM\Table(name="App_wixmp_orders")
 */
class SellerOrders
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Webkul\Modules\Wix\WixmpBundle\Entity\Seller")
     * @ORM\JoinColumn(nullable=true)
     */
    private $seller;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Company")
     * @ORM\JoinColumn(nullable=false)
     */
    private $company;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $parentId;

    /**
     * @ORM\Column(type="text")
     */
    private $storeOrderId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $storeOrderNo;

     /**
     * @ORM\Column(type="string", length=255)
     */
    private $customerName;

     /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sellerStatus;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $customerId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(type="float")
     */
    private $total;
     /**
     * @ORM\Column(type="float")
     */
    private $tax;

    /**
     * @ORM\Column(type="float")
     */
    private $subtotal;
    /**
     * @ORM\Column(type="float")
     */
    private $shipping;

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $isParent;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $data;

    /**
     * @ORM\Column(type="integer")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $updatedAt;


    /**
     * @ORM\OneToMany(targetEntity="Webkul\Modules\Wix\WixmpBundle\Entity\SellerOrderProducts", mappedBy="SellerOrder", orphanRemoval=true)
     */
    private $OrderProduct;

    /**
    * @ORM\Column(type="float", nullable=true)
    */
    private $discount;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fullfillmentStatus;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $sellerFullfillmentStatus;


    public function __construct()
    {
        $this->createdAt =time();
        $this->updatedAt =time();
        $this->isParent = 'N';
    }

    public function getId()
    {
        return $this->id;
    }

    public function getSeller(): ?Seller
    {
        return $this->seller;
    }

    public function setSeller(?Seller $seller): self
    {
        $this->seller = $seller;

        return $this;
    }

    public function getCompany()
    {
        return $this->company;
    }

    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get the value of parentId
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set the value of parentId
     *
     * @return  self
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
        return $this;
    }

    /**
     * Get the value of customerId
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * Set the value of customerId
     *
     * @return  self
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;

        return $this;
    }

    /**
     * Get the value of storeOrderId
     */
    public function getStoreOrderId()
    {
        return $this->storeOrderId;
    }

    /**
     * Set the value of storeOrderId
     *
     * @return  self
     */
    public function setStoreOrderId($storeOrderId)
    {
        $this->storeOrderId = $storeOrderId;

        return $this;
    }

    /**
     * Get the value of status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the value of status
     *
     * @return  self
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the value of total
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Set the value of total
     *
     * @return  self
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get the value of shipping
     */
    public function getShipping()
    {
        return $this->shipping;
    }

    /**
     * Set the value of shipping
     *
     * @return  self
     */
    public function setShipping($shipping)
    {
        $this->shipping = $shipping;

        return $this;
    }

    /**
     * Get the value of subtotal
     */
    public function getSubtotal()
    {
        return $this->subtotal;
    }
    /**
    * Get the value of tax
    */
    public function getTax()
    {
        return $this->tax;
    }
    /**
     * Set the value of tax
     *
     * @return  self
     */
    public function setTax($tax)
    {
        $this->tax = $tax;

        return $this;
    }
    /**
     * Set the value of subtotal
     *
     * @return  self
     */
    public function setSubtotal($subtotal)
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    /**
     * Get the value of isParent
     */
    public function getIsParent()
    {
        return $this->isParent;
    }

    /**
     * Get the value of data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get the value of createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set the value of createdAt
     *
     * @return  self
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get the value of updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set the value of updatedAt
     *
     * @return  self
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Set the value of isParent
     *
     * @return  self
     */
    public function setIsParent($isParent)
    {
        $this->isParent = $isParent;

        return $this;
    }

    /**
     * Get the value of sellerStatus
     */
    public function getSellerStatus()
    {
        return $this->sellerStatus;
    }

    /**
     * Set the value of sellerStatus
     *
     * @return  self
     */
    public function setSellerStatus($sellerStatus)
    {
        $this->sellerStatus = $sellerStatus;

        return $this;
    }

    /**
     * Get the value of customerName
     */
    public function getCustomerName()
    {
        return $this->customerName;
    }

    /**
     * Set the value of customerName
     *
     * @return  self
     */
    public function setCustomerName($customerName)
    {
        $this->customerName = $customerName;

        return $this;
    }

    /**
     * Get the value of OrderProduct
     */
    public function getOrderProduct()
    {
        return $this->OrderProduct;
    }

    /**
     * Set the value of OrderProduct
     *
     * @return  self
     */
    public function setOrderProduct($OrderProduct)
    {
        $this->OrderProduct = $OrderProduct;

        return $this;
    }

    /**
    * Get the value of discount
    */
    public function getDiscount()
    {
        return $this->discount;
    }
    /**
     * Set the value of discount
     *
     * @return  self
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get the value of storeOrderId
     */
    public function getStoreOrderNo()
    {
        return $this->storeOrderNo;
    }

    /**
     * Set the value of storeOrderId
     *
     * @return  self
     */
    public function setStoreOrderNo($storeOrderNo)
    {
        $this->storeOrderNo = $storeOrderNo;

        return $this;
    }

    /**
     * Get the value of fullfillment status
     */
    public function getFullfillmentStatus()
    {
        return $this->fullfillmentStatus;
    }

    /**
     * Set the value of fullfillment status
     *
     * @return  self
     */
    public function setFullfillmentStatus($fullfillmentStatus)
    {
        $this->fullfillmentStatus = $fullfillmentStatus;

        return $this;
    }

    public function getSellerFullfillmentStatus()
    {
        return $this->sellerFullfillmentStatus;
    }

    public function setSellerFullfillmentStatus($sellerFullfillmentStatus)
    {
        $this->sellerFullfillmentStatus = $sellerFullfillmentStatus;
        return $this;
    }
}
