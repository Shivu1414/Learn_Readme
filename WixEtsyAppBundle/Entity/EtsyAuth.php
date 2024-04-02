<?php

namespace Webkul\Modules\Wix\WixEtsyAppBundle\Entity;

use Webkul\Modules\Wix\WixEtsyAppBundle\Repository\EtsyAuthRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=Webkul\Modules\Wix\WixEtsyAppBundle\Repository\EtsyAuthRepository::class)
 * @ORM\Table(name="App_wixetsy_auth")
 */
class EtsyAuth
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $client_id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $code_challenge;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $verifier;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $auth_code;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $access_token;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $refresh_token;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $raw_data = [];

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $token_expires_in;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $etsy_user_id;

     /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $redirect_url;

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

    public function getClientId(): ?string
    {
        return $this->client_id;
    }

    public function setClientId(?string $client_id): self
    {
        $this->client_id = $client_id;

        return $this;
    }

    public function getCodeChallenge(): ?string
    {
        return $this->code_challenge;
    }

    public function setCodeChallenge(?string $code_challenge): self
    {
        $this->code_challenge = $code_challenge;

        return $this;
    }

    public function getVerifier(): ?string
    {
        return $this->verifier;
    }

    public function setVerifier(?string $verifier): self
    {
        $this->verifier = $verifier;

        return $this;
    }

    public function getAuthCode(): ?string
    {
        return $this->auth_code;
    }

    public function setAuthCode(?string $auth_code): self
    {
        $this->auth_code = $auth_code;

        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->access_token;
    }

    public function setAccessToken(?string $access_token): self
    {
        $this->access_token = $access_token;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refresh_token;
    }

    public function setRefreshToken(?string $refresh_token): self
    {
        $this->refresh_token = $refresh_token;

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

    public function getUpdatedAt(): ?int
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?int $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getTokenExpiresIn()
    {
        return $this->token_expires_in;
    }

    public function setTokenExpiresIn($token_expires_in)
    {
        $this->token_expires_in = $token_expires_in;

        return $this;
    }

    public function getEtsyUserId()
    {
        return $this->etsy_user_id;
    }

    public function setEtsyUserId($etsy_user_id)
    {
        $this->etsy_user_id = $etsy_user_id;

        return $this;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirect_url;
    }

    public function setRedirectUrl(?string $redirect_url): self
    {
        $this->redirect_url = $redirect_url;

        return $this;
    }
}
