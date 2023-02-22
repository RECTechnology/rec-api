<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;


/**
 * Class SmsTemplates
 * @package App\Entity
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