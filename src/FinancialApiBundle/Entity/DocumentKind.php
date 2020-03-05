<?php

namespace App\FinancialApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
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
     * @Serializer\Groups({"user"})
     */
    protected $name;

    /**
     * @var string $type
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"user"})
     */
    protected $description;

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\Document", mappedBy="kind")
     * @Serializer\Groups({"admin"})
     * @Serializer\MaxDepth(1)
     */
    protected $documents;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Tier", inversedBy="document_kinds")
     * @Serializer\Groups({"admin"})
     * @Serializer\MaxDepth(1)
     */
    protected $tier;


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
    public function getTier()
    {
        return $this->tier;
    }

    /**
     * @param mixed $tier
     */
    public function setTier($tier): void
    {
        $this->tier = $tier;
    }

}