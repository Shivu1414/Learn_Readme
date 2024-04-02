<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Entity;

use App\Repository\SettingValueRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=Webkul\Modules\Wix\WixEtsyAppBundle\Repository\SettingValueRepository::class)
 * @ORM\Table(name="App_wixetsy_setting_value")
 */
class SettingValue
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
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $setting_name;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $setting_value;

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

    public function getSettingName(): ?string
    {
        return $this->setting_name;
    }

    public function setSettingName(?string $setting_name): self
    {
        $this->setting_name = $setting_name;

        return $this;
    }

    public function getSettingValue()
    {
        return $this->setting_value;
    }

    public function setSettingValue($setting_value)
    {
        $this->setting_value = $setting_value;

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
