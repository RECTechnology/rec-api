<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 12/12/15
 * Time: 8:16 AM
 */

namespace Telepay\FinancialApiBundle\Controller\Transactions;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\LimitAdder;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\LimitChecker;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\SwiftFee;
use Telepay\FinancialApiBundle\Entity\SwiftLimit;
use Telepay\FinancialApiBundle\Entity\SwiftLimitCount;
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

                $previous_status = $transaction->getStatus();

                //TODO implement a resend with changed params

                //resend out method
                try{
                    $payOutInfo = $method_out->send($payOutInfo);
                }catch (HttpException $e){
                    throw new HttpException(400, 'Resend transaction error');
                }

                $transaction->setPayOutInfo($payOutInfo);
                $transaction->setStatus(Transaction::$STATUS_SUCCESS);
                $transaction->setUpdated(new \DateTime());
                $message = 'Transaction resend successfully';

                $dm->persist($transaction);
                $dm->flush();

                //if previous status == failed generate fees transactions
                if($previous_status == Transaction::$STATUS_FAILED){
                    $this->_generateFees($transaction, $method_in, $method_out);
                }

            }
        }elseif($option = 'refund'){

            if($transaction->getStatus() == Transaction::$STATUS_FAILED || $transaction->getStatus() == Transaction::$STATUS_CANCELLED){
                //for refund we need different values foreach services
                //if pay_in is with bitcoins we need btc address
                if($request->request->has('amount')){
                    $request->request->remove('amount');
                }

                $request->request->add(array(
                    'amount'    =>  $payInInfo['amount']
                ));

                $refund_info = $method_in->getPayOutInfo($request);

                try{
                    $refund_info = $method_out->send($refund_info);
                }catch (HttpException $e){
                    throw new HttpException($e->getStatusCode(), $e->getMessage());
                }

                $payInInfo['status'] = 'refund';
                $payInInfo['refund_info'] = $refund_info;
                $transaction->setStatus('refund');
                $transaction->setUpdated(new \DateTime());
                $transaction->setPayInInfo($payInInfo);

                $dm->persist($transaction);
                $dm->flush();

                //TODO get feeTransactions and refund too - restar al wallet porque antes se las hemos sumado.
                //get fee transactions to refund.
//                $feesTransactions = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->findBy(array(
//                    'type'  =>  'fee',
//                    'method_in' =>  $type_in,
//                    'method_out'    =>  $type_out,
//                    'previous_transaction' => $transaction->getId()
//                ));
                $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');
                $transaction_id = $transaction->getId();
                $transactions = $qb
                    ->field('type')->equals('fee')
                    ->where("function() {
            if (typeof this.dataIn !== 'undefined') {
                if (typeof this.dataIn.previous_transaction !== 'undefined') {
                    if(String(this.dataIn.previous_transaction).indexOf('$transaction_id') > -1){
                        return true;
                    }
                }
            }

            return false;
            }")
                    ->getQuery()
                    ->execute();

                $resArray = [];
                foreach($transactions->toArray() as $res){
                    $resArray []= $res;

                }

                $total = count($resArray);

                die(print_r($total,true));


            }
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

    private function _exchange($amount,$curr_in,$curr_out){

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

    private function _generateFees(Transaction $transaction, $method_in, $method_out){

        $em = $this->getDoctrine()->getManager();
        $dm = $this->get('doctrine_mongodb')->getManager();
        $client = $transaction->getClient();
        $amount = $transaction->getAmount();

        $root_id = $this->getContainer()->getParameter('admin_user_id');
        $root = $em->getRepository('TelepayFinancialApiBundle:User')->find($root_id);

        //get configuration(method)
        $swift_config = $this->getContainer()->get('net.telepay.config.'.$method_out);
        $methodFees = $swift_config->getFees();

        //get client fees (fixed & variable)
        $clientFees = $em->getRepository('TelepayFinancialApiBundle:SwiftFee')->findOneBy(array(
            'client'    =>  $client,
            'cname' =>  $method_in.'_'.$method_out
        ));

        $client_fee = ($amount * ($clientFees->getVariable()/100) + $clientFees->getFixed());
        $service_fee = ($amount * ($methodFees->getVariable()/100) + $methodFees->getFixed());

        //client fees goes to the user
        $userFee = new Transaction();
        $userFee->setUser($transaction->getUser());
        $userFee->setType('fee');
        $userFee->setCurrency($transaction->getCurrency());
        $userFee->setScale($transaction->getScale());
        $userFee->setAmount($client_fee);
        $userFee->setFixedFee($clientFees->getFixed());
        $userFee->setVariableFee($amount * ($clientFees->getVariable()/100));
        $userFee->setService($method_in.'_'.$method_out);
        $userFee->setStatus('success');
        $userFee->setTotal($client_fee);
        $userFee->setDataIn(array(
            'previous_transaction'  =>  $transaction->getId(),
            'transaction_amount'    =>  $transaction->getAmount(),
            'total_fee' =>  $client_fee + $service_fee
        ));
        $userFee->setClient($client);

        //service fees goes to root
        $rootFee = new Transaction();
        $rootFee->setUser($root->getId());
        $rootFee->setType('fee');
        $rootFee->setCurrency($transaction->getCurrency());
        $rootFee->setScale($transaction->getScale());
        $rootFee->setAmount($service_fee);
        $rootFee->setFixedFee($methodFees->getFixed());
        $rootFee->setVariableFee($amount * ($methodFees->getVariable()/100));
        $rootFee->setService($method_in.'_'.$method_out);
        $rootFee->setStatus('success');
        $rootFee->setTotal($service_fee);
        $rootFee->setDataIn(array(
            'previous_transaction'  =>  $transaction->getId(),
            'transaction_amount'    =>  $transaction->getAmount(),
            'total_fee' =>  $client_fee + $service_fee
        ));
        $rootFee->setClient($client);
        $dm->persist($userFee);
        $dm->persist($rootFee);
        $dm->flush();

        //TODO get wallets and add fees to both, user and wallet
        $rootWallets = $root->getWallets();
        $current_wallet = null;

        foreach ( $rootWallets as $wallet){
            if ($wallet->getCurrency() == $rootFee->getCurrency()){
                $current_wallet = $wallet;
            }
        }

        $current_wallet->setAvailable($current_wallet->getAvailable() + $service_fee);
        $current_wallet->setBalance($current_wallet->getBalance() + $service_fee);

        $em->persist($current_wallet);
        $em->flush();

        $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($transaction->getUser());
        $userWallets = $user->getWallets();
        $current_wallet = null;

        foreach ( $userWallets as $wallet){
            if ($wallet->getCurrency() == $userFee->getCurrency()){
                $current_wallet = $wallet;
            }
        }

        $current_wallet->setAvailable($current_wallet->getAvailable() + $client_fee);
        $current_wallet->setBalance($current_wallet->getBalance() + $client_fee);

        $em->persist($current_wallet);
        $em->flush();

    }

}


