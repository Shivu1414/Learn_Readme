<?php

namespace Webkul\Modules\Wix\WixmpBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Webkul\Modules\Wix\WixmpBundle\Repository\CollectionsRepository")
 * @ORM\Table(name="App_wixmp_collections")
 */
class Collections
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
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $_collectionId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Company")
     * @ORM\JoinColumn(nullable=false)
     */
    private $company;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $comission;

    public function __constuct()
    {
        $this->timestamp = time();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCollectionId(): ?string
    {
        return $this->_collectionId;
    }

    public function setCollectionId(string $_collectionId): self
    {
        $this->_collectionId = $_collectionId;

        return $this;
    }

    public function getCompany()
    {
        return $this->company;
    }

    public function setCompany($company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getComission(): ?float
    {
        return $this->comission;
    }

    public function setComission(?float $comission): self
    {
        $this->comission = $comission;

        return $this;
    }
}
