<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs\Halcash;
use Telepay\FinancialApiBundle\Document\Transaction;


class HalcashService extends BaseService{

    private $halcashProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $halcashProvider, $container){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $container);
        $this->halcashProvider = $halcashProvider;
    }

    public function getFields(){
        return array(
            'phone_number','phone_prefix','country','amount','reference','pin'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();

        $phone_number = $baseTransaction->getDataIn()['phone_number'];
        if(strlen($phone_number)<1){
            throw new HttpException(400,'phone_number is required');
        }
        $phone_prefix = $baseTransaction->getDataIn()['phone_prefix'];
        if(strlen($phone_prefix)<1){
            throw new HttpException(400,'phone_prefix is required');
        }
        if(isset($baseTransaction->getDataIn()['sms_language'])){
            $language = strtoupper($baseTransaction->getDataIn()['sms_language']);
        }else{
            $language = 'ENG';
        }

        $country = $baseTransaction->getDataIn()['country'];
        //pasamos a euros porque lo recibimos en centimos
        $amount = $baseTransaction->getDataIn()['amount']/100;
        if($amount <= 0) throw new HttpException(400,'Amount must be bigger than 0');
        $reference = $baseTransaction->getDataIn()['reference'];
        if(strlen($reference) > 20){
            throw new HttpException(400,'Reference Field must be less than 20 characters');
        }

        $pin = $baseTransaction->getDataIn()['pin'];
        if(strlen($pin) > 4) throw new HttpException(400,'Pin Field must be less than 5 characters');
        $transaction_id = $baseTransaction->getId();

        //comprobar el pais para utilizar uno u otro
        if($country ==='ES'){
            $hal = $this->halcashProvider->sendV3($phone_number,$phone_prefix,$amount,$reference,$pin,$transaction_id);
        }else{
            $hal = $this->halcashProvider->sendInternational($phone_number,$phone_prefix,$amount,$reference,$pin,$transaction_id,$country,$language);
        }

        $logger = $this->getContainer()->get('logger');
        $logger->info('HALCASH->create');
        $logger->info('HALCASH: phone->'.$phone_number.', amount->'.$amount.', id->'.$transaction_id);
        $logger->info('HALCASH: response->'.$hal);

        if($hal === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData($hal);
        $baseTransaction->setDataOut($hal);

        switch($hal['errorcode']){
            case 0:
                $baseTransaction->setStatus('created');
                break;
            case 44:
                throw new HttpException(502,'Invalid credentials');
            default:
                throw new HttpException(503,'Service unavailable');

        }

        return $baseTransaction;

    }

    public function update(Transaction $transaction, $data){

    }

    public function cancel(Transaction $transaction,$data){

        $ticket = $transaction->getDataOut()['halcashticket'];

        $hal = $this->halcashProvider->cancelation($ticket, 'Telepay cancelation');

        if($hal['errorcode'] == 0){
            $transaction->setStatus('cancelled');
        }

        $logger = $this->getContainer()->get('logger');
        $logger->info('HALCASH->cancel');
        $logger->info('HALCASH: response-> '.$hal);

        return $transaction;

    }

    public function check(Transaction $transaction){

        $ticket=$transaction->getDataOut()['halcashticket'];

        $hal = $this->halcashProvider->status($ticket);

        if($hal['errorcode']==0){

            switch($hal['estado']){
                case 'Autorizada':
                    $transaction->setStatus('created');
                    break;
                case 'Preautorizada':
                    $transaction->setStatus('created');
                    break;
                case 'Anulada':
                    $transaction->setStatus('cancelled');
                    break;
                case 'BloqueadaPorCaducidad':
                    $transaction->setStatus('expired');
                    break;
                case 'BloqueadaPorReintentos':
                    $transaction->setStatus('locked');
                    break;
                case 'Devuelta':
                    $transaction->setStatus('returned');
                    break;
                case 'Dispuesta':
                    $transaction->setStatus('success');
                    break;
                case 'EstadoDesconocido':
                    $transaction->setStatus('unknown');
                    break;
            }

        }

        $logger = $this->getContainer()->get('logger');
        $logger->info('HALCASH->check');
        $logger->info('HALCASH: response-> '.$hal);

        return $transaction;

    }

}