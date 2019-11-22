<?php

namespace App\FinancialApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class DocumentKind
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity()
 */
class DocumentKind extends AppObject {

    const DOCTYPE_LW_ID = 0;
    const DOCTYPE_LW_PROOF_OF_ADDRESS = 1;

    /**
     * @var string $name
     * @ORM\Column(type="string")
     * @Serializer\Groups({"user"})
     */
    private $name;

    /**
     * @var string $type
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"user"})
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\Document", mappedBy="kind")
     * @Serializer\Groups({"admin"})
     */
    private $documents;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Tier", inversedBy="document_kinds")
     * @Serializer\Groups({"admin"})
     */
    private $tier;


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