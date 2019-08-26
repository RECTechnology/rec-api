<?php
/**
 *  Author: Lluis Santos
 *  Date: 24 Aug 2019
 */

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Mailing
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class MailingDelivery extends AppObject {

    const STATUS_CREATED = "created";
    const STATUS_SCHEDULED = "scheduled";
    const STATUS_SENT = "sent";
    const STATUS_CANCELLED = "cancelled";
    const STATUS_ERRORED = "errored";

    /**
     * @ORM\Column(type="string")
     * @Assert\Regex(pattern="^(created|scheduled|sent|cancelled|errored)$")
     * @Groups({"admin"})
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     * @Groups({"admin"})
     */
    private $account;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Mailing", inversedBy="deliveries")
     * @Groups({"admin"})
     */
    private $mailing;

    /**
     * Activity constructor.
     */
    public function __construct() {
        $this->status = self::STATUS_CREATED;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param mixed $account
     */
    public function setAccount($account): void
    {
        $this->account = $account;
    }

    /**
     * @return mixed
     */
    public function getMailing()
    {
        return $this->mailing;
    }

    /**
     * @param mixed $mailing
     */
    public function setMailing($mailing): void
    {
        $this->mailing = $mailing;
    }

}