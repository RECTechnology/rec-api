<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/6/14
 * Time: 2:22 PM
 */

namespace Telepay\FinancialApiBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * @ORM\Entity
 * @ORM\Table(name="delegated_change_data",uniqueConstraints={
 *     @ORM\UniqueConstraint(
 *          name="fk_idx",
 *          columns={
 *              "delegated_change_id",
 *              "account_id",
 *              "exchanger_id"
 *          }
 *     )
 * })
 * @ExclusionPolicy("all")
 */
class DelegatedChangeData{

    public function __construct()
    {
        $this->created = new DateTime();
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Expose
     */
    protected $id;

    /**
     * @ORM\Column(type="datetime")
     * @Expose
     */
    protected $created;


    /**
     * @ORM\Column(type="datetime")
     * @Expose
     */
    protected $updated;


    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\DelegatedChange")
     * @Expose
     */
    private $delegated_change;


    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\Group")
     * @Expose
     */
    private $account;

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\Group")
     * @Expose
     */
    private $exchanger;

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
     * @return mixed
     */
    public function getAccount()
    {
        return $this->account;
    }


    /**
     * @param mixed $account
     */
    public function setAccount($account)
    {
        if($account->hasRole('ROLE_COMPANY')){
            throw new Exception("Expect a user not a commerce!");
        }else{
            $this->account = $account;
        }
    }

    /**
     * @return mixed
     */
    public function getExchanger()
    {
        return $this->exchanger;
    }

    /**
     * @param mixed $exchanger
     */
    public function setExchanger($exchanger)
    {
        if($exchanger->hasRole('ROLE_COMPANY')){
            $this->exchanger = $exchanger;
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

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param mixed $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return mixed
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param mixed $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }



}