<?php

namespace App\FinancialApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

use Symfony\Component\Validator\Constraints AS Assert;

/**
 * Class Challenge
 * @package App\FinancialApiBundle\Entity
 * @ORM\Entity
 */
class Challenge extends AppObject
{
    public const ACTION_TYPE_BUY = "buy";
    public const ACTION_TYPE_QUALIFICATION = "qualification";
    public const ACTION_TYPE_RECHARGE = "recharge";
    public const ACTION_TYPE_SEND = "send";

    public const ACTIONS = [
        self::ACTION_TYPE_BUY,
        self::ACTION_TYPE_QUALIFICATION,
        self::ACTION_TYPE_RECHARGE,
        self::ACTION_TYPE_SEND
    ];

    public const TYPE_CAMPAIGN = "campaign";
    public const TYPE_CHALLENGE = "challenge";

    public const TYPES = [
        self::TYPE_CAMPAIGN,
        self::TYPE_CHALLENGE
    ];

    public const STATUS_DRAFT = "draft";
    public const STATUS_SCHEDULED = "scheduled";
    public const STATUS_OPEN = "open";
    public const STATUS_CLOSED = "closed";

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_CLOSED,
        self::STATUS_OPEN,
        self::STATUS_SCHEDULED
    ];

    public function __construct(){
        $this->activities = new ArrayCollection();
    }
    /**
     * @ORM\Column(type="string", length=60)
     * @Serializer\Groups({"admin", "user"})
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     * @Serializer\Groups({"admin", "user"})
     */
    private $description;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin", "user"})
     * @Assert\Choice(
     *     choices=Challenge::ACTIONS,
     *     message="Choose a valid action"
     * )
     */
    private $action;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin", "user"})
     * @Assert\Choice(
     *     choices=Challenge::TYPES,
     *     message="Choose a valid type"
     * )
     */
    private $type;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"admin", "user"})
     * @Assert\Choice(
     *     choices=Challenge::STATUSES,
     *     message="Choose a valid status"
     * )
     */
    private $status = self::STATUS_SCHEDULED;

    /**
     * How many times the action needs to be repeated
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"admin", "user"})
     */
    private $threshold;

    /**
     * Minimum amount to be reached
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"admin", "user"})
     */
    private $amount_required;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Groups({"admin", "user"})
     */
    private $start_date;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Groups({"admin", "user"})
     * @Assert\Expression("this.getFinishDate() >= this.getStartDate()", message="Finish date must be greater than Start Date")
     */
    private $finish_date;

    /**
     * @ORM\Column(type="string")
     * @Assert\Url(message="This values is not a valid url")
     * @Serializer\Groups({"admin", "user"})
     */
    private $cover_image;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="App\FinancialApiBundle\Entity\Activity",
     *     fetch="EXTRA_LAZY"
     * )
     * @Serializer\MaxDepth(2)
     * @Serializer\Groups({"admin"})
     */
    private $activities;

    /**
     * One Challenge has One TokenReward or null.
     * @ORM\OneToOne(targetEntity="App\FinancialApiBundle\Entity\TokenReward")
     * @Serializer\Groups({"admin", "user"})
     */
    private $token_reward;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     * @Serializer\Groups({"admin"})
     */
    private $owner;

    public function addActivity(Activity $activity){
        $this->activities[] = $activity;
    }

    public function getActivities(){
        return $this->activities;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param mixed $action
     */
    public function setAction($action): void
    {
        $this->action = $action;
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
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * @param mixed $threshold
     */
    public function setThreshold($threshold): void
    {
        $this->threshold = $threshold;
    }

    /**
     * @return mixed
     */
    public function getAmountRequired()
    {
        return $this->amount_required;
    }

    /**
     * @param mixed $amount_required
     */
    public function setAmountRequired($amount_required): void
    {
        $this->amount_required = $amount_required;
    }

    /**
     * @return mixed
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * @param mixed $start_date
     */
    public function setStartDate($start_date): void
    {
        $this->start_date = $start_date;
    }

    /**
     * @return mixed
     */
    public function getFinishDate()
    {
        return $this->finish_date;
    }

    /**
     * @param mixed $finish_date
     */
    public function setFinishDate($finish_date): void
    {
        $this->finish_date = $finish_date;
    }

    /**
     * @return mixed
     */
    public function getCoverImage()
    {
        return $this->cover_image;
    }

    /**
     * @param mixed $cover_image
     */
    public function setCoverImage($cover_image): void
    {
        $this->cover_image = $cover_image;
    }

    /**
     * @return mixed
     */
    public function getTokenReward()
    {
        return $this->token_reward;
    }

    /**
     * @param mixed $token_reward
     */
    public function setTokenReward($token_reward): void
    {
        $this->token_reward = $token_reward;
    }

    /**
     * @return mixed
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param mixed $owner
     */
    public function setOwner(Group $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

}