<?php

namespace App\Entity;


use App\Exception\PreconditionFailedException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Tier
 * @package App\Entity
 * @ORM\Entity()
 */
class Tier extends AppObject {

    const KYC_LEVELS = ['KYC0', 'KYC1', 'KYC2'];

    /**
     * @var string $code
     * @ORM\Column(type="string", unique=true)
     * @Serializer\Groups({"user"})
     */
    private $code;

    /**
     * @var string $description
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"user"})
     */
    private $description;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="App\Entity\DocumentKind",
     *     mappedBy="tiers",
     *     fetch="EXTRA_LAZY"
     * )
     * @Serializer\MaxDepth(2)
     * @Serializer\Groups({"user"})
     */
    private $document_kinds;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Tier", inversedBy="children")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @Serializer\MaxDepth(2)
     * @Serializer\Groups({"user"})
     */
    private $parent;


    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Tier", mappedBy="parent")
     * @Serializer\MaxDepth(2)
     * @Serializer\Groups({"user"})
     */
    private $children;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $max_out = 0;


    public function __construct(){
        $this->document_kinds = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
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
    public function getDocumentKinds()
    {
        return $this->document_kinds;
    }

    /**
     * @param DocumentKind $documentKind
     * @param bool $recursive
     * @throws PreconditionFailedException
     */
    public function addDocumentKind(DocumentKind $documentKind, $recursive = true): void
    {
        if($this->document_kinds->contains($documentKind)){
            throw new PreconditionFailedException("DocumentKind already related to this Tier");
        }
        $this->document_kinds []= $documentKind;
        if($recursive) $documentKind->addTier($this, false);
    }

    /**
     * @param DocumentKind $documentKind
     * @param bool $recursive
     */
    public function delDocumentKind(DocumentKind $documentKind, $recursive = true): void
    {
        if(!$this->document_kinds->contains($documentKind)){
            throw new PreconditionFailedException("DocumentKind not related to this Tier");
        }
        $this->document_kinds->removeElement($documentKind);
        if($recursive) $documentKind->delTier($this, false);
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param mixed $parent
     */
    public function setParent($parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return mixed
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param mixed $children
     */
    public function setChildren($children): void
    {
        $this->children = $children;
    }

    /**
     * @return mixed
     */
    public function getMaxOut()
    {
        return $this->max_out;
    }

    /**
     * @param mixed $max_out
     */
    public function setMaxOut($max_out): void
    {
        $this->max_out = $max_out;
    }

}