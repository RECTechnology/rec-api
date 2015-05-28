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

        $user = $this->container->get('doctrine')->getRepository('TelepayFinancialApiBundle:User')
            ->find($transaction->getUser());

        $dm = $this->container->get('doctrine_mongodb')->getManager();

        //notificar con curl la transaccion
        //necesitamos el id el status el amount y el secret
        $id = $transaction->getId();
        $status = $transaction->getStatus();
        $amount = $transaction->getAmount();

        $key = $user->getAccessSecret();

        $data_to_sign = $id.$status.$amount;

        $signature = hash_hmac('sha256',$data_to_sign,$key);

        $params = array(
            'id'        =>   $id,
            'status'    =>  $status,
            'amount'    =>  $amount,
            'signature' =>  $signature
        );

        // create curl resource
        $ch = curl_init();
        // set url
        curl_setopt($ch, CURLOPT_URL, $url_notification);
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$params);
        // $output contains the output string
        $output = curl_exec($ch);

        // Comprobar si ocurrió un error
        if(!curl_errno($ch))
        {
            $info = curl_getinfo($ch);

            if( $info['http_code'] >= 200 && $info['http_code'] <=299 ){
                //notificado
                $notified = true;
            }else{
                $notified = false;

                //no notificado
            }

            if( $notified == true && $transaction->getStatus()==Transaction::$STATUS_SUCCESS){
                $transaction->setNotified(true);
                $transaction->setNotificationTries($transaction->getNotificationTries()+1);
            }

        }
        // close curl resource to free up system resources
        curl_close($ch);

        $dm->persist($transaction);
        $dm->flush();

        return $transaction;

    }
}