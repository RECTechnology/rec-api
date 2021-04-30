<?php

namespace App\FinancialApiBundle\Entity;


use App\FinancialApiBundle\Annotations\StatusProperty;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use App\FinancialApiBundle\Exception\PreconditionFailedException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class SmsTemplates
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity()
 */
class SmsTemplates extends AppObject {

    /**
     * @var string $type
     * @ORM\Column(type="string", unique=true)
     * @Serializer\Groups({"user"})
     */
    private $type;

    /**
     * @var string $body
     * @ORM\Column(type="string")
     * @Serializer\Groups({"user"})
     */
    private $body;



    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }
}