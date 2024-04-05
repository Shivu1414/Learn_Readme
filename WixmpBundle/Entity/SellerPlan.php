<?php

namespace Webkul\Modules\Wix\WixmpBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="Webkul\Modules\Wix\WixmpBundle\Repository\SellerPlanRepository")
 * @ORM\Table(name="App_wixmp_plan")
 *
 * @UniqueEntity(
 *     fields={"company", "code"},
 *     errorPath="code",
 *     message="This code is already in use."
 * )
 */
class SellerPlan
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
    private $plan;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=999, nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $status;

    /**
     * @ORM\Column(type="float")
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $intervalType;

    /**
     * @ORM\Column(type="integer")
     */
    private $intervalValue;

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $bestChoice;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Company")
     * @ORM\JoinColumn(nullable=false)
     */
    private $company;

    /**
     * @ORM\Column(type="integer")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="text")
     */
    private $conditions;

    public function __construct()
    {
        $this->createdAt =time();
        $this->updatedAt =time();
        $this->bestChoice = 'N';
        $this->price = 0.00;
        $this->status = 'N';
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPlan(): ?string
    {
        return $this->plan;
    }

    public function setPlan(string $plan): self
    {
        $this->plan = $plan;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

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

    public function getIntervalType(): ?string
    {
        return $this->intervalType;
    }

    public function setIntervalType(string $intervalType): self
    {
        $this->intervalType = $intervalType;

        return $this;
    }

    public function getIntervalValue(): ?int
    {
        return $this->intervalValue;
    }

    public function setIntervalValue(int $intervalValue): self
    {
        $this->intervalValue = $intervalValue;

        return $this;
    }

    public function getBestChoice(): ?string
    {
        return $this->bestChoice;
    }

    public function setBestChoice(string $bestChoice): self
    {
        $this->bestChoice = $bestChoice;

        return $this;
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

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }

    public function setCreatedAt(int $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(int $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getConditions()
    {
        return  unserialize($this->conditions);
    }

    public function setConditions($conditions)
    {
        if (is_array($conditions)) {
            $this->conditions = serialize($conditions);
        } else {
            $this->conditions = $conditions;
        }        
    }
}
