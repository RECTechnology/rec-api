<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;


/**
 * Class SmsTemplates
 * @package App\Entity
 * @ORM\Entity()
 */
class UsersSmsLogs extends AppObject {

    /**
     * @var integer $user_id
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"admin"})
     */
    private $user_id;

    /**
     * @var string $type
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin"})
     */
    private $type;

    /**
     * @var integer $security_code
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"admin"})
     */
    private $security_code;

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     */
    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

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
     * @return int
     */
    public function getSecurityCode(): int
    {
        return $this->security_code;
    }

    /**
     * @param int $security_code
     */
    public function setSecurityCode(int $security_code): void
    {
        $this->security_code = $security_code;
    }

}