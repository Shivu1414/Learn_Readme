<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Entity;

use Webkul\Modules\Wix\WixEtsyAppBundle\Repository\ProductsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=Webkul\Modules\Wix\WixEtsyAppBundle\Repository\ProductsRepository::class)
 * @ORM\Table(name="App_wixetsy_products")
 */
class Products
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=App\Entity\Company::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $company;

    /**
     * @ORM\ManyToOne(targetEntity=App\Entity\CompanyApplication::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $company_application;

    /**
     * @ORM\Column(type="text")
     */
    private $name;

      /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $shop_id;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $sku;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $wix_prod_id;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $sync_status;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $etsy_listing_id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $created_on_etsy;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $sync_message;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $image;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $extra_details;


    public function getId(): ?int
    {
        return $this->id;
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

    public function getCompanyApplication()
    {
        return $this->company_application;
    }

    public function setCompanyApplication($company_application)
    {
        $this->company_application = $company_application;

        return $this;
    }

    public function getShopId()
    {
        return $this->shop_id;
    }

    public function setShopId($shop_id): self
    {
        $this->shop_id = $shop_id;

        return $this;
    }
    
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): self
    {
        $this->sku = $sku;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getWixProdId(): ?string
    {
        return $this->wix_prod_id;
    }

    public function setWixProdId(?string $wix_prod_id): self
    {
        $this->wix_prod_id = $wix_prod_id;

        return $this;
    }

    public function getSyncStatus(): ?string
    {
        return $this->sync_status;
    }

    public function setSyncStatus(?string $sync_status): self
    {
        $this->sync_status = $sync_status;

        return $this;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?int $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getEtsyListingId(): ?int
    {
        return $this->etsy_listing_id;
    }

    public function setEtsyListingId(?int $etsy_listing_id): self
    {
        $this->etsy_listing_id = $etsy_listing_id;

        return $this;
    }

    public function getCreatedOnEtsy(): ?int
    {
        return $this->created_on_etsy;
    }

    public function setCreatedOnEtsy(?int $created_on_etsy): self
    {
        $this->created_on_etsy = $created_on_etsy;

        return $this;
    }

    public function getSyncMessage()
    {
        return $this->sync_message;
    }

    public function setSyncMessage($sync_message)
    {
        $this->sync_message = $sync_message;

        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    public function getExtraDetails()
    {
        return $this->extra_details;
    }

    /**
     * @return  self
     */
    public function setExtraDetails($extra_details)
    {
        $this->extra_details = $extra_details;

        return $this;
    }

}
