<?php

namespace Webkul\Modules\Wix\WixmpBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="Webkul\Modules\Wix\WixmpBundle\Repository\SellerRepository")
 * @ORM\Table(name="App_wixmp_seller")
 *
 * @UniqueEntity(
 *     fields={"company", "email"},
 *     errorPath="email",
 *     message="This email is already in use."
 * )
 */
class Seller
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
    private $seller;

    /**
     * @ORM\Column(type="string", length=1)
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Company")
     * @ORM\JoinColumn(nullable=false)
     */
    private $company;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $address;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $address2;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $state;

    /**
     * @ORM\Column(type="string", length=2)
     */
    private $country;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $zipcode;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phone;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $logo;

    /**
     * @ORM\ManyToOne(targetEntity="Webkul\Modules\Wix\WixmpBundle\Entity\SellerPlan")
     * @ORM\JoinColumn(nullable=false)
     */
    private $currentPlan;

    /**
     * @ORM\OneToMany(targetEntity="Webkul\Modules\Wix\WixmpBundle\Entity\Products", mappedBy="seller")
     */
    private $products;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $allowedCategories;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $allowedCustomerDetails;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $vatNumber;

     /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sellerType;

    /**
     * @ORM\Column(type="integer",nullable=true)
     */
    private $brandId;

    /**
     * @ORM\Column(type="integer")
     */
    private $expireAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="text",nullable=true)
     */
    private $custom_fields;

    /**
     * @ORM\Column(type="text",nullable=true)
     */
    private $password;

    /**
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    private $isArchieved;

    public function __construct()
    {
        $this->expireAt = time();
        $this->status = 'N';
        $this->updatedAt = time();
        $this->createdAt = time();
        $this->sellerAnswers = new ArrayCollection();
        $this->sellerReview = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getSeller(): ?string
    {
        return $this->seller;
    }

    public function setSeller(string $seller): self
    {
        $this->seller = $seller;

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

    public function getCompany()
    {
        return $this->company;
    }

    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getAddress2(): ?string
    {
        return $this->address2;
    }

    public function setAddress2(?string $address2): self
    {
        $this->address2 = $address2;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getZipcode(): ?string
    {
        return $this->zipcode;
    }

    public function setZipcode(string $zipcode): self
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function getCurrentPlan()
    {
        return $this->currentPlan;
    }

    public function setCurrentPlan($currentPlan)
    {
        $this->currentPlan = $currentPlan;

        return $this;
    }

    /**
     * @return Collection|Products[]
     */
    public function getProducts()
    {
        return $this->products;
    }

    public function addProduct($product)
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
            $product->setSeller($this);
        }

        return $this;
    }

    public function removeProduct($product)
    {
        if ($this->products->contains($product)) {
            $this->products->removeElement($product);
            // set the owning side to null (unless already changed)
            if ($product->getSeller() === $this) {
                $product->setSeller(null);
            }
        }

        return $this;
    }

    public function getExpireAt(): ?int
    {
        return $this->expireAt;
    }

    public function setExpireAt(int $expireAt): self
    {
        $this->expireAt = $expireAt;

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
    public function getAllowedCategories()
    {
        if (empty($this->allowedCategories)) {
            return [];
        }
        return explode(',',$this->allowedCategories);
    }

    public function setAllowedCategories($allowedCategories): self
    {
        if (is_array($allowedCategories)) {
            $this->allowedCategories = implode(',',$allowedCategories);
        } else {
            $this->allowedCategories = $allowedCategories;
        }
        return $this;
    }

    public function getAllowedCustomerDetails()
    {
        if (empty($this->allowedCustomerDetails)) {
            return [];
        }
        return explode(',',$this->allowedCustomerDetails);
    }

    public function setAllowedCustomerDetails($allowedCustomerDetails): self
    {
        if (is_array($allowedCustomerDetails)) {
            $this->allowedCustomerDetails = implode(',',$allowedCustomerDetails);
        } else {
            $this->allowedCustomerDetails = $allowedCustomerDetails;
        }
        return $this;
    }

    public function getBrandId(): ?int
    {
        return $this->brandId;
    }

    public function setBrandId(int $brandId): self
    {
        $this->brandId = $brandId;

        return $this;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): self
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    public function getSellerType(): ?string
    {
        return $this->sellerType;
    }

    public function setSellerType(?string $sellerType): self
    {
        $this->sellerType = $sellerType;

        return $this;
    }

    /**
     * @return Collection|SellerAnswers[]
     */
    public function getSellerAnswers(): Collection
    {
        return $this->sellerAnswers;
    }

    public function addSellerAnswer($sellerAnswer): self
    {
        if (!$this->sellerAnswers->contains($sellerAnswer)) {
            $this->sellerAnswers[] = $sellerAnswer;
            $sellerAnswer->setSeller($this);
        }

        return $this;
    }

    public function removeSellerAnswer($sellerAnswer): self
    {
        if ($this->sellerAnswers->removeElement($sellerAnswer)) {
            // set the owning side to null (unless already changed)
            if ($sellerAnswer->getSeller() === $this) {
                $sellerAnswer->setSeller(null);
            }
        }

        return $this;
    }

    public function getAnswered(): ?int
    {
        return $this->answered;
    }

    public function setAnswered(int $answered): self
    {
        $this->answered = $answered;

        return $this;
    }

    public function getCustomFields()
    {
        return json_decode($this->custom_fields);
    }

    public function setCustomFields($custom_fields): self
    {
        $this->custom_fields = json_encode($custom_fields);

        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function getIsArchieved()
    {
        return $this->isArchieved;
    }

    public function setIsArchieved($isArchieved)
    {
        $this->isArchieved = $isArchieved;

        return $this;
    }
}
