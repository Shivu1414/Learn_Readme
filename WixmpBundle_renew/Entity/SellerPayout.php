<?php

namespace Webkul\Modules\Wix\WixmpBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Webkul\Modules\Wix\WixmpBundle\Repository\SellerPayoutRepository")
 * @ORM\Table(name="App_wixmp_payout")
 */
class SellerPayout
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $payoutType;

    /**
     * @ORM\Column(type="string", length=999, nullable=true)
     */
    private $comment;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $orderAmount;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $payoutAmount;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $commission;

    /**
     * @ORM\Column(type="float")
     */
    private $commissionAmount;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    private $commissionType;

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity="Webkul\Modules\Wix\WixmpBundle\Entity\SellerPlan")
     */
    private $plan;

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
     * @ORM\ManyToOne(targetEntity="Webkul\Modules\Wix\WixmpBundle\Entity\Seller", cascade={"remove"})
     * @ORM\JoinColumns({
     *    @ORM\JoinColumn(nullable=true)
     * })
     */
    private $seller;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $orderId;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $is_commission_per_product;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $is_commission_per_category;

    public function __construct()
    {
        $this->createdAt = time();
        $this->updatedAt = time();
        $this->payout = 'P';
        $this->payoutAmount = 0.00;
        $this->orderAmount = 0.00;
        $this->commission = 0.00;
        $this->commissionAmount = 0.00;
        $this->commissionType = 'P';
        $this->status = 'D';
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPayoutType(): ?string
    {
        return $this->payoutType;
    }

    public function setPayoutType(string $payoutType): self
    {
        $this->payoutType = $payoutType;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getOrderAmount(): ?float
    {
        return $this->orderAmount;
    }

    public function setOrderAmount(?float $orderAmount): self
    {
        $this->orderAmount = $orderAmount;

        return $this;
    }

    public function getPayoutAmount(): ?float
    {
        return $this->payoutAmount;
    }

    public function setPayoutAmount(?float $payoutAmount): self
    {
        $this->payoutAmount = $payoutAmount;

        return $this;
    }

    public function getCommission(): ?float
    {
        return $this->commission;
    }

    public function setCommission(?float $commission): self
    {
        $this->commission = $commission;

        return $this;
    }

    public function getCommissionAmount(): ?float
    {
        return $this->commissionAmount;
    }

    public function setCommissionAmount(float $commissionAmount): self
    {
        $this->commissionAmount = $commissionAmount;

        return $this;
    }

    public function getCommissionType(): ?string
    {
        return $this->commissionType;
    }

    public function setCommissionType(?string $commissionType): self
    {
        $this->commissionType = $commissionType;

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

    public function getPlan(): ?SellerPlan
    {
        return $this->plan;
    }

    public function setPlan(?SellerPlan $plan): self
    {
        $this->plan = $plan;

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

    public function getSeller(): ?Seller
    {
        return $this->seller;
    }

    public function setSeller(?Seller $seller): self
    {
        $this->seller = $seller;

        return $this;
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function setOrderId(int $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getIsCommissionPerProduct(): ?int
    {
        return $this->is_commission_per_product;
    }

    public function setIsCommissionPerProduct(int $is_commission_per_product): self
    {
        $this->is_commission_per_product = $is_commission_per_product;

        return $this;
    }

    public function getIsCommissionPerCategory(): ?int
    {
        return $this->is_commission_per_category;
    }

    public function setIsCommissionPerCategory(int $is_commission_per_category): self
    {
        $this->is_commission_per_category = $is_commission_per_category;

        return $this;
    }
}
