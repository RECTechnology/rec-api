<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/13/15
 * Time: 6:50 PM
 */

namespace Telepay\FinancialApiBundle\Entity;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Financial\CashOut;
use Telepay\FinancialApiBundle\Financial\Currency;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ExclusionPolicy("all")
 */
class BTCAddresses implements OwnByUserInterface {


    public function getCurrency()
    {
        return Currency::$BTC;
    }

    public function getDriverName()
    {
        return 'net.telepay.provider.btc';
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Expose
     */
    private $address;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $label;

    /**
     * @ORM\Column(type="boolean")
     * @Expose
     */
    private $archived;


    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\User")
     */
    private $user;

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

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
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return mixed
     */
    public function getArchived()
    {
        return $this->archived;
    }

    /**
     * @param mixed $archived
     */
    public function setArchived($archived)
    {
        $this->archived = $archived;
    }


    public function getUser()
    {
        return $this->user;
    }
}