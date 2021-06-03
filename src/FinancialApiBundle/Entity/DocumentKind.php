<?php

namespace App\FinancialApiBundle\Entity;

use App\FinancialApiBundle\Exception\PreconditionFailedException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\MaxDepth;                                                                                                                                                                                                                                 
use JMS\Serializer\Annotation as Serializer;

/**
 * Class DocumentKind
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity()
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 */
class DocumentKind extends AppObject {

    /**
     * @var string $name
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     * @Expose
     */
    public $name;

    /**
     * @var string $description
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"public"})
     * @Expose
     */
    public $description;

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\Document", mappedBy="kind")
     * @Serializer\Groups({"admin"})
     * @Serializer\MaxDepth(1)
     */
    protected $documents;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="App\FinancialApiBundle\Entity\Tier",
     *     inversedBy="document_kinds",
     *     fetch="EXTRA_LAZY"
     * )
     * @Serializer\Groups({"admin"})
     * @Serializer\MaxDepth(1)
     */
    protected $tiers;

    /**
     * @ORM\Column(type="boolean")
     * @Serializer\Groups({"public"})
     * @Expose
     */
    public $is_user_document;


    public function __construct()
    {
        $this->documents = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * @param mixed $documents
     */
    public function setDocuments($documents): void
    {
        $this->documents = $documents;
    }

    /**
     * @return mixed
     */
    public function getTiers()
    {
        return $this->tiers;
    }

    /**
     * @param Tier $tier
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function addTier(Tier $tier, $recursive = true): void
    {
        if($this->tiers->contains($tier)){
            throw new PreconditionFailedException("Tier already related to this DocumentKind");
        }
        $this->tiers []= $tier;
        if($recursive) $tier->addDocumentKind($this, false);
    }

    /**
     * @param Tier $tier
     * @param bool $recursive
     */
    public function delTier(Tier $tier, $recursive = true): void
    {
        if(!$this->tiers->contains($tier)){
            throw new PreconditionFailedException("Tier not related to this DocumentKind");
        }
        $this->tiers->removeElement($tier);
        if($recursive) $tier->delDocumentKind($this, false);
    }

    /**
     * @return mixed
     */
    public function getIsUserDocument()
    {
        return $this->is_user_document;
    }

    /**
     * @param mixed $is_user_document
     */
    public function setIsUserDocument($is_user_document): void
    {
        $this->is_user_document = $is_user_document;
    }

}