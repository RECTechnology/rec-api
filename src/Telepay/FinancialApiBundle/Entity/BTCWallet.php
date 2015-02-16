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
class BTCWallet extends AbstractWallet {

    /**
     * @ORM\Column(type="string")
     */
    private $seedWords;

    /**
     * @ORM\Column(type="string")
     */
    private $mpk;

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
}