<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Entity;

use Webkul\Modules\Wix\WixEtsyAppBundle\Repository\EtsyCategoriesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=Webkul\Modules\Wix\WixEtsyAppBundle\Repository\EtsyCategoriesRepository::class)
 * @ORM\Table(name="App_wixetsy_etsy_categories")
 */
class EtsyCategories
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
    private $category_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $category;

    /**
     * @ORM\Column(type="integer", nullable=true)
    */
    private $shop_id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $is_default;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCompanyApplication()
    {
        return $this->company_application;
    }

    public function setCompanyApplication($company_application): self
    {
        $this->company_application = $company_application;

        return $this;
    }

    public function getCategoryId(): ?string
    {
        return $this->category_id;
    }

    public function setCategoryId(?string $category_id): self
    {
        $this->category_id = $category_id;

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
    
    public function getUpdatedAt(): ?int
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?int $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getIsDefault(): ?int
    {
        return $this->is_default;
    }

    public function setIsDefault(?int $is_default): self
    {
        $this->is_default = $is_default;

        return $this;
    }
}
