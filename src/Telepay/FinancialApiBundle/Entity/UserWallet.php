<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/13/15
 * Time: 6:50 PM
 */

namespace Telepay\FinancialApiBundle\Entity;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 */
class UserWallet {

    const STATUS_ENABLED = 'enabled';
    const STATUS_DISABLED = 'disabled';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    private $currency;

    /**
     * @ORM\Column(type="float")
     */
    private $available;

    /**
     * @ORM\Column(type="float")
     */
    private $balance;

    private $scale;

    /**
     * @ORM\Column(type="string")
     */
    private $status = 'enabled';

    /**
     * @ORM\ManyToOne(targetEntity="Telepay\FinancialApiBundle\Entity\Group")
     */
    private $group;


    public function getAvailable()
    {
        return $this->available;
    }

    public function getCurrency()
    {
        return $this->currency;
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
     * @param mixed $available
     */
    public function setAvailable($available)
    {
        $this->available = $available;
    }

    /**
     * @param mixed $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
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

    public function getWalletView(){
        $this->scale=$this->getScale();
        $wallet['id'] = $this->getId();
        $wallet['currency'] = $this->getCurrency();
        $wallet['available'] = intval($this->getAvailable());
        $wallet['balance'] = intval($this->getBalance());
        $wallet['scale'] = $this->getScale();
        $wallet['status'] = $this->getStatus();

        return $wallet;
    }

    //TODO quitar esto de aqui porque se repite en varios sitios
    public function getScale(){
        $currency=$this->getCurrency();
        $scale=0;
        switch($currency){
            case "EUR":
                $scale=2;
                break;
            case "REC":
                $scale=8;
                break;
            case "":
                $scale=0;
                break;
        }
        return $scale;
    }

    /**
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param mixed $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @param mixed $amount
     */
    public function addBalance($amount){

        $this->available = $this->available + $amount;
        $this->balance = $this->balance + $amount;

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
}