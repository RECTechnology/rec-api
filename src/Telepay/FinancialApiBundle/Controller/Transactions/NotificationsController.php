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

        if (!$transaction) throw new HttpException(404, 'Transaction not found');
        $logger->info('notifications -> transaction found');
        if ($transaction->getStatus() != Transaction::$STATUS_CREATED) throw new HttpException(409, 'Transaction notificated yet');
        $paymentInfo = $transaction->getPayInInfo();
        if ($paymentInfo['transaction_id'] != $tid) throw new HttpException(409, 'Notification not allowed');

        if($status == 'cancel' || $status == 'error') {
            $transaction->setStatus('failed');
            $paymentInfo['error'] = $params['response_code'];
            $paymentInfo['concept'] = 'error status';
            $transaction->setPayInInfo($paymentInfo);
        }
        else {
            $paymentInfo = $cashInMethod->notification($params, $paymentInfo);
            $logger->info('notifications -> status => ' . $paymentInfo['status']);
            if ($paymentInfo['status'] == 'received') {
                if($paymentInfo['save_card']){
                    $cardInfo = $cashInMethod->cardInfo($paymentInfo['card_id']);
                    $em = $this->getDoctrine()->getManager();
                    $card = new CreditCard();
                    $card->setCompany($transaction->getGroup());
                    $card->setUser($transaction->getUser());
                    $card->setExternalId($cardInfo['id']);
                    $card->setAlias($cardInfo['alias']);
                    $em->persist($card);
                    $em->flush();
                }
                $paymentInfo['received'] = $params['response_transactionAmount'];
                $transaction->setStatus('received');
                $transaction->setPayInInfo($paymentInfo);
            } elseif ($paymentInfo['status'] == 'failed') {
                $paymentInfo['error'] = $params['response_code'];
                $transaction->setStatus('failed');
                $transaction->setPayInInfo($paymentInfo);
            } else {
                $logger->info('notifications -> debug => ' . $paymentInfo['debug']);
            }
        }
        $transaction->setNotified(true);
        $logger->info('notifications -> status => ' . json_encode($paymentInfo));
        $dm->persist($transaction);
        $dm->flush();
        return array('status' => $status, 'concept' => $paymentInfo['concept']);
    }

    private function _logger(){
        $logger = $this->container->get('logger');
        return $logger;
    }
}