<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/22/15
 * Time: 8:16 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Transactions;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\LimitAdder;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\LimitChecker;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\LimitCount;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Entity\SwiftFee;
use Telepay\FinancialApiBundle\Entity\SwiftLimit;
use Telepay\FinancialApiBundle\Entity\SwiftLimitCount;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

class SwiftController extends RestApiController{

    public function make(Request $request, $version_number, $type_in, $type_out){

        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();

        $admin_id = $this->container->getParameter('admin_user_id');
        $client_default_id = $this->container->getParameter('swift_client_id_default');

        $admin = $em->getRepository('TelepayFinancialApiBundle:User')->findOneById($admin_id);

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {

            //transaction authenticated with user/pass or credentials
            $user = $this->container->get('security.context')->getToken()->getUser();

            $tokenManager = $this->container->get('fos_oauth_server.access_token_manager.default');
            $accessToken = $tokenManager->findTokenByToken(
                $this->container->get('security.context')->getToken()->getToken()
            );
            $client = $accessToken->getClient();
            if(!$user){
                $user = $client->getUser();
            }

        }else{
            $user = $admin;
            $client = $em->getRepository('TelepayFinancialApiBundle:Client')->findOneById($client_default_id);

        }

        //check if user has this service and if is active
        $services = $client->getSwiftList();
        if(!$services) throw new HttpException(403,'Method not allowed');

        if(!in_array($type_in.'_'.$type_out.':1', $services)) throw new HttpException(403, 'Method not allowed');

        if(!$request->request->has('amount')) throw new HttpException(404, 'Param amount not found');

        //TODO optional url_notification param

        $amount = $request->request->get('amount');

        //Create transaction
        $transaction = new Transaction();
        $transaction->createFromRequest($request);
        $transaction->setAmount($amount);
        $transaction->setTotal($amount);
        $transaction->setFixedFee(0);
        $transaction->setVersion($version_number);
        $transaction->setVariableFee(0);
        $transaction->setService($type_in.'_'.$type_out);
        $transaction->setUser($user->getId());
        $transaction->setType('swift');
        $transaction->setMethodIn($type_in);
        $transaction->setMethodOut($type_out);
        $transaction->setClient($client->getId());

        //GET METHODS
        $cashInMethod = $this->container->get('net.telepay.in.'.$type_in.'.v'.$version_number);
        $cashOutMethod = $this->container->get('net.telepay.out.'.$type_out.'.v'.$version_number);

        //GET PAYOUT INFO(parameters sent by user
        $pay_out_info = $cashOutMethod->getPayOutInfo($request);
        $transaction->setDataIn($pay_out_info);
        $transaction->setPayOutInfo($pay_out_info);

        //GET PAYOUT CURRENCY FOR THE TRANSATION
        $transaction->setCurrency($cashOutMethod->getCurrency());
        //TODO get scale in and out
        $transaction->setScale(2);

        //get configuration(method)
        $swift_config = $this->container->get('net.telepay.config.'.$type_out);
        $methodFees = $swift_config->getFees();

        //get client fees (fixed & variable)
        $clientFees = $em->getRepository('TelepayFinancialApiBundle:SwiftFee')->findOneBy(array(
            'client'    =>  $client->getId(),
            'cname' =>  $type_in.'_'.$type_out
        ));

        $clientLimits = $em->getRepository('TelepayFinancialApiBundle:SwiftLimit')->findOneBy(array(
            'client'    =>  $client->getId(),
            'cname' =>  $type_in.'_'.$type_out
        ));

        $clientLimitsCount = $em->getRepository('TelepayFinancialApiBundle:SwiftLimitCount')->findOneBy(array(
            'client'    =>  $client->getId(),
            'cname' =>  $type_in.'_'.$type_out
        ));

        if(!$clientFees){
            $clientFees = new SwiftFee();
            $clientFees->setFixed(0);
            $clientFees->setVariable(0);
            $clientFees->setCname($type_in.'_'.$type_out);
            $clientFees->setClient($client);
            $clientFees->setCurrency($transaction->getCurrency());
            $em->persist($clientFees);
            $em->flush();
        }
        if(!$clientLimits){
            $clientLimits = new SwiftLimit();
            $clientLimits->setCname($type_in.'_'.$type_out);
            $clientLimits->setSingle(0);
            $clientLimits->setDay(0);
            $clientLimits->setWeek(0);
            $clientLimits->setMonth(0);
            $clientLimits->setYear(0);
            $clientLimits->setTotal(0);
            $clientLimits->setClient($client);
            $clientLimits->setCurrency($transaction->getCurrency());
            $em->persist($clientLimits);
            $em->flush();
        }
        if(!$clientLimitsCount) {
            $clientLimitsCount = new SwiftLimitCount();
            $clientLimitsCount->setClient($client);
            $clientLimitsCount->setCname($type_in.'_'.$type_out);
            $clientLimitsCount->setSingle(0);
            $clientLimitsCount->setDay(0);
            $clientLimitsCount->setWeek(0);
            $clientLimitsCount->setMonth(0);
            $clientLimitsCount->setYear(0);
            $clientLimitsCount->setTotal(0);
            $em->persist($clientLimitsCount);
            $em->flush();
        }

        if($methodFees->getVariable() == 0){
            $service_fee = ($methodFees->getFixed());
        }else{
            $service_fee = ($amount * ($methodFees->getVariable()/100) + $methodFees->getFixed());
        }

        if($clientFees->getVariable() == 0){
            $client_fee = ($clientFees->getFixed());
        }else{
            $client_fee = ($amount * ($clientFees->getVariable()/100) + $clientFees->getFixed());
        }

        $total_fee = $client_fee + $service_fee;
        $total = round($amount + $total_fee, 0);

        //ADD AND CHECK LIMITS
        $clientLimitsCount = (new LimitAdder())->add($clientLimitsCount, $total);
        $checker = new LimitChecker();
        if(!$checker->leq($clientLimitsCount , $clientLimits))
            throw new HttpException(509,'Limit exceeded');

        $em->persist($clientLimitsCount);
        $em->flush();

        $exchange = $this->_exchange($total  , $cashOutMethod->getCurrency(), $cashInMethod->getCurrency());

        try{
            $pay_in_info = $cashInMethod->getPayInInfo($exchange);

        }catch (HttpException $e){
            $transaction->setStatus(Transaction::$STATUS_ERROR);
            $dm->persist($transaction);
            $dm->flush();
            throw new HttpException(400,'Service Temporally unavailable.');
        }

        $price = round($total/($pay_in_info['amount']/1e8),0);

        $transaction->setPrice($price);

        $transaction->setPayInInfo($pay_in_info);
        $transaction->setDataOut($pay_in_info);
        $transaction->setStatus(Transaction::$STATUS_CREATED);
        $dm->persist($transaction);
        $dm->flush();

        return $this->swiftTransaction($transaction, "Done");

    }

    public function check(Request $request, $version_number, $type_in, $type_out, $id){
        //Get transaction by id
        $dm = $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->findOneBy(array(
            'id'    =>  $id,
            'type'  =>  'swift',
            'method_in' =>  $type_in,
            'method_out'    =>  $type_out
        ));

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        return $this->swiftTransaction($transaction, "Done");

    }

    public function update(Request $request, $version_number, $type_in, $type_out, $id){

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            $user = $this->get('security.context')->getToken()->getUser();
        }else{
            throw new HttpException(403, 'You don\'t have the necessary permissions');
        }

        $dm = $this->get('doctrine_mongodb')->getManager();

        if(!$request->request->has('option')) throw new HttpException(404, 'Missing parameter \'option\'');

        $option = $request->request->get('option');

        $transaction = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->findOneBy(array(
            'id'    =>  $id,
            'type'  =>  'swift',
            'user'  =>  $user->getId()
        ));

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        $payInInfo = $transaction->getPayInInfo();
        $payOutInfo = $transaction->getPayOutInfo();

        $method_in = $this->get('net.telepay.in.'.$transaction->getMethodIn().'.v1');
        $method_out = $this->get('net.telepay.out.'.$transaction->getMethodOut().'.v1');

        if($option == 'cancel'){
            if($transaction->getStatus() == Transaction::$STATUS_SUCCESS && $payOutInfo['status'] == 'sent'){
                //cancel transaction
                try{
                    $payOutInfo = $method_out->cancel($payOutInfo);
                }catch (HttpException $e){
                    throw new HttpException(400, 'Cancel transaction error');
                }

                $transaction->setPayOutInfo($payOutInfo);
                $transaction->setStatus(Transaction::$STATUS_CANCELLED);
                $transaction->setUpdated(new \DateTime());
                $message = 'Transaction cancelled successfully';

            }else{
                throw new HttpException(403, 'Transaction can\'t be cancelled');
            }
        }elseif($option == 'resend'){
            if($transaction->getStatus() == Transaction::$STATUS_FAILED || $transaction->getStatus() == Transaction::$STATUS_CANCELLED){
                //resend out method
                try{
                    $payOutInfo = $method_out->send($payOutInfo);
                }catch (HttpException $e){
                    throw new HttpException(400, 'Resend transaction error');
                }

                //TODO if previous status = failed generate fees transactions

                $transaction->setPayOutInfo($payOutInfo);
                $transaction->setStatus(Transaction::$STATUS_SUCCESS);
                $transaction->setUpdated(new \DateTime());
                $message = 'Transaction resend successfully';

            }
        }elseif($option = 'refund'){

            throw new HttpException(403, 'Method not implemented yet');

        }else{
            throw new HttpException(400, 'Bad parameter \'option\'');
        }

        $dm->persist($transaction);
        $dm->flush();

        return $this->restV2(204,"ok", $message);

    }

    public function hello(Request $request, $version_number, $currency){

        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();

        $admin_id = $this->container->getParameter('admin_user_id');
        $client_default_id = $this->container->getParameter('swift_client_id_default');

        $admin = $em->getRepository('TelepayFinancialApiBundle:User')->findOneById($admin_id);

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {

            //transaction authenticated with user/pass or credentials
            $user = $this->container->get('security.context')->getToken()->getUser();

            $tokenManager = $this->container->get('fos_oauth_server.access_token_manager.default');
            $accessToken = $tokenManager->findTokenByToken(
                $this->container->get('security.context')->getToken()->getToken()
            );
            $client = $accessToken->getClient();
            if(!$user){
                $user = $client->getUser();
            }

        }else{
            $user = $admin;
            $client = $em->getRepository('TelepayFinancialApiBundle:Client')->findOneById($client_default_id);

        }

        $swiftServices = array(
            'btc_halcash_es'    =>  array('btc','halcash_es'),
            'btc_halcash_pl'    =>  array('btc','halcash_pl'),
            'btc_bank_transfer' =>  array('btc','sepa'),
            'btc_cryptocapital' =>  array('btc','cryptocapital'),
            'paynet_btc'        =>  array('paynet_reference','btc')
        );

        $response = array();

        foreach($swiftServices as $swift => $methods){
            $service = $swift;
            $type_in = $methods[0];
            $type_out = $methods[1];

            //get configuration(method)
            $swift_config = $this->container->get('net.telepay.config.'.$type_out);
            $methodFees = $swift_config->getFees();

            //get client fees (fixed & variable)
            $clientFees = $em->getRepository('TelepayFinancialApiBundle:SwiftFee')->findOneBy(array(
                'client'    =>  $client->getId(),
                'cname' =>  $type_in.'_'.$type_out
            ));

            $clientLimits = $em->getRepository('TelepayFinancialApiBundle:SwiftLimit')->findOneBy(array(
                'client'    =>  $client->getId(),
                'cname' =>  $type_in.'_'.$type_out
            ));

            $fixed_fee = $methodFees->getFixed() + $clientFees->getFixed();
            $variable_fee = $methodFees->getVariable() + $clientFees->getVariable();

            $cashInMethod = $this->container->get('net.telepay.in.'.$type_in.'.v'.$version_number);
            $cashOutMethod = $this->container->get('net.telepay.out.'.$type_out.'.v'.$version_number);

            $currency_in = $cashInMethod->getCurrency();
            $currency_out = $cashOutMethod->getCurrency();

            $scale_in = Currency::$SCALE[$currency_in];

            $amount = pow(10,$scale_in);

            $exchange = $this->_exchange($amount , $currency_in, $currency_out);

            $values = array();

            if($clientLimits->getSingle() > 0){

                for($i = 10;$i <= $clientLimits->getSingle(); $i=+10){
                    array_push($values, $i);
                }
            }else{

                for($i = 10;$i <= 1000; $i+=10){
                    array_push($values, $i);
                }
            }

            $response[$service] = array(
                'price' =>  $exchange,
                'limits'    =>  array(
                    'single'    =>  ($clientLimits->getSingle() >= 0) ? $clientLimits->getSingle(): 'unlimited',
                    'daily'     =>  ($clientLimits->getDay() >= 0) ? $clientLimits->getDay() : 'unlimited',
                    'weekly'    =>  ($clientLimits->getWeek() >= 0) ? $clientLimits->getWeek() : 'unlimited',
                    'monthly'   =>  ($clientLimits->getMonth() >= 0) ? $clientLimits->getMonth() : 'unlimited',
                    'yearly'    =>  ($clientLimits->getYear() >=0) ? $clientLimits->getYear() : 'unlimited',
                    'total'     =>  ($clientLimits->getTotal() >= 0) ? $clientLimits->getTotal() : 'unlimited'
                ),
                'fees'  =>  array(
                    'fixed' =>  $fixed_fee,
                    'variable'  =>  $variable_fee
                ),
                'values'    =>  $values
            );

        }

        $resp = array(
            'swift_methods' =>  $response,
            'confirmations' =>  1,
            'timeout'   =>  1200,
            'terms' =>  "http://www.chip-chap.com/legal.html",
            'title' =>  'ChipChap'
        );

        return $this->restPlain(200, $resp);

    }

    public function _exchange($amount,$curr_in,$curr_out){

        $dm=$this->getDoctrine()->getManager();
        $exchangeRepo=$dm->getRepository('TelepayFinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$curr_in,'dst'=>$curr_out),
            array('id'=>'DESC')
        );

        if(!$exchange) throw new HttpException(404,'Exchange not found -> '.$curr_in.' TO '.$curr_out);

        $price = $exchange->getPrice();
        $total = round($amount * $price,0);

        return $total;

    }

}


