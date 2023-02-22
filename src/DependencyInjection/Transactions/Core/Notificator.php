<?php
namespace App\DependencyInjection\Transactions\Core;

use App\Entity\Group;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Document\Transaction;
use Throwable;

class Notificator {

    /** @var ContainerInterface $container */
    private $container;
    /** @var LoggerInterface */
    private $logger;
    /** @var EntityManagerInterface */
    private $em;

    function __construct(ContainerInterface $container, LoggerInterface $logger, EntityManagerInterface $em)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->em = $em;
    }

    public function notificate(Transaction $transaction, $force = false){
        $this->logger->debug("START Notificator::notificate({$transaction->getId()})");
        if($transaction->getNotified()) {
            if($force)
                $this->logger->warning("Transaction {$transaction->getId()} notification forced, notifying anyway...");
            else{
                $this->logger->warning("Transaction {$transaction->getId()} is already notified, skipping...");
                return $transaction;
            }
        }

        /** @var DocumentManager $dm */
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        /** @var Group $group */
        $group = $this->em->getRepository(Group::class)->find($transaction->getGroup());

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

    public function notificate_error($url_notification, $group_id, $amount, $post_params){
        $group = $this->container->get('doctrine')->getRepository('FinancialApiBundle:Group')
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
        curl_setopt($ch,CURLOPT_HTTPHEADER, ["Expect:  "]);
        // $output contains the output string
        $output = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);
        return curl_errno($ch);
    }
}