<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Entity;

use Webkul\Modules\Wix\WixEtsyAppBundle\Repository\ShippingProfilesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=Webkul\Modules\Wix\WixEtsyAppBundle\Repository\ShippingProfilesRepository::class)
 * @ORM\Table(name="App_wixetsy_shipping_profiles")
 */
class ShippingProfiles
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
     * @ORM\Column(type="integer", nullable=true)
     */
    private $shop_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $shipping_profile_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $display_label;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $is_default;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $updated_at;

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

    public function getShopId(): ?int
    {
        return $this->shop_id;
    }

    public function setShopId(?int $shop_id): self
    {
        $this->shop_id = $shop_id;

        return $this;
    }

    public function getShippingProfileId()
    {
        return $this->shipping_profile_id;
    }

    public function setShippingProfileId($shipping_profile_id)
    {
        $this->shipping_profile_id = $shipping_profile_id;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDisplayLabel(): ?string
    {
        return $this->display_label;
    }

    public function setDisplayLabel(?string $display_label): self
    {
        $this->display_label = $display_label;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

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

    public function getUpdatedAt(): ?int
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?int $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }
}
