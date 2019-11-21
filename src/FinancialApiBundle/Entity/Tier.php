<?php

namespace App\FinancialApiBundle\Entity;


use App\FinancialApiBundle\Annotations\StatusProperty;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
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
     * @var string $code
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"user"})
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="App\FinancialApiBundle\Entity\DocumentKind", mappedBy="tier")
     * @Serializer\Groups({"user"})
     */
    private $document_kinds;

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
     * @param mixed $document_kinds
     */
    public function setDocumentKinds($document_kinds): void
    {
        $this->document_kinds = $document_kinds;
    }


}