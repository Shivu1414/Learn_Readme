<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Entity;

use App\Repository\EtsyShopRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=Webkul\Modules\Wix\WixEtsyAppBundle\Repository\EtsyShopRepository::class)
 * @ORM\Table(name="App_wixetsy_shops")
 */
class EtsyShop
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
     * @ORM\Column(type="integer", nullable=true)
     */
    private $user_id;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $shop_name;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $currency_code;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $shop_url;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $status;

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

    public function setShopId($shop_id)
    {
        $this->shop_id = $shop_id;

        return $this;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function setUserId($user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getShopName()
    {
        return $this->shop_name;
    }

    public function setShopName($shop_name)
    {
        $this->shop_name = $shop_name;

        return $this;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currency_code;
    }

    public function setCurrencyCode(?string $currency_code): self
    {
        $this->currency_code = $currency_code;

        return $this;
    }

    public function getShopUrl(): ?string
    {
        return $this->shop_url;
    }

    public function setShopUrl(?string $shop_url): self
    {
        $this->shop_url = $shop_url;

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

    public function getUpdatedAt(): ?int
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?int $updated_at): self
    {
        $this->updated_at = $updated_at;

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
