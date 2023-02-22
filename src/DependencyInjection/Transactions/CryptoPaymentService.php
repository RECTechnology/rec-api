<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace App\DependencyInjection\Transactions;

use App\DependencyInjection\Commons\IntegerManipulator;
use App\DependencyInjection\Commons\MailerAwareTrait;
use App\DependencyInjection\Transactions\Core\BaseService;
use App\Document\Transaction;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mime\Email;

class CryptoPaymentService extends BaseService {

    use MailerAwareTrait;
    private $cryptoProvider;

    public function __construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $cryptoProvider, $container){
        parent::__construct($name, $cname, $role, $cash_direction, $currency, $base64Image, $container);
        $this->cryptoProvider = $cryptoProvider;
    }

    public function getFields(){
        return array(
            'amount', 'confirmations', 'expires_in'
        );
    }

    public function create(Transaction $baseTransaction = null){

        if($baseTransaction === null) $baseTransaction = new Transaction();
        $amount = $baseTransaction->getDataIn()['amount'];
        $confirmations = $baseTransaction->getDataIn()['confirmations'];
        $expires_in = $baseTransaction->getDataIn()['expires_in'];

        $im = new IntegerManipulator();
        if(!$im->isInteger($amount)) throw new HttpException(400, "amount must be an integer (".$amount.") given");
        if(!$im->isInteger($confirmations)) throw new HttpException(400, "confirmations must be an integer");
        if(!$im->isInteger($expires_in)) throw new HttpException(400, "expires_in must be an integer");
        if($amount <= 0) throw new HttpException(400, "Amount must be positive");
        if($confirmations < 0 ) throw new HttpException(400, "confirmations can't be negative");

        $address = $this->cryptoProvider->getnewaddress();

        if($address === false)
            throw new HttpException(503, "Service temporarily unavailable, please try again in a few minutes");

        $baseTransaction->setData(array(
            'id' => $baseTransaction->getId(),
            'address' => $address,
            'expires_in' => intval($expires_in),
            'amount' => doubleval($amount),
            'received' => 0.0,
            'min_confirmations' => intval($confirmations),
            'confirmations' => 0,
        ));

        $baseTransaction->setDataOut(array(
            'address' => $address,
            'expires_in' => intval($expires_in),
            'received' => 0.0,
            'min_confirmations' => intval($confirmations),
            'confirmations' => 0,
        ));

        return $baseTransaction;

    }
    private function hasExpired($transaction){
        return $transaction->getCreated()->getTimestamp()+$transaction->getData()['expires_in'] < time();
    }

    public function check(Transaction $transaction){

        $currentData = $transaction->getData();

        if($transaction->getStatus() === 'success' || $transaction->getStatus() === 'expired')
            return $transaction;

        $address = $currentData['address'];
        $amount = $currentData['amount'];
        $allReceived = $this->cryptoProvider->listreceivedbyaddress(0, true);

        $margin = 100;
        $allowed_amount = $amount - $margin;

        foreach($allReceived as $cryptoData){
            if($cryptoData['address'] === $address){
                $currentData['received'] = doubleval($cryptoData['amount'])*1e8;
                if(doubleval($cryptoData['amount'])*1e8 >= $allowed_amount){
                    $currentData['confirmations'] = $cryptoData['confirmations'];
                    if($currentData['confirmations'] >= $currentData['min_confirmations'])
                        $transaction->setStatus("success");
                    else
                        $transaction->setStatus("received");

                }
                
                $transaction->setData($currentData);
                $transaction->setDataOut($currentData);
                return $transaction;
            }
        }

        if($transaction->getStatus() === 'created' && $this->hasExpired($transaction))
            $transaction->setStatus('expired');

        return $transaction;
    }

    public function cancel(Transaction $transaction, $data){

        throw new HttpException(400,'Method not implemented');

    }

    public function notificate(Transaction $transaction, $request){

        static $paramNames = array(
            'value',
            'address',
            'txid'
        );

        $params = array();
        foreach ($paramNames as $paramName){
            if(isset( $request[$paramName] )){
                $params[] = $request[$paramName];
            }else{
                throw new HttpException(404,'Param '.$paramName.' not found ');
            }

        }

        $this->sendEmail(
            'Btc_pay notification --> '.$transaction->getId(),
            'value --> '.$params[0].' address --> '.$params[1].'txid -->'.$params[2]
        );

        return $transaction;

    }

    public function sendEmail($subject, $body){

        $message = (new Email())
            ->subject($subject)
            ->from('no-reply@chip-chap.com')
            ->to('pere@chip-chap.com', 'cto@chip-chap.com')
            ->html(
                $this->getContainer()->get('templating')
                    ->render('Email/support.html.twig',
                        array(
                            'message'        =>  $body
                        )
                    )
            );

        $this->mailer->send($message);
    }
}