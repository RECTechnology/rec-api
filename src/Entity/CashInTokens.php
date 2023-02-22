<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/13/15
 * Time: 6:50 PM
 */

namespace App\Entity;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Financial\Currency;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Exclude;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ExclusionPolicy("all")
 */
class CashInTokens{

    /**
     * @Exclude
     */
    public static $STATUS_ACTIVE = "active";

    /**
     * @Exclude
     */
    public static $STATUS_EXPIRED = "expired";

    /**
     * @Exclude
     */
    public static $STATUS_CLOSED = "closed";

    public function __construct(){
        $this->created = new \DateTime();
        $this->updated = new \DateTime();
    }

    public function getDriverNameByType($type)
    {
        if($type == 'easypay' || $type == 'sepa'){
            return 'net.app.provider.eur';
        }else{
            return 'net.app.provider.'.$type;
        }

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
    private $created;

    /**
     * @ORM\Column(type="datetime")
     * @Expose
     */
    private $updated;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Expose
     */
    private $token;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $method;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $currency;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Group")
     */
    private $company;

    /**
     * @ORM\Column(type="integer")
     * @Expose
     */
    private $expires_in;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $label;

    /**
     * @ORM\Column(type="string")
     * @Expose
     */
    private $status;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CashInDeposit", mappedBy="token", cascade={"remove"})
     * @Expose
     */
    private $deposits;

    /**
     * @Expose
     */
    private $account_number;

    /**
     * @Expose
     */
    private $beneficiary;

    /**
     * @Expose
     */
    private $bic_swift;

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
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @param mixed $account_number
     */
    public function setAccountNumber($account_number)
    {
        $this->account_number = $account_number;
    }

    /**
     * @param mixed $beneficiary
     */
    public function setBeneficiary($beneficiary)
    {
        $this->beneficiary = $beneficiary;
    }

    /**
     * @param mixed $bic_swift
     */
    public function setBicSwift($bic_swift)
    {
        $this->bic_swift = $bic_swift;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
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
    public function getExpiresIn()
    {
        return $this->expires_in;
    }

    /**
     * @param mixed $expires_in
     */
    public function setExpiresIn($expires_in)
    {
        $this->expires_in = $expires_in;
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
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getDeposits()
    {
        return $this->deposits;
    }

    /**
     * @param mixed $deposits
     */
    public function setDeposits($deposits)
    {
        $this->deposits = $deposits;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
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
}