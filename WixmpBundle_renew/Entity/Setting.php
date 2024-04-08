<?php

namespace Webkul\Modules\Wix\WixmpBundle\Entity;

use App\Entity\companyApplication;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Webkul\Modules\Wix\WixmpBundle\Repository\SettingRepository")
 * @ORM\Table(name="App_wixmp_settings")
 * @ORM\HasLifecycleCallbacks()
 */
class Setting
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=15)
     */
    private $area;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\companyApplication", inversedBy="settings")
     * @ORM\JoinColumn(nullable=false)
     */
    private $companyApplication;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $showWorkingHours;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $workingHours;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $profileDescription;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $showProfileDescription;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $showProfileGallery;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $showTopSoldProducts;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $showSellerProducts;

    /**
     * @ORM\ManyToOne(targetEntity="Webkul\Modules\Wix\WixmpBundle\Entity\Seller", cascade={"remove"})
     * @ORM\JoinColumns({
     *    @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * })
     */
    private $seller;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $paypalPayoutEmail;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $stripePayoutEmail;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $stripePayoutAccount;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $aboutUs;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $showAboutUs;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $hub;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $showHub;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $shippingLogistic;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $showShippingLogistic;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $showSellerReview;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $showDocuments;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $reviewsRecoginition;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $showReviewsRecoginition;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $awardsRecoginition;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $showAwardsRecoginition;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $documentContent;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $testimonial;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $showTestimonial;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $payoutFirstName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $payoutLastName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $payoutBankName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $payoutBankIBAN;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArea(): ?string
    {
        return $this->area;
    }

    public function setArea(string $area): self
    {
        $this->area = $area;

        return $this;
    }

    public function getCompanyApplication(): ?companyApplication
    {
        return $this->companyApplication;
    }

    public function setCompanyApplication(?companyApplication $companyApplication): self
    {
        $this->companyApplication = $companyApplication;

        return $this;
    }

    public function getShowWorkingHours(): ?bool
    {
        return $this->showWorkingHours;
    }

    public function setShowWorkingHours(?bool $showWorkingHours): self
    {
        $this->showWorkingHours = $showWorkingHours;

        return $this;
    }

    public function getWorkingHours(): ?string
    {
        return $this->workingHours;
    }

    public function setWorkingHours(?string $workingHours): self
    {
        $this->workingHours = $workingHours;

        return $this;
    }

    public function getProfileDescription(): ?string
    {
        return $this->profileDescription;
    }

    public function setProfileDescription(?string $profileDescription): self
    {
        $this->profileDescription = $profileDescription;

        return $this;
    }

    public function getShowProfileDescription(): ?bool
    {
        return $this->showProfileDescription;
    }

    public function setShowProfileDescription(?bool $showProfileDescription): self
    {
        $this->showProfileDescription = $showProfileDescription;

        return $this;
    }

    public function getShowProfileGallery(): ?bool
    {
        return $this->showProfileGallery;
    }

    public function setShowProfileGallery(?bool $showProfileGallery): self
    {
        $this->showProfileGallery = $showProfileGallery;

        return $this;
    }

    public function getShowTopSoldProducts(): ?bool
    {
        return $this->showTopSoldProducts;
    }

    public function setShowTopSoldProducts(?bool $showTopSoldProducts): self
    {
        $this->showTopSoldProducts = $showTopSoldProducts;

        return $this;
    }

    public function getShowSellerProducts(): ?bool
    {
        return $this->showSellerProducts;
    }

    public function setShowSellerProducts(?bool $showSellerProducts): self
    {
        $this->showSellerProducts = $showSellerProducts;

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

    public function getPaypalPayoutEmail(): ?string
    {
        return $this->paypalPayoutEmail;
    }

    public function setPaypalPayoutEmail(string $paypalPayoutEmail): self
    {
        $this->paypalPayoutEmail = $paypalPayoutEmail;

        return $this;
    }

    public function getAboutUs(): ?string
    {
        return $this->aboutUs;
    }

    public function setAboutUs(?string $aboutUs): self
    {
        $this->aboutUs = $aboutUs;

        return $this;
    }

    public function getHub(): ?string
    {
        return $this->hub;
    }

    public function setHub(?string $hub): self
    {
        $this->hub = $hub;

        return $this;
    }

    public function getShippingLogistic(): ?string
    {
        return $this->shippingLogistic;
    }

    public function setShippingLogistic(?string $shippingLogistic): self
    {
        $this->shippingLogistic = $shippingLogistic;

        return $this;
    }

    public function getShowAboutUs(): ?bool
    {
        return $this->showAboutUs;
    }

    public function setShowAboutUs(?bool $showAboutUs): self
    {
        $this->showAboutUs = $showAboutUs;

        return $this;
    }

    public function getShowHub(): ?bool
    {
        return $this->showHub;
    }

    public function setShowHub(?bool $showHub): self
    {
        $this->showHub = $showHub;

        return $this;
    }

    public function getShowShippingLogistic(): ?bool
    {
        return $this->showShippingLogistic;
    }

    public function setShowShippingLogistic(?bool $showShippingLogistic): self
    {
        $this->showShippingLogistic = $showShippingLogistic;

        return $this;
    }

    public function setShowSellerReview(?bool $showSellerReview): self
    {
        $this->showSellerReview = $showSellerReview;

        return $this;
    }

    public function getShowSellerReview(): ?bool
    {
        return $this->showSellerReview;
    }

    public function setShowDocuments(?bool $showDocuments): self
    {
        $this->showDocuments = $showDocuments;

        return $this;
    }

    public function getShowDocuments(): ?bool
    {
        return $this->showDocuments;
    }

    public function getShowReviewsRecoginition(): ?bool
    {
        return $this->showReviewsRecoginition;
    }

    public function setShowReviewsRecoginition(?bool $showReviewsRecoginition): self
    {
        $this->showReviewsRecoginition = $showReviewsRecoginition;

        return $this;
    }

    public function getReviewsRecoginition(): ?string
    {
        return $this->reviewsRecoginition;
    }

    public function setReviewsRecoginition(?string $reviewsRecoginition): self
    {
        $this->reviewsRecoginition = $reviewsRecoginition;

        return $this;
    }

    public function getShowAwardsRecoginition(): ?bool
    {
        return $this->showAwardsRecoginition;
    }

    public function setShowAwardsRecoginition(?bool $showAwardsRecoginition): self
    {
        $this->showAwardsRecoginition = $showAwardsRecoginition;

        return $this;
    }

    public function getAwardsRecoginition(): ?string
    {
        return $this->awardsRecoginition;
    }

    public function setAwardsRecoginition(?string $awardsRecoginition): self
    {
        $this->awardsRecoginition = $awardsRecoginition;

        return $this;
    }

    public function getDocumentContent(): ?string
    {
        return $this->documentContent;
    }

    public function setDocumentContent(?string $documentContent): self
    {
        $this->documentContent = $documentContent;

        return $this;
    }

    public function getShowTestimonial(): ?bool
    {
        return $this->showTestimonial;
    }

    public function setShowTestimonial(?bool $showTestimonial): self
    {
        $this->showTestimonial = $showTestimonial;

        return $this;
    }

    public function getTestimonial(): ?string
    {
        return $this->testimonial;
    }

    public function setTestimonial(?string $testimonial): self
    {
        $this->testimonial = $testimonial;

        return $this;
    }

    public function getStripePayoutEmail(): ?string
    {
        return $this->stripePayoutEmail;
    }

    public function setStripePayoutEmail(string $stripePayoutEmail): self
    {
        $this->stripePayoutEmail = $stripePayoutEmail;

        return $this;
    }
    
    public function getStripePayoutAccount(): ?string
    {
        return $this->stripePayoutAccount;
    }

    public function setStripePayoutAccount(string $stripePayoutAccount): self
    {
        $this->stripePayoutAccount = $stripePayoutAccount;

        return $this;
    }

    public function getPayoutFirstName(): ?string {

        return $this->payoutFirstName;

    }

    public function setPayoutFirstName(string $payoutFirstName): self {

        $this->payoutFirstName = $payoutFirstName;
        return $this;

    }

    public function getPayoutLastName(): ?string {

        return $this->payoutLastName;

    }

    public function setPayoutLastName(string $payoutLastName): self {

        $this->payoutLastName = $payoutLastName;
        return $this;

    }

    public function getPayoutBankName(): ?string {

        return $this->payoutBankName;

    }

    public function setPayoutBankName(string $payoutBankName): self {

        $this->payoutBankName = $payoutBankName;
        return $this;

    }
    
    public function getPayoutBankIBAN(): ?string {

        return $this->payoutBankIBAN;

    }

    public function setPayoutBankIBAN(string $payoutBankIBAN): self {

        $this->payoutBankIBAN = $payoutBankIBAN;
        return $this;

    }
}
