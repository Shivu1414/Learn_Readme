<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Entity;

use Webkul\Modules\Wix\WixEtsyAppBundle\Repository\CategoryMappingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=Webkul\Modules\Wix\WixEtsyAppBundle\Repository\CategoryMappingRepository::class)
 * @ORM\Table(name="App_wixetsy_category_mapping")
 */
class CategoryMapping
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $shop_id;

     /**
     * @ORM\ManyToOne(targetEntity=Webkul\Modules\Wix\WixEtsyAppBundle\Entity\WixCategories::class)
     */
    private $wix_category;

    /**
     * @ORM\ManyToOne(targetEntity=Webkul\Modules\Wix\WixEtsyAppBundle\Entity\EtsyCategories::class)
     */
    private $etsy_category;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $wix_category_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $etsy_category_name;

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

    public function getShopId(): ?string
    {
        return $this->shop_id;
    }

    public function setShopId(?string $shop_id): self
    {
        $this->shop_id = $shop_id;

        return $this;
    }

    public function getWixCategory(): ?WixCategories
    {
        return $this->wix_category;
    }

    public function setWixCategory(?WixCategories $wix_category): self
    {
        $this->wix_category = $wix_category;

        return $this;
    }

    public function getEtsyCategory(): ?EtsyCategories
    {
        return $this->etsy_category;
    }

    public function setEtsyCategory(?EtsyCategories $etsy_category): self
    {
        $this->etsy_category = $etsy_category;

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

    public function getWixCategoryName(): ?string
    {
        return $this->wix_category_name;
    }

    public function setWixCategoryName(?string $wix_category_name): self
    {
        $this->wix_category_name = $wix_category_name;

        return $this;
    }

    public function getEtsyCategoryName(): ?string
    {
        return $this->etsy_category_name;
    }

    public function setEtsyCategoryName(?string $etsy_category_name): self
    {
        $this->etsy_category_name = $etsy_category_name;

        return $this;
    }
}
