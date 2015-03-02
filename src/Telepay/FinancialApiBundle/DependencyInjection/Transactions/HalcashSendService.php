<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;


use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\Document\Transaction;

class HalcashSendService extends BaseService {

    public function getFields(){
        return array(
            'phone_number',
            'phone_prefix',
            'country',
            'amount',
            'reference',
            'pin',
            'transaction_id',
            'alias'
        );
    }

    public function create(Transaction $t){
        die(print_r($this->getFields(), true));
    }
    
    public function update(Transaction $t, $data){

    }

}