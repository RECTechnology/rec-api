<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/13/15
 * Time: 6:50 PM
 */

namespace Telepay\FinancialApiBundle\Entity;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Financial\Currency;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 */
class BTCWallet {


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
        return $this->getAmount();
    }

    public function getCurrency()
    {
        return Currency::$BTC;
    }

    public function getDriverName()
    {
        return 'net.telepay.provider.btc';
    }

    /**
     * @ORM\Column(type="string")
     */
    private $cypher_data;

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
    public function getCypherData()
    {
        return $this->cypher_data;
    }

    /**
     * @param mixed $cypher_data
     */
    public function setCypherData($cypher_data)
    {
        $this->cypher_data = $cypher_data;
    }
}