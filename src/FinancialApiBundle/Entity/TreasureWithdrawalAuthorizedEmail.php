<?php

namespace App\FinancialApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Class TreasureWithdrawalAuthorizedEmail
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity()
 */
class TreasureWithdrawalAuthorizedEmail extends AppObject {

    /**
     * @ORM\OneToMany(targetEntity="TreasureWithdrawal", mappedBy="email")
     * @Serializer\Groups({"admin"})
     */
    private $withdrawals;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Email()
     * @Serializer\Groups({"admin"})
     */
    private $email;

    /**
     * TreasureWithdrawalAuthorizedEmail constructor.
     */
    public function __construct()
    {
        $this->withdrawals = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getWithdrawals()
    {
        return $this->withdrawals;
    }

    /**
     * @param mixed $withdrawals
     */
    public function setWithdrawals($withdrawals): void
    {
        $this->withdrawals = $withdrawals;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

}