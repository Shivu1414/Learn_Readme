<?php

namespace Webkul\Modules\Wix\WixmpBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Webkul\Modules\Wix\WixmpBundle\Repository\ProductsRepository")
 * @ORM\Table(name="App_wixmp_products")
 */
class Products
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sku;

    /**
     * @ORM\Column(type="float")
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pricedata;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $stockLevel;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $video;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $videoDescription;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $videoTitle;

    /**
     * @ORM\ManyToOne(targetEntity="Webkul\Modules\Wix\WixmpBundle\Entity\Seller", inversedBy="products")
     */
    private $seller;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Company")
     * @ORM\JoinColumn(nullable=false)
     */
    private $company;

    /**
     * @ORM\Column(type="string")
     */
    private $_prod_id;

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $status;

    /**
     * @ORM\Column(type="integer", length=10)
     */
    private $timestamp;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $data;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $storeUrl;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $availableOn;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $commission;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $commission_type;

    /**
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    private $is_deleted = 0;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $category_data;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $weight;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $brand;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $original_name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $extra_details;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $trackInventory;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $quantity;
    
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $inStock;

    public function __constuct()
    {
        $this->timestamp = time();
    }

    public function getId()
    {
        return $this->id;
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

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }
    public function getPriceData(): ?string
    {
        return $this->pricedata;
    }
    public function setPriceData(?string $pricedata): self 
    {
        $this->pricedata = $pricedata;

        return $this;
    }
    public function getStockLevel(): ?int
    {
        return $this->stockLevel;
    }

    public function setStockLevel(int $stockLevel): self
    {
        $this->stockLevel = $stockLevel;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getSeller()
    {
        return $this->seller;
    }

    public function setSeller($seller): self
    {
        $this->seller = $seller;

        return $this;
    }

    public function getCompany()
    {
        return $this->company;
    }

    public function setCompany($company): self
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Set the value of id.
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getProdId()
    {
        return $this->_prod_id;
    }

    public function setProdId($_prod_id): self
    {
        $this->_prod_id = $_prod_id;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(?string $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getStoreUrl(): ?string
    {
        return $this->storeUrl;
    }

    public function setStoreUrl(?string $url): self
    {
        $this->storeUrl = $url;

        return $this;
    }

    /**
     * Get the value of timestamp.
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set the value of timestamp.
     *
     * @return self
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get the value of availableOn.
     */
    public function getAvailableOn()
    {
        return $this->availableOn;
    }

    /**
     * Set the value of availableOn.
     *
     * @return self
     */
    public function setAvailableOn($availableOn)
    {
        $this->availableOn = $availableOn;

        return $this;
    }

    public function getVideo(): ?string
    {
        return $this->video;
    }

    public function setVideo(?string $video): self
    {
        $this->video = $video;

        return $this;
    }

    public function getVideoDescription(): ?string
    {
        return $this->videoDescription;
    }

    public function setVideoDescription(?string $videoDescription): self
    {
        $this->videoDescription = $videoDescription;

        return $this;
    }

    public function getVideoTitle(): ?string
    {
        return $this->videoTitle;
    }

    public function setVideoTitle(?string $videoTitle): self
    {
        $this->videoTitle = $videoTitle;

        return $this;
    }

    public function getCommission(): ?float
    {
        return $this->commission;
    }

    public function setCommission(float $commission): self
    {
        $this->commission = $commission;

        return $this;
    }

    public function getCommissionType(): ?string
    {
        return $this->commission_type;
    }

    public function setCommissionType(?string $commission_type): self
    {
        $this->commission_type = $commission_type;

        return $this;
    }

    public function getIsDeleted(): ?int
    {
        return $this->is_deleted;
    }

    public function setIsDeleted(int $isDeleted): self
    {
        $this->is_deleted = $isDeleted;

        return $this;
    }

    public function getCategoryData()
    {
        return json_decode($this->category_data);
    }

    public function setCategoryData($category_data)
    {
        $this->category_data = json_encode($category_data);

        return $this;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getBrand()
    {
        return $this->brand;
    }

    public function setBrand($brand)
    {
        $this->brand = $brand;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->original_name;
    }

    public function setOriginalName(string $original_name): self
    {
        $this->original_name = $original_name;

        return $this;
    }
    public function getExtraDetails()
    {
        return $this->extra_details;
    }
    public function setExtraDetails($extra_details){
        $this->extra_details = $extra_details;

        return $this;
    }

    public function hasTrackInventory(): ?bool
    {
        return $this->trackInventory;
    }

    public function setTrackInventory(?bool $trackInventory): self
    {
        $this->trackInventory = $trackInventory;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function isInStock(): ?bool
    {
        return $this->inStock;
    }

    public function setInStock(?bool $inStock): self
    {
        $this->inStock = $inStock;

        return $this;
    }

}
