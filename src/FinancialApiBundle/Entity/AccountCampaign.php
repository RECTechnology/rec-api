<?php

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class AccountCampaign extends AppObject
{
    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Campaign")
     */
    protected $campaign;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     */
    protected $account;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    protected $acumulated_bonus;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    protected $spent_bonus;

    /**
     * @return mixed
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @param mixed $campaign
     */
    public function setCampaign($campaign): void
    {
        $this->campaign = $campaign;
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
    public function getAcumulatedBonus()
    {
        return $this->acumulated_bonus;
    }

    /**
     * @param mixed $acumulated_bonus
     */
    public function setAcumulatedBonus($acumulated_bonus): void
    {
        $this->acumulated_bonus = $acumulated_bonus;
    }

    /**
     * @return mixed
     */
    public function getSpentBonus()
    {
        return $this->spent_bonus;
    }

    /**
     * @param mixed $spent_bonus
     */
    public function setSpentBonus($spent_bonus): void
    {
        $this->spent_bonus = $spent_bonus;
    }

}