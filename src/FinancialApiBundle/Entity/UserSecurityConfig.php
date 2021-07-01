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
class UserSecurityConfig extends AppObject {

    const USER_SECURITY_CONFIG_TYPE_PIN_FAILURES = 'pin_failures';
    const USER_SECURITY_CONFIG_TYPE_PASSWORD_FAILURES = 'password_failures';
    const USER_SECURITY_CONFIG_TYPE_SMS_VALIDATE_PHONE = 'sms_validate_phone';
    const USER_SECURITY_CONFIG_TYPE_SMS_FORGET_PASSWORD = 'sms_forget_password';
    const USER_SECURITY_CONFIG_TYPE_SMS_CHANGE_PIN = 'sms_change_pin';
    const USER_SECURITY_CONFIG_TYPE_SMS_CHANGE_PASSWORD = 'sms_change_password';
    const USER_SECURITY_CONFIG_TYPE_SMS_UNLOCK_USER = 'sms_unlock_user';

    /**
     * @var string $type
     * @ORM\Column(type="string", unique=true)
     * @Serializer\Groups({"user"})
     */
    private $type;

    /**
     * @var integer $max_attempts
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"user"})
     */
    private $max_attempts;

    /**
     * @var integer $time_range
     * @ORM\Column(type="integer", nullable=true)
     * @Serializer\Groups({"user"})
     */
    private $time_range;

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
    public function getMaxAttempts(): int
    {
        return $this->max_attempts;
    }

    /**
     * @param int $max_attempts
     */
    public function setMaxAttempts(int $max_attempts): void
    {
        $this->max_attempts = $max_attempts;
    }

    /**
     * @return mixed
     */
    public function getTimeRange()
    {
        return $this->time_range;
    }

    /**
     * @param mixed $time_range
     */
    public function setTimeRange(int $time_range): void
    {
        $this->time_range = $time_range;
    }




}