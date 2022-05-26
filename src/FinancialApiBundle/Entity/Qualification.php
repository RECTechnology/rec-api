<?php

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\MaxDepth;

/**
 * Class Qualification
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class Qualification extends AppObject
{

    public const STATUS_PENDING = 'pending';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_IGNORED = 'ignored';

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Serializer\Groups({"user"})
     */
    private $value;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"user"})
     * @Assert\Choice(
     *     choices={"pending", "reviewed", "ignored"},
     *     message="Invalid parameter status, valid options: pending, reviewed, ignored"
     * )
     */
    private $status;

    /**
     * This account is who makes the review
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     * @Serializer\Groups({"user"})
     * @MaxDepth(1)
     */
    private $reviewer;

    /**
     * This account is who is reviewed
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     * @Serializer\Groups({"user"})
     * @MaxDepth(1)
     */
    private $account;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Badge")
     * @Serializer\Groups({"user"})
     * @MaxDepth(1)
     */
    private $badge;

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
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
    public function getReviewer()
    {
        return $this->reviewer;
    }

    /**
     * @param mixed $reviewer
     */
    public function setReviewer($reviewer): void
    {
        $this->reviewer = $reviewer;
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
    public function getBadge()
    {
        return $this->badge;
    }

    /**
     * @param mixed $badge
     */
    public function setBadge($badge): void
    {
        $this->badge = $badge;
    }

}