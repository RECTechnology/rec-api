<?php

namespace App\FinancialApiBundle\Entity;


use App\FinancialApiBundle\Annotations\StatusProperty;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use App\FinancialApiBundle\Exception\PreconditionFailedException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Tier
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity()
 */
class Tier extends AppObject {

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
     *     targetEntity="App\FinancialApiBundle\Entity\DocumentKind",
     *     mappedBy="tiers",
     *     fetch="EXTRA_LAZY"
     * )
     * @Serializer\Groups({"user"})
     */
    private $document_kinds;

    /**
     * @ORM\OneToOne(targetEntity="App\FinancialApiBundle\Entity\Tier", inversedBy="next")
     * @Serializer\Groups({"user"})
     */
    private $previous;

    /**
     * @ORM\OneToOne(targetEntity="App\FinancialApiBundle\Entity\Tier", inversedBy="previous")
     * @Serializer\Groups({"user"})
     */
    private $next;

    public function __construct(){
        $this->document_kinds = new ArrayCollection();
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
     * @throws PreconditionFailedException
     */
    public function delDocumentKind(DocumentKind $documentKind): void
    {
        if(!$this->document_kinds->contains($documentKind)){
            throw new PreconditionFailedException("DocumentKind not related to this Tier");
        }
        $this->document_kinds->removeElement($documentKind);
    }

    /**
     * @return mixed
     */
    public function getPrevious()
    {
        return $this->previous;
    }

    /**
     * @param mixed $previous
     */
    public function setPrevious($previous): void
    {
        $this->previous = $previous;
    }

    /**
     * @return mixed
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * @param mixed $next
     */
    public function setNext($next): void
    {
        $this->next = $next;
    }


}