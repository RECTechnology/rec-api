<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/12/15
 * Time: 7:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Services;


class TransactionCreatedResponse{
    private $id;
    public function __construct($transaction){
        $this->id = $transaction->getId();
    }
}