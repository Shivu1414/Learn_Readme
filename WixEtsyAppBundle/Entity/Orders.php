<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Entity;

use App\Repository\OrdersRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=Webkul\Modules\Wix\WixEtsyAppBundle\Repository\OrdersRepository::class)
 * @ORM\Table(name="App_wixetsy_orders")
 */
class Orders
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=App\Entity\Company::class)
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
    private $shop_id;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $receipt_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $buyer_email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $first_line;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $second_line;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $state;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $zip;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $order_status;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $formatted_address;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $country_iso;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $payment_method;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $is_shipped;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $is_paid;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $created_at;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $raw_data;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     */
    private $sync_status;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $sync_message;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    private $wix_order_id;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $wix_order_no;

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

    public function getShopId(): ?string
    {
        return $this->shop_id;
    }

    public function setShopId(?string $shop_id): self
    {
        $this->shop_id = $shop_id;

        return $this;
    }

    public function getReceiptId(): ?string
    {
        return $this->receipt_id;
    }

    public function setReceiptId(?string $receipt_id): self
    {
        $this->receipt_id = $receipt_id;

        return $this;
    }

    public function getBuyerEmail(): ?string
    {
        return $this->buyer_email;
    }

    public function setBuyerEmail(?string $buyer_email): self
    {
        $this->buyer_email = $buyer_email;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFirstLine(): ?string
    {
        return $this->first_line;
    }

    public function setFirstLine(?string $first_line): self
    {
        $this->first_line = $first_line;

        return $this;
    }

    public function getSecondLine(): ?string
    {
        return $this->second_line;
    }

    public function setSecondLine(?string $second_line): self
    {
        $this->second_line = $second_line;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(?string $zip): self
    {
        $this->zip = $zip;

        return $this;
    }

    public function getOrderStatus(): ?string
    {
        return $this->order_status;
    }

    public function setOrderStatus(?string $order_status): self
    {
        $this->order_status = $order_status;

        return $this;
    }

    public function getFormattedAddress(): ?string
    {
        return $this->formatted_address;
    }

    public function setFormattedAddress(?string $formatted_address): self
    {
        $this->formatted_address = $formatted_address;

        return $this;
    }

    public function getCountryIso(): ?string
    {
        return $this->country_iso;
    }

    public function setCountryIso(?string $country_iso): self
    {
        $this->country_iso = $country_iso;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->payment_method;
    }

    public function setPaymentMethod(?string $payment_method): self
    {
        $this->payment_method = $payment_method;

        return $this;
    }

    public function getIsShipped(): ?string
    {
        return $this->is_shipped;
    }

    public function setIsShipped(?string $is_shipped): self
    {
        $this->is_shipped = $is_shipped;

        return $this;
    }

    public function getIsPaid(): ?string
    {
        return $this->is_paid;
    }

    public function setIsPaid(?string $is_paid): self
    {
        $this->is_paid = $is_paid;

        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function setCreatedAt(?string $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getRawData()
    {
        return $this->raw_data;
    }

    public function setRawData($raw_data)
    {
        $this->raw_data = $raw_data;

        return $this;
    }

    public function getSyncStatus()
    {
        return $this->sync_status;
    }

    public function setSyncStatus($sync_status)
    {
        $this->sync_status = $sync_status;

        return $this;
    }

    public function getSyncMessage()
    {
        return $this->sync_message;
    }

    public function setSyncMessage($sync_message)
    {
        $this->sync_message = $sync_message;

        return $this;
    }

    public function getWixOrderId()
    {
        return $this->wix_order_id;
    }

    public function setWixOrderId($wix_order_id)
    {
        $this->wix_order_id = $wix_order_id;

        return $this;
    }

    public function getWixOrderNo()
    {
        return $this->wix_order_no;
    }

    public function setWixOrderNo($wix_order_no)
    {
        $this->wix_order_no = $wix_order_no;

        return $this;
    }
}
