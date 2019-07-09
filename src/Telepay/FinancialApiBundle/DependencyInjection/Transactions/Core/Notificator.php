<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Telepay\FinancialApiBundle\Document\Transaction;

class Notificator {

    private $container;
    private $env;

    function __construct(ContainerInterface $container, $env)
    {
        $this->container = $container;
        $this->env = $env;
    }

    public function notificate(Transaction $transaction){
        $transaction->setNotified(false);
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $group = $this->container->get('doctrine')->getRepository('TelepayFinancialApiBundle:Group')
            ->find($transaction->getGroup());

        if(!$transaction->getInternal()) {
            if (
                $group->getType() == 'PRIVATE'
                &&
                $transaction->getStatus()==Transaction::$STATUS_SUCCESS
                &&
                $group->getSubtype() == 'BMINCOME'
                &&
                ($transaction->getType() == 'out' || $transaction->getType() == 'in')
            ) {
                $this->notificate_upc($transaction);
                $transaction->setNotified(true);
            }
        }

        if(isset($transaction->getDataIn()['url_notification']))
            $url_notification = $transaction->getDataIn()['url_notification'];
        else
            return $transaction;

        if($url_notification == null) return $transaction;

        //necesitamos el id el status el amount y el secret
        $id = $transaction->getId();
        $status = $transaction->getStatus();
        $amount = $transaction->getAmount();

        if($transaction->getType() == 'in'){
            $data = $transaction->getPayInInfo();
        }elseif($transaction->getType() == 'out'){
            $data = $transaction->getPayOutInfo();
        }elseif($transaction->getType() == 'swift'){
            $data = array(
                'pay_in_info'   =>  $transaction->getPayInInfo(),
                'pay_out_info'  =>   $transaction->getPayOutInfo()
            );
        }else{
            $type = $transaction->getType();
            $type = explode('-', $type);
            if($type[0] == 'POS'){
                $data = $transaction->getPayInInfo();
                $amount = $transaction->getPayInInfo()['received_amount'];
            }else{
                $data = $transaction->getDataOut();
            }
        }

        $key = $group->getAccessSecret();
        $data_to_sign = $id.$status.$amount;
        $signature = hash_hmac('sha256', $data_to_sign, $key);
        $params = array(
            'id'        =>  $id,
            'status'    =>  $status,
            'amount'    =>  $amount,
            'signature' =>  $signature,
            'data'      =>  json_encode($data)
        );

        if(isset($transaction->getPayInInfo()['order_id'])) $params['order_id'] = $transaction->getPayInInfo()['order_id'];

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
        if(!curl_errno($ch)) {
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

    public function notificate_upc(Transaction $transaction){
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $group = $this->container->get('doctrine')->getRepository('TelepayFinancialApiBundle:Group')
            ->find($transaction->getGroup());

        //necesitamos el id el status el amount y el secret
        $id = $transaction->getId();
        $status = $transaction->getStatus();
        $amount = round($transaction->getAmount() / 100000000, 2);
        $key = 'HyRJn3cQ35fbpKah';

        if ($transaction->getType() == 'out') {
            $payment_info = $transaction->getPayOutInfo();;
            $destination = $this->container->get('doctrine')->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
                'rec_address' => $payment_info['address']
            ));

            if($destination->getType()=='PRIVATE') {
                $data_to_sign = $id . $status . $amount;
                $signature = hash_hmac('sha256', $data_to_sign, $key);
                $data = array(
                    'receiver' => 'PARTICULAR',
                    'date' => time(),
                    'activity_type_code' => "16"
                );
            }
            elseif($destination->getType()=='COMPANY'){
                $data_to_sign = $id . $status . $amount;
                $signature = hash_hmac('sha256', $data_to_sign, $key);
                $activity = $destination->getCategory() ? $destination->getCategory()->getId() : "16";
                $data = array(
                    'receiver' => $destination->getCif(),
                    'date' => time(),
                    'activity_type_code' => $activity
                );
            }
        }
        elseif($transaction->getType() == 'in'){
            $data_to_sign = $id . $status . $amount;
            $signature = hash_hmac('sha256', $data_to_sign, $key);
            $data = array(
                'receiver' => 'CAMBIO',
                'date' => time(),
                'activity_type_code' => "16"
            );
        }

        $params = array(
            'account_id'=>  $group->getCif(),
            'id'        =>  $id,
            'status'    =>  $status,
            'amount'    =>  $amount,
            'signature' =>  $signature,
            'data'      =>  json_encode($data)
        );


        $payload = [
            "account_id" => $params['account_id'],
            "id" => $params['account_id'],
            "status" => $params['status'],
            "amount" => strval($params['amount']),
            "signature" => $params['signature'],
            "data" => [
                "receiver" => $data['receiver'],
                "date" => strval($data['date']),
                "activity_type_code" => strval($data['activity_type_code'])
            ],
        ];

        $msg = json_encode($payload, JSON_BIGINT_AS_STRING);

        $logger = $this->container->get('com.qbitartofacts.rec.commons.notificator');
        $logger->msg('#NOTIFICATION_UPC_REQUEST: ' . $msg);

        $notificator = $this->container->get('com.qbitartofacts.rec.commons.upc_notificator');
        $response = $notificator->msg($msg);

        $response_data = json_decode($response, true);
        if(!isset($response_data['Message'])){
            $response_data['Message'] = array();
        }
        if(!isset($response_data['Message']['Type'])) {
            $response_data['Message']['Type'] = 'FAILED';
        }
        if($response_data['Message']['Type']!='SUCCESS'){
            $transaction->setNotified(false);
            $transaction->setNotificationTries($transaction->getNotificationTries()+1);
        }

        $clean_response = str_replace('"', '', $response);

        $logger->msg('#NOTIFICATION_UPC_RESPONSE: ' . $clean_response);


        // close curl resource to free up system resources
        $dm->persist($transaction);
        $dm->flush();
        return $transaction;
    }

    public function notificate_error($url_notification, $group_id, $amount, $post_params){
        $group = $this->container->get('doctrine')->getRepository('TelepayFinancialApiBundle:Group')
            ->find($group_id);

        $dm = $this->container->get('doctrine_mongodb')->getManager();

        //necesitamos el id el status el amount y el secret
        $id = 0;
        $status = 'error';

        $key = $group->getAccessSecret();

        $data_to_sign = $id.$status.$amount;

        $signature = hash_hmac('sha256', $data_to_sign, $key);

        $params = array(
            'id'        =>  $id,
            'status'    =>  $status,
            'amount'    =>  $amount,
            'signature' =>  $signature,
            'data'      =>  json_encode($post_params)
        );

        if(isset($post_params['order_id'])) $params['order_id'] = $post_params['order_id'];

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

        // close curl resource to free up system resources
        curl_close($ch);
        return curl_errno($ch);
    }
}