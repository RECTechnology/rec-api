<?php

namespace Telepay\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * @ORM\Entity
 * @ORM\Table(name="KYC")
 * @ExclusionPolicy("all")
 */
class KYC {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     */
    private $id;


    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $email;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $phone;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $image_front;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $image_back;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $first_transaction;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $card_info;

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
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param mixed $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return mixed
     */
    public function getImageFront()
    {
        return $this->image_front;
    }

    /**
     * @param mixed $image_front
     */
    public function setImageFront($image_front)
    {
        $this->image_front = $image_front;
    }

    /**
     * @return mixed
     */
    public function getImageBack()
    {
        return $this->image_back;
    }

    /**
     * @param mixed $image_back
     */
    public function setImageBack($image_back)
    {
        $this->image_back = $image_back;
    }

    /**
     * @return mixed
     */
    public function getFirstTransaction()
    {
        return $this->first_transaction;
    }

    /**
     * @param mixed $first_transaction
     */
    public function setFirstTransaction($first_transaction)
    {
        $this->first_transaction = $first_transaction;
    }

    /**
     * @return mixed
     */
    public function getCardInfo()
    {
        return $this->card_info;
    }

    /**
     * @param mixed $card_info
     */
    public function setCardInfo($card_info)
    {
        $this->card_info = $card_info;
    }
}