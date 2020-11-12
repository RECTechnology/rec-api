<?php

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="campaign")
 */
class Campaign extends AppObject {

    const BONISSIM_CAMPAIGN_NAME = 'Li toca al barri';
    const DEFAULT_MIN = 50;
    const DEFAULT_MAX = 1000;
    const PERCENTAGE = 15;

    public function __construct() {
        $this->accounts = new ArrayCollection();
    }

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Groups({"public"})
     */
    protected $init_date;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Groups({"public"})
     */
    protected $end_date;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    protected $name;


    /**
     * @ORM\Column(type="float")
     * @Serializer\Groups({"admin"})
     */
    private $balance;

    /**
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\Group", mappedBy="campaigns")
     * @Serializer\MaxDepth(3)
     * @Serializer\Groups({"public"})
     */
    private $accounts;


    /**
     * @ORM\Column(type="float")
     * @Serializer\Groups({"public"})
     */
    private $min=self::DEFAULT_MIN;

    /**
     * @ORM\Column(type="float")
     * @Serializer\Groups({"public"})
     */
    private $max=self::DEFAULT_MAX;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    private $campaign_account;

    /**
     * @ORM\Column(type="float")
     * @Serializer\Groups({"public"})
     */
    private $redeemable_percentage=self::PERCENTAGE;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }


    /**
     * @return mixed
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @param mixed $balance
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;
    }

    /**
     * @return \DateTime
     */
    public function getInitDate()
    {
        return $this->init_date;
    }

    /**
     * @param \DateTime $init_date
     */
    public function setInitDate(\DateTime $init_date)
    {
        $this->init_date = $init_date;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * @param \DateTime $end_date
     */
    public function setEndDate(\DateTime $end_date)
    {
        $this->end_date = $end_date;
    }
    /**
     * @return \mixed
     * @param \mixed $amount
     */
    public function getBonus($amount)
    {
        $bonus = $amount * 0.15;
        if ($this->balance >= $bonus){
            $this->balance = $this->balance - $bonus;
            return $bonus;
        }
        return 0;
    }

    /**
     * @return mixed
     */
    public function getAccounts()
    {
        return $this->accounts;
    }

    /**
     * @param mixed $accounts
     */
    public function setAccounts($accounts): void
    {
        $this->accounts = $accounts;
    }

    /**
     * @return mixed
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * @param mixed $min
     */
    public function setMin($min): void
    {
        $this->min = $min;
    }

    /**
     * @return mixed
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * @param mixed $max
     */
    public function setMax($max): void
    {
        $this->max = $max;
    }

    /**
     * @return mixed
     */
    public function getCampaignAccount()
    {
        return $this->campaign_account;
    }

    /**
     * @param mixed $campaign_account
     */
    public function setCampaignAccount($campaign_account): void
    {
        $this->campaign_account = $campaign_account;
    }

    /**
     * @return mixed
     */
    public function getRedeemablePercentage()
    {
        return $this->redeemable_percentage;
    }

    /**
     * @param mixed $redeemable_percentage
     */
    public function setRedeemablePercentage($redeemable_percentage)
    {
        $this->redeemable_percentage = $redeemable_percentage;
    }



}