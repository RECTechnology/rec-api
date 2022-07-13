<?php

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\MaxDepth;

/**
 * Class AccountAward
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class AccountAward extends AppObject
{

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"public"})
     */
    private $score;

    /**
     * This account is who get the award
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     * @Serializer\Groups({"user"})
     * @MaxDepth(1)
     */
    private $account;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Award")
     * @Serializer\Groups({"user"})
     * @MaxDepth(1)
     */
    private $award;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"public"})
     */
    private $level = 0;

    /**
     * @return mixed
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param mixed $score
     */
    public function setScore($score): void
    {
        $this->score = $score;
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
    public function getAward()
    {
        return $this->award;
    }

    /**
     * @param mixed $award
     */
    public function setAward($award): void
    {
        $this->award = $award;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

}