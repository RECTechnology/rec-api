<?php

namespace App\FinancialApiBundle\Controller\Transactions;

use MongoDBODMProxies\__CG__\App\FinancialApiBundle\Document\Transaction;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\RestApiController;
use App\FinancialApiBundle\Entity\CreditCard;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\User;


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
        $cashInMethod = $this->container->get('net.app.in.lemonway.v1');
        $allParams = $request->request->all();
        $logger->info('notifications -> data => '. json_encode($allParams));
        $params = array();
        foreach($allParams as $key => $value){
            $params[$key] = $value;
        }
        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();

        $tid = $params['response_wkToken'];
        $logger->info('notifications -> ID = ' . $tid);
        $transaction = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
            ->field('pay_in_info.wl_token')->equals($tid)
            ->getQuery()
            ->getSingleResult();

        if (!$transaction) throw new HttpException(404, 'Transaction not found');
        $logger->info('notifications -> transaction found');
        $paymentInfo = $transaction->getPayInInfo();

        $logger->info('notifications -> LEMON STATUS CHECK ' . $status);
        if($status === 'cancel' || $status === 'error') {
            $logger->info('notifications -> ERROR');
            $transaction->setStatus('failed');
            $paymentInfo['payment_info'] = json_encode($allParams);
            $paymentInfo['received'] = 0;
            $paymentInfo['status'] = 'failed';
            $paymentInfo['final'] = true;
            $paymentInfo['error'] = $params['response_code'];
            $paymentInfo['concept'] = 'error status';
            $transaction->setPayInInfo($paymentInfo);
        }
        else {
            $paymentInfo = $cashInMethod->notification($params, $paymentInfo);
            $logger->info('notifications -> status => ' . $paymentInfo['status']);
            if ($paymentInfo['status'] == 'received') {
                if($paymentInfo['save_card']){
                    $cardInfo = $cashInMethod->cardInfo($paymentInfo['external_card_id']);

                    $group = $em->getRepository('FinancialApiBundle:Group')->findOneBy(array(
                        'id'    =>  $transaction->getGroup()
                    ));

                    $user = $em->getRepository('FinancialApiBundle:User')->findOneBy(array(
                        'id'    =>  $transaction->getUser()
                    ));

                    $card = new CreditCard();
                    $card->setCompany($group);
                    $card->setUser($user);
                    $card->setExternalId($cardInfo['id']);
                    $card->setAlias($cardInfo['alias']);
                    $em->persist($card);
                    $em->flush();
                }
                $paymentInfo['received'] = $params['response_transactionAmount'];
                $transaction->setStatus('received');
                $transaction->setPayInInfo($paymentInfo);

                //Pasar saldo de admin a commerce
                $commerce_id = $paymentInfo['commerce_id'];
                $group_destination = $em->getRepository('FinancialApiBundle:Group')->findOneBy(array(
                    'id'    =>  $commerce_id
                ));

                $logger->info('notifications -> envio euros a => '. $group_destination->getCIF());

                $sentInfo = array(
                    'to' => $group_destination->getCIF(),
                    'amount' => number_format($transaction->getAmount()/100, 2)
                );
                $resultado = $cashInMethod->send($sentInfo);
                $logger->info('notifications -> eur resultado => '. json_encode($resultado));
            }
            elseif ($paymentInfo['status'] === 'failed') {
                $logger->info('notifications -> FAILED');
                $paymentInfo['error'] = $params['response_code'];
                $paymentInfo['payment_info'] = json_encode($allParams);
                $transaction->setStatus('failed');
                $transaction->setPayInInfo($paymentInfo);
            }
            else {
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