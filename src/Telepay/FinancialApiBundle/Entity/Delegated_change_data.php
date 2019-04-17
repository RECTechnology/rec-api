<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace Telepay\FinancialApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * @ORM\Entity
 * @ORM\Table(name="Delegated_change_data")
 * @ExclusionPolicy("all")
 */
class Delegated_change_data{


    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\Delegated_change")
     */
    private $delegated_change;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\Group")
     */
    private $user;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\Group")
     */
    private $commerce;

    /**
     * @return mixed
     */
    public function getDelegatedChange()
    {
        return $this->delegated_change;
    }

    /**
     * @param mixed $delegated_change
     */
    public function setDelegatedChange($delegated_change)
    {
        $this->delegated_change = $delegated_change;
    }




    /**
     * @param mixed $delegated_change
     */
    public function setGroup($delegated_change)
    {
        $this->delegated_change = $delegated_change;
    }



    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        if($user->hasRole('ROLE_COMPANY')){
            throw new Exception("Expect a user not a commerce!");
        }else{
            $this->user = $user;
        }
    }

    /**
     * @return mixed
     */
    public function getCommerce()
    {
        return $this->commerce;
    }

    /**
     * @param mixed $commerce
     */
    public function setCommerce($commerce)
    {
        if($commerce->hasRole('ROLE_COMPANY')){
            $this->commerce = $commerce;
        }else{
            throw new Exception("Expect a commerce not a user!");

        }

    }

    /**
     * @ORM\Column(type="string")
     */
    private $PAN = '';


    /**
     * @ORM\Column(type="datetime")
     */
    private $expiry_date;

    /**
     * @ORM\Column(type="integer")
     */
    private $cvv2 = 0;


    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $ammount = null;


    /**
     * @ORM\Column(type="string")
     */
    private $state = '';

    /**
     * @return mixed
     */
    public function getPAN()
    {
        return $this->PAN;
    }

    /**
     * @param mixed $PAN
     */
    public function setPAN($PAN)
    {
        $this->PAN = $PAN;
    }

    /**
     * @return mixed
     */
    public function getExpiryDate()
    {
        return $this->expiry_date;
    }

    /**
     * @param mixed $expiry_date
     */
    public function setExpiryDate($expiry_date)
    {
        $this->expiry_date = $expiry_date;
    }

    /**
     * @return mixed
     */
    public function getCvv2()
    {
        return $this->cvv2;
    }

    /**
     * @param mixed $cvv2
     */
    public function setCvv2($cvv2)
    {
        $this->cvv2 = $cvv2;
    }

    /**
     * @return mixed
     */
    public function getAmmount()
    {
        return $this->ammount;
    }

    /**
     * @param mixed $ammount
     */
    public function setAmmount($ammount)
    {
        $this->ammount = $ammount;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }



}