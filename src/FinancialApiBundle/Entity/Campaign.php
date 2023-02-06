<?php

namespace App\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity()
 * @ORM\Table(name="campaign")
 */
class Campaign extends AppObject {

    const BONISSIM_CAMPAIGN_NAME = 'LI TOCA AL BARRI';
    const CULTURE_CAMPAIGN_NAME = 'REC Cultural';
    const DEFAULT_MIN = 50;
    const DEFAULT_MAX = 1000;
    const PERCENTAGE = 15;
    const STATUS_CREATED = 'created';
    const STATUS_ACTIVE = 'active';
    const STATUS_FINISHED = 'finished';

    public function __construct() {
        $this->accounts = new ArrayCollection();
        $this->code = $this->generateRandomCode(5);
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
     * @ORM\Column(type="boolean")
     * @Serializer\Groups({"public"})
     */
    protected $bonus_enabled=true;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    protected $name;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    protected $tos;

    /**
     * @ORM\Column(type="float")
     * @Serializer\Groups({"admin"})
     */
    private $balance;

    /**
     * @ORM\ManyToMany(targetEntity="App\FinancialApiBundle\Entity\Group", mappedBy="campaigns")
     * @Serializer\MaxDepth(3)
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
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    protected $image_url='';

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"public"})
     */
    protected $video_promo_url='';

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"public"})
     */
    protected $promo_url;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"public"})
     */
    protected $code;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"public"})
     */
    protected $url_tos;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     * @Serializer\Groups({"admin"})
     */
    protected $bonus_ending_threshold;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Serializer\Groups({"public"})
     */
    protected $ending_alert = false;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"public"})
     */
    protected $version =2;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Groups({"public"})
     */
    protected $status = self::STATUS_CREATED;

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

    /**
     * @return mixed
     */
    public function getImageUrl()
    {
        return $this->image_url;
    }

    /**
     * @param mixed $image_url
     */
    public function setImageUrl($image_url): void
    {
        $this->image_url = $image_url;
    }

    /**
     * @return mixed
     */
    public function getVideoPromoUrl()
    {
        return $this->video_promo_url;
    }

    /**
     * @param mixed $video_promo_url
     */
    public function setVideoPromoUrl($video_promo_url): void
    {
        $this->video_promo_url = $video_promo_url;
    }

    /**
     * @return mixed
     */
    public function getTos()
    {
        return $this->tos;
    }

    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     */
    public function setCode($code): void
    {
        $this->code = $code;
    }

    /**
     * @param mixed $tos
     */
    public function setTos($tos): void
    {
        $this->tos = $tos;
    }

    /**
     * @return mixed
     */
    public function getUrlTos()
    {
        return $this->url_tos;
    }

    /**
     * @param mixed $url_tos
     */
    public function setUrlTos($url_tos): void
    {
        $this->url_tos = $url_tos;
    }

    /**
     * @return mixed
     */
    public function isBonusEnabled(): bool
    {
        return $this->bonus_enabled;
    }

    /**
     * @param mixed $bonus_enabled
     */
    public function setBonusEnabled($bonus_enabled): void
    {
        $this->bonus_enabled = $bonus_enabled;
    }

    /**
     * @return mixed
     */
    public function getBonusEndingThreshold()
    {
        return $this->bonus_ending_threshold;
    }

    /**
     * @param mixed $bonus_ending_threshold
     */
    public function setBonusEndingThreshold($bonus_ending_threshold): void
    {
        $this->bonus_ending_threshold = $bonus_ending_threshold;
    }

    /**
     * @return mixed
     */
    public function getEndingAlert()
    {
        return $this->ending_alert;
    }

    /**
     * @param mixed $ending_alert
     */
    public function setEndingAlert($ending_alert): void
    {
        $this->ending_alert = $ending_alert;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param int $version
     */
    public function setVersion(int $version): void
    {
        $this->version = $version;
    }
    /**
     * @return string
     */
    public function getPromoUrl(): string
    {
        return $this->promo_url;
    }

    /**
     * @param string $promo_url
     */
    public function setPromoUrl(string $promo_url): void
    {
        $this->promo_url = $promo_url;
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

    private function generateRandomCode($length = 10)
    {
        return substr(str_shuffle(str_repeat($x = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / strlen($x)))), 1, $length);
    }
}
