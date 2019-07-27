<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace App\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\DateTime;
use App\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use App\FinancialApiBundle\Document\Transaction;

class PosService extends BaseService{

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $container){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $container);
    }

    public function getFields(){
        return array(
            'amount', 'description', 'currency', 'url_notification', 'url_ok', 'url_ko', 'order_id'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $id = $baseTransaction->getId();

        $trans_id = rand();

        $url_final ='/notifications/v1/pos/'.$id;

        $important_data = array(
            'url_final' =>  $url_final,
            'contador'  =>  1,
            'transaction_id'    =>  $trans_id
        );

        $out = array(
            'transaction_pos_id'    => $trans_id,
            'url_notification'  =>  $url_final
        );

        if($baseTransaction->getCurrency() == 'BTC'){
            $out['address'] = $this->getContainer()->get('net.app.provider.btc')->getnewaddress();
            $out['received'] = 0.0;
            $out['min_confirmations'] = 0;
            $out['confirmations'] = 0;
        }

        $baseTransaction->setData($important_data);
        $baseTransaction->setDataOut($out);

        return $baseTransaction;

    }

    //Regenera la tpv con los mismos datos
    public function update(Transaction $transaction, $data){

        return $transaction;
    }

    public function cancel(Transaction $transaction,$data){

        throw new HttpException(400,'Method not implemented');

    }

    public function check(Transaction $transaction){
        $client_reference = $transaction->getId();

        return $transaction;
    }

    public function notificate(Transaction $transaction , $request){


        return $transaction;

    }

}