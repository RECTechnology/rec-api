<?php

namespace App\FinancialApiBundle\Entity;
use Symfony\Component\HttpKernel\Exception\HttpException;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * @ORM\Entity
 * @ExclusionPolicy("all")
 */
class Offer{

    const OFFER_TYPE_CLASSIC = "classic";
    const OFFER_TYPE_PERCENTAGE = "percentage";
    const OFFER_TYPE_FREE = "free";

    const OFFER_TYPES_ALL = [self::OFFER_TYPE_CLASSIC, self::OFFER_TYPE_FREE, self::OFFER_TYPE_PERCENTAGE];

    public function __construct(){
        $this->created = new \DateTime();
        $this->start = new \DateTime();
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     * @Groups({"public"})
     */
    protected $id;

    /**
     * @ORM\Column(type="datetime")
     * @Expose
     * @Groups({"public"})
     */
    private $created;

    /**
     * @ORM\Column(type="datetime")
     * @Expose
     * @Groups({"public"})
     */
    private $start;

    /**
     * @ORM\Column(type="datetime")
     * @Expose
     * @Groups({"public"})
     */
    private $end;

    /**
     * @ORM\ManyToOne(targetEntity="App\FinancialApiBundle\Entity\Group")
     * @Groups({"public"})
     */
    private $company;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Expose
     * @Groups({"public"})
     */
    private $discount;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     * @Groups({"public"})
     */
    private $description;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Expose
     * @Groups({"public"})
     */
    private $image;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     * @Groups({"public"})
     */
    private $active = false;


    /**
     * @ORM\Column(type="string")
     * @Assert\Choice(choices=self::OFFER_TYPES_ALL, message="Choose a valid type")
     * @Expose
     * @Groups({"public"})
     */
    private $type;


    /**
     * @ORM\Column(type="float", nullable=true)
     * @Expose
     * @Groups({"public"})
     */
    private $initial_price;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Expose
     * @Groups({"public"})
     */
    private $offer_price;


    /**
     * Returns the user unique id.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @return mixed
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param mixed $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * @return mixed
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @param mixed $end
     */
    public function setEnd($end)
    {
        $this->end = $end;
    }

    /**
     * @return mixed
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * @param mixed $discount
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;
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
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param mixed $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return mixed
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param mixed $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    public function isActive(){
        $now = strtotime("now");
        return date_timestamp_get($this->getStart()) > $now && date_timestamp_get($this->getEnd()) < $now;
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

    /**
     * @return mixed
     */
    public function getInitialPrice()
    {
        return $this->initial_price;
    }

    /**
     * @param mixed $initial_price
     */
    public function setInitialPrice($initial_price): void
    {
        $this->initial_price = $initial_price;
    }

    /**
     * @return mixed
     */
    public function getOfferPrice()
    {
        return $this->offer_price;
    }

    /**
     * @param mixed $offer_price
     */
    public function setOfferPrice($offer_price): void
    {
        $this->offer_price = $offer_price;
    }
}