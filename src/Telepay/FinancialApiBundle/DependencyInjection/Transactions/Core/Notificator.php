<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/23/15
 * Time: 6:51 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Telepay\FinancialApiBundle\Document\Transaction;

class Notificator {

    private $container;

    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function notificate(Transaction $transaction){

        if(isset($transaction->getDataIn()['url_notification']))
            $url_notification = $transaction->getDataIn()['url_notification'];
        else
            return $transaction;

        if($url_notification == null) return $transaction;

        $user = $this->container->get('doctrine')->getRepository('TelepayFinancialApiBundle:User')
            ->find($transaction->getUser());

        $dm = $this->container->get('doctrine_mongodb')->getManager();

        //notificar con curl la transaccion
        //necesitamos el id el status el amount y el secret
        $id = $transaction->getId();
        $status = $transaction->getStatus();
        $amount = $transaction->getAmount();

        $data = $transaction->getDataOut();

        $key = $user->getAccessSecret();

        $data_to_sign = $id.$status.$amount;

        $signature = hash_hmac('sha256',$data_to_sign,$key);

        $params = array(
            'id'        =>  $id,
            'status'    =>  $status,
            'amount'    =>  $amount,
            'signature' =>  $signature
        );

        if(isset($transaction->getDataIn()['order_id'])) $params['order_id'] = $transaction->getDataIn()['order_id'];

        // create curl resource
        $ch = curl_init();
        // set url
        curl_setopt($ch, CURLOPT_URL, $url_notification);
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$params);
        //fix bug 417 Expectation Failed
        curl_setopt($ch,CURLOPT_HTTPHEADER,array("Expect:  "));
        // $output contains the output string
        $output = curl_exec($ch);

        // Comprobar si ocurrió un error
        if(!curl_errno($ch))
        {
            $info = curl_getinfo($ch);

            if( $transaction->getStatus()==Transaction::$STATUS_SUCCESS || $transaction->getStatus()==Transaction::$STATUS_CANCELLED){
                if( $info['http_code'] >= 200 && $info['http_code'] <=299){
                    $transaction->setNotified(true);
                    $transaction->setNotificationTries($transaction->getNotificationTries()+1);
                }else{
                    $transaction->setNotified(false);
                    $transaction->setNotificationTries($transaction->getNotificationTries()+1);
                    //no notificado
                }
            }

        }
        // close curl resource to free up system resources
        curl_close($ch);

        $dm->persist($transaction);
        $dm->flush();

        return $transaction;

    }

    public function gcm_notificate($user_id, $message){

    }
}