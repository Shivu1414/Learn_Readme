<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Entity;

use Webkul\Modules\Wix\WixEtsyAppBundle\Repository\WixEtsyProductImageMappingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=Webkul\Modules\Wix\WixEtsyAppBundle\Repository\WixEtsyProductImageMappingRepository::class)
 * @ORM\Table(name="App_wixetsy_product_image_mapping")
 */
class WixEtsyProductImageMapping
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=App\Entity\Company::class)
     */
    private $company;

    /**
     * @ORM\ManyToOne(targetEntity=App\Entity\CompanyApplication::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $company_application;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $listing_id;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $wix_prod_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $wix_image_id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $etsy_image_id;

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

    public function getListingId(): ?string
    {
        return $this->listing_id;
    }

    public function setListingId(?string $listing_id): self
    {
        $this->listing_id = $listing_id;

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

    public function getWixImageId(): ?string
    {
        return $this->wix_image_id;
    }

    public function setWixImageId(?string $wix_image_id): self
    {
        $this->wix_image_id = $wix_image_id;

        return $this;
    }

    public function getEtsyImageId(): ?string
    {
        return $this->etsy_image_id;
    }

    public function setEtsyImageId(string $etsy_image_id): self
    {
        $this->etsy_image_id = $etsy_image_id;

        return $this;
    }
}
