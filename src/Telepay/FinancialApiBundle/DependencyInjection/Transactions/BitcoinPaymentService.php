<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\Services\Cryptos\Lib\EasyBitcoin\EasyBitcoin;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\TelepayResponse;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Services\Bitcoin;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Services\Responses\BitcoinResponse;
use Telepay\FinancialApiBundle\Document\Transaction;

class BitcoinPaymentService extends BaseService {

    private $transaction;

    public function getFields(){
        return array(
            'amount', 'confirmations'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $amount = $baseTransaction->getData()['amount'];
        $confirmations = $baseTransaction->getData()['confirmations'];

        $btc = new EasyBitcoin('','');

        $address = $btc->getnewaddress();

        $data = new BitcoinResponse(
            $baseTransaction->getId(),
            3600,
            $address,
            $amount,
            $confirmations
        );

        $baseTransaction->setData(json_encode($data));

        return $baseTransaction;

    }

    public function update($id, $data){

    }

}