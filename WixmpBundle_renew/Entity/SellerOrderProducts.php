<?php

namespace Webkul\Modules\Wix\WixmpBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerOrders;

/**
 * @ORM\Entity(repositoryClass="Webkul\Modules\Wix\WixmpBundle\Repository\SellerOrderProductsRepository")
 * @ORM\Table(name="App_wixmp_orders_product")
 */
class SellerOrderProducts
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $CartProductId;

    /**
     * @ORM\Column(type="text")
     */
    private $PlatformProductId;

    /**
     * @ORM\ManyToOne(targetEntity="Webkul\Modules\Wix\WixmpBundle\Entity\Products")
     * @ORM\JoinColumn(nullable=false)
     */
    private $Product;

    /**
     * @ORM\ManyToOne(targetEntity="Webkul\Modules\Wix\WixmpBundle\Entity\SellerOrders", inversedBy="OrderProduct")
     * @ORM\JoinColumn(nullable=false)
     */
    private $SellerOrder;

    public function getId()
    {
        return $this->id;
    }

    public function getCartProductId()
    {
        return $this->CartProductId;
    }

    public function setCartProductId($CartProductId): self
    {
        $this->CartProductId = $CartProductId;

        return $this;
    }

    public function getPlatformProductId()
    {
        return $this->PlatformProductId;
    }

    public function setPlatformProductId($PlatformProductId): self
    {
        $this->PlatformProductId = $PlatformProductId;

        return $this;
    }

    public function getProduct(): ?Products
    {
        return $this->Product;
    }

    public function setProduct(?Products $Product): self
    {
        $this->Product = $Product;

        return $this;
    }

    /**
     * Get the value of SellerOrder
     */
    public function getSellerOrder()
    {
        return $this->SellerOrder;
    }

    /**
     * Set the value of SellerOrder
     *
     * @return  self
     */
    public function setSellerOrder($SellerOrder)
    {
        $this->SellerOrder = $SellerOrder;

        return $this;
    }
}
