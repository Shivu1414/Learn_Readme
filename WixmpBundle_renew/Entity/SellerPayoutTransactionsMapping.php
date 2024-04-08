<?php

namespace Webkul\Modules\Wix\WixmpBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\ApplicationPayoutTransactions;
use App\Entity\Company;
use App\Entity\User;

/**
 * @ORM\Entity(repositoryClass="Webkul\Modules\Wix\WixmpBundle\Repository\SellerPayoutTransactionsMappingRepository")
 * @ORM\Table(name="App_wixmp_seller_payout_transactions_mapping")
 */
class SellerPayoutTransactionsMapping
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Webkul\Modules\Wix\WixmpBundle\Entity\Seller", cascade={"remove"})
     * @ORM\JoinColumns({
     *    @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     * })
     */
    private $seller;

    /**
     * @ORM\ManyToOne(targetEntity="Webkul\Modules\Wix\WixmpBundle\Entity\SellerPayoutTransactions", cascade={"remove"})
     * @ORM\JoinColumns({
     *    @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     * })
     */
    private $sellerPayoutTransactions;
    /**
     * 
     * @ORM\ManyToOne(targetEntity="App\Entity\Company")
     */
    private $company;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $batchId;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $senderBatchId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $senderItemId;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $payoutItemId;

    
    /**
     * @ORM\Column(type="float")
     */
    private $amount;
    /**
     * @ORM\Column(type="string", length=25)
     */
    private $status;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $extra;
    
    /**
     * @ORM\Column(type="string", length=999, nullable=true)
     */
    private $transactionId;
    /**
     * @ORM\Column(type="integer")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $modifiedBy;

    public function __construct()
    {
        $this->createdAt = time();
        $this->updatedAt = time();
    }

    public function getId()
    {
        return $this->id;
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

    public function getSellerPayoutTransactions(): ?SellerPayoutTransactions
    {
        return $this->sellerPayoutTransactions;
    }

    public function setSellerPayoutTransactions(?SellerPayoutTransactions $sellerPayoutTransactions): self
    {
        $this->sellerPayoutTransactions = $sellerPayoutTransactions;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }
    
    public function getBatchId(): ?string
    {
        return $this->batchId;
    }

    public function setBatchId(string $batchId): self
    {
        $this->batchId = $batchId;

        return $this;
    }
    public function getSenderBatchId(): ?string
    {
        return $this->senderBatchId;
    }

    public function setSenderBatchId(string $senderBatchId): self
    {
        $this->senderBatchId = $senderBatchId;

        return $this;
    }
    public function getSenderItemId(): ?string
    {
        return $this->senderItemId;
    }

    public function setSenderItemId(string $senderItemId): self
    {
        $this->senderItemId = $senderItemId;

        return $this;
    }
    public function getPayoutItemId(): ?string
    {
        return $this->payoutItemId;
    }

    public function setPayoutItemId(string $payoutItemId): self
    {
        $this->payoutItemId = $payoutItemId;

        return $this;
    }
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

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

    public function getExtra()
    {
        return unserialize($this->extra);
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId): self
    {
        $this->transactionId = $transactionId;

        return $this;
    }
    
    public function setExtra($extra): self
    {
        $this->extra = serialize($extra);

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

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedAtValue(): self
    {
        $this->updatedAt = time();

        return $this;
    }

   

    public function getModifiedBy(): ?User
    {
        return $this->modifiedBy;
    }

    public function setModifiedBy(?User $modifiedBy): self
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }
}
