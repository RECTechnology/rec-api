<?php

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\MaxDepth;

/**
 * Class AccountChallenge
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class AccountChallenge extends AppObject
{

    /**
     * This account is who get the award
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     * @Serializer\Groups({"user"})
     * @MaxDepth(2)
     */
    private $account;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Challenge")
     * @Serializer\Groups({"user"})
     * @MaxDepth(2)
     */
    private $challenge;

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
    public function getChallenge()
    {
        return $this->challenge;
    }

    /**
     * @param mixed $challenge
     */
    public function setChallenge($challenge): void
    {
        $this->challenge = $challenge;
    }

}