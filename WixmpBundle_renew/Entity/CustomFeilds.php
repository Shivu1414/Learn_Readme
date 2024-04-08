<?php

namespace Webkul\Modules\Wix\WixmpBundle\Entity;

use Webkul\Modules\Wix\WixmpBundle\Repository\CustomFeildsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=Webkul\Modules\Wix\WixmpBundle\Repository\CustomFeildsRepository::class)
 * @ORM\Table(name="App_wix_custom_fields")
 */
class CustomFeilds
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=App\Entity\CompanyApplication::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $company_application;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $feild_name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $label;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $options;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_required;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $class;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $status;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFeildName(): ?string
    {
        return $this->feild_name;
    }

    public function setFeildName(?string $feild_name): self
    {
        $this->feild_name = $feild_name;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getOptions(): ?string
    {
        return $this->options;
    }

    public function setOptions(?string $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function getIsRequired(): ?bool
    {
        return $this->is_required;
    }

    public function setIsRequired(?bool $is_required): self
    {
        $this->is_required = $is_required;

        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?string $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(?string $class): self
    {
        $this->class = $class;

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
}
