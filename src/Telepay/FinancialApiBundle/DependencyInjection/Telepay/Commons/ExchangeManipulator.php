<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons;

use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Balance;
use Telepay\FinancialApiBundle\Entity\Group;

class ExchangeManipulator{
    private $doctrine;

    public function __construct($doctrine){
        $this->doctrine = $doctrine;
    }

    /**
     * User
     * Transaction amount (+/-)
     * Transaction
     */

    public function exchange($amount, $currency_in, $currency_out){

        $em = $this->doctrine->getManager();

        $exchange = $em->getRepository('TelepayFinancialApiBundle:Exchange')->findOneBy(
            array(
                'src'   =>  strtoupper($currency_in),
                'dst'   =>  strtoupper($currency_out)
            ),
            array('id'  =>  'DESC')
        );

        $amount = round($amount * $exchange->getPrice(),0);

        return $amount;

    }

}