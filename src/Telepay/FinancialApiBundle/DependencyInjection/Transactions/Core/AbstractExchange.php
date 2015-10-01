<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/23/15
 * Time: 6:44 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core;


use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;

class AbstractExchange implements ExchangeInterface {


    /**
     * @var
     */
    private $currency_in;

    /**
     * @var
     */
    private $currency_out;

    /**
     * @var
     */
    private $cname;

    /**
     * @return string
     */
    public function getCurrencyIn()
    {
        return $this->currency_in;
    }

    /**
     * @return string
     */
    public function getCurrencyOut()
    {
        return $this->currency_out;
    }

    /**
     * @return string
     */
    public function getCname()
    {
        return $this->cname;
    }

    public function getFields()
    {
        return array();
    }

    function __construct($currency_in, $currency_out, $cname)
    {
        $this->cname = $cname;
        $this->currency_in = $currency_in;
        $this->currency_out = $currency_out;
    }

}