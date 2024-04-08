<?php

namespace Webkul\Modules\Wix\WixmpBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\ApplicationPayoutPayments;
use App\Entity\Company;
use App\Entity\User;
use Webkul\Modules\Wix\WixmpBundle\Entity\SellerPayoutTransactionsMapping;
/**
 * @ORM\Entity(repositoryClass="Webkul\Modules\Wix\WixmpBundle\Repository\SellerPayoutTransactionsRepository")
 * @ORM\Table(name="App_wixmp_seller_payout_transactions")
 */
class SellerPayoutTransactions
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $batchId;

    /**
     * @ORM\Column(type="float")
     */
    private $amount;
    /**
     * 
     * @ORM\ManyToOne(targetEntity="App\Entity\Company")
     */
    private $company;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ApplicationPayoutPayments")
     */
    private $payment;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $extra;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $senderBatchId;

    /**
     * @ORM\Column(type="string", length=25, options={"default" : "OPEN"})
     */
    private $status;


    /**
     * @ORM\Column(type="string", length=999, nullable=true)
     */
    private $transactionId;

     /**
     * @ORM\OneToMany(targetEntity="Webkul\Modules\Wix\WixmpBundle\Entity\SellerPayoutTransactionsMapping", mappedBy="sellerPayoutTransactions", cascade={"persist"})
     */
    private $sellerPayoutTransactionsMapping;
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

    public function getBatchId(): ?string
    {
        return $this->batchId;
    }

    public function setBatchId(string $batchId): self
    {
        $this->batchId = $batchId;

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
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getPayment(): ?ApplicationPayoutPayments
    {
        return $this->payment;
    }

    public function setPayment(?ApplicationPayoutPayments $payment): self
    {   
        $this->payment = $payment;

        return $this;
    }

    public function getExtra()
    {
        return unserialize($this->extra);
    }

    public function setExtra($extra): self
    {
        $this->extra = serialize($extra);

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

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

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId): self
    {
        $this->transactionId = $transactionId;

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

    /**
     * @return Collection|Products[]
     */
    public function getSellerPayoutTransactionsMapping()
    {
        return $this->sellerPayoutTransactionsMapping;
    }

    public function addSellerPayoutTransactionsMapping($sellerPayoutTransactionsMapping)
    {
        if (empty($this->sellerPayoutTransactionsMapping) || !$this->sellerPayoutTransactionsMapping->contains($sellerPayoutTransactionsMapping)) {
            $this->sellerPayoutTransactionsMapping[] = $sellerPayoutTransactionsMapping;
            $sellerPayoutTransactionsMapping->setSellerPayoutTransactions($this);
        }
        return $this;
    }
}
