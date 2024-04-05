<?php

namespace Webkul\Modules\Wix\WixmpBundle\Entity;

use App\Repository\PayoutCommissionsRepository;
use Doctrine\ORM\Mapping as ORM;
use Webkul\Modules\Wix\WixmpBundle\Entity\Products;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerOrders;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPayout;

/**
 * @ORM\Entity(repositoryClass=Webkul\Modules\Wix\WixmpBundle\Repository\PayoutCommissionsRepository::class)
 * @ORM\Table(name="App_wixmp_payout_commissions")
 */
class PayoutCommissions
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Webkul\Modules\Wix\WixmpBundle\Entity\SellerOrders::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $orders;

    /**
     * @ORM\ManyToOne(targetEntity=Webkul\Modules\Wix\WixmpBundle\Entity\SellerPayout::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $payout;

    /**
     * @ORM\ManyToOne(targetEntity=Webkul\Modules\Wix\WixmpBundle\Entity\Products::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $commission_type;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $commission_rate;

    /**
     * @ORM\Column(type="float")
     */
    private $commission_amount;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrders(): ?SellerOrders
    {
        return $this->orders;
    }

    public function setOrders(?SellerOrders $orders): self
    {
        $this->orders = $orders;

        return $this;
    }

    public function getPayout(): ?SellerPayout
    {
        return $this->payout;
    }

    public function setPayout(?SellerPayout $payout): self
    {
        $this->payout = $payout;

        return $this;
    }

    public function getProduct(): ?Products
    {
        return $this->product;
    }

    public function setProduct(?Products $product): self
    {
        $this->product = $product;

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

    public function getCommissionRate(): ?float
    {
        return $this->commission_rate;
    }

    public function setCommissionRate(?float $commission_rate): self
    {
        $this->commission_rate = $commission_rate;

        return $this;
    }

    public function getCommissionAmount(): ?float
    {
        return $this->commission_amount;
    }

    public function setCommissionAmount(float $commission_amount): self
    {
        $this->commission_amount = $commission_amount;

        return $this;
    }
}
