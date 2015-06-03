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
class BTCAdresses extends AbstractWallet implements ExternallyDrived {


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
    private $address;

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


}