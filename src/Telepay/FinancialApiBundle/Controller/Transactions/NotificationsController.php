<?php

namespace Telepay\FinancialApiBundle\Controller\Transactions;

use MongoDBODMProxies\__CG__\Telepay\FinancialApiBundle\Document\Transaction;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;

class NotificationsController extends RestApiController{

    public function notificate(Request $request, $version_number, $service_cname, $status = null){
        if($service_cname == 'lemonway'){
            $notification = $this->_lemonway($request, $status);
        }else{
            $notification = false;
        }
        return $this->restV2(
            200,
            "ok",
            "Request successful",
            $notification
        );
    }

    public function notificateGET(Request $request, $version_number, $service_cname, $status = null){
        if($service_cname == 'lemonway'){
            $url = $this->container->getParameter('lemonway_notification_app');
            $link_app = $url . $status;
            $response = $this->redirect($link_app);
            return $response;
        }else{
            $notification = false;
        }
        return $notification;
    }

    public function _lemonway(Request $request, $status){
        $logger = $this->_logger();
        $logger->info('notifications -> lemonway notification(' . $status . ')');
        $cashInMethod = $this->container->get('net.telepay.in.lemonway.v1');
        $allParams = $request->request->all();
        $logger->info('notifications -> data => '. json_encode($allParams));
        $params = array();
        foreach($allParams as $key => $value){
            $params[$key] = $value;
        }
        $dm = $this->get('doctrine_mongodb')->getManager();
        $tid = $params['response_transactionId'];
        $transaction = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('pay_in_info.transaction_id')->equals($tid)
            ->getQuery()
            ->getSingleResult();

        if($status == 'cancel' || $status == 'error') {
            $transaction->setStatus('failed');
            $paymentInfo['error'] = $params['response_code'];
        }
        else{
            if(!$transaction) throw new HttpException(404, 'Transaction not found');
            $logger->info('notifications -> transaction found');
            if($transaction->getStatus() != Transaction::$STATUS_CREATED) throw new HttpException(409, 'Transaction notificated yet');
            $paymentInfo = $transaction->getPayInInfo();
            if($paymentInfo['transaction_id'] != $tid) throw new HttpException(409, 'Notification not allowed');
            $paymentInfo = $cashInMethod->notification($params, $paymentInfo);
            $logger->info('notifications -> status => ' . $paymentInfo['status']);
            if($paymentInfo['status'] == 'received') {
                $paymentInfo['received'] = $params['response_transactionAmount'];
                $transaction->setStatus('received');
                $transaction->setPayInInfo($paymentInfo);
                $dm->persist($transaction);
                $dm->flush();
            }elseif($paymentInfo['status'] == 'failed'){
                $transaction->setStatus('failed');
                $paymentInfo['error'] = $params['response_code'];
            }else{
                $logger->info('notifications -> debug => '.$paymentInfo['debug']);
            }
        }
        return array('status' => $paymentInfo['status']);
    }

    private function _logger(){
        $logger = $this->container->get('logger');
        return $logger;
    }
}