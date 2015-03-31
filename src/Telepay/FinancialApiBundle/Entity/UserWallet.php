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

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 */
class UserWallet extends AbstractWallet implements ExternallyDrived {


    public function receive($amount)
    {
        throw new HttpException(501, "Method receive not implemented");
    }

    public function send($amount)
    {
        // TODO: Implement send() method.
    }

    public function getAmount()
    {
        // TODO: Implement getAmount() method.
    }

    public function getAvailable()
    {
        return $this->available;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getDriverName()
    {
        return 'net.telepay.provider.btc';
    }

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

    public function getWalletView(){
        $this->scale=$this->getScale();
        return $this;
    }

    //TODO quitar esto de aqui porque se repite en varios sitios
    public function getScale(){
        $currency=$this->getCurrency();
        $scale=0;
        switch($currency){
            case "EUR":
                $scale=2;
                break;
            case "MXN":
                $scale=2;
                break;
            case "USD":
                $scale=2;
                break;
            case "BTC":
                $scale=8;
                break;
            case "FAC":
                $scale=8;
                break;
            case "":
                $scale=0;
                break;
        }
        return $scale;
    }
}