<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 12/12/15
 * Time: 8:16 AM
 */

namespace Telepay\FinancialApiBundle\Controller\Transactions;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\LimitAdder;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\LimitChecker;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Client;
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

        if(!in_array($type_in.'-'.$type_out.':1', $services)) throw new HttpException(403, 'Service temporally unavailable');

        if(!$request->request->has('amount')) throw new HttpException(404, 'Param amount not found');

        //TODO optional url_notification param

        $amount = $request->request->get('amount');

        //check amount
        if($amount == '') throw new HttpException(400, 'Amount is empty');

        //GET METHODS
        $cashInMethod = $this->container->get('net.telepay.in.'.$type_in.'.v'.$version_number);
        $cashOutMethod = $this->container->get('net.telepay.out.'.$type_out.'.v'.$version_number);

        //get configuration(method)
        $swift_config = $this->container->get('net.telepay.config.'.$type_in.'.'.$type_out);
        $methodFees = $swift_config->getFees();
        $methodInfo = $swift_config->getInfo();

        if($cashOutMethod->getCurrency() != $methodInfo['currency']){
            $amount_in = $amount;
            $amount_out = $this->_exchange($amount_in, $cashInMethod->getCurrency(), $cashOutMethod->getCurrency());

        }else{
            $amount_in = 0;
            $amount_out = $amount;
        }

        $this->_checkLimits($amount_out, $type_out, $request);


        $ip = $request->server->get('REMOTE_ADDR');

        //Create transaction
        $transaction = new Transaction();
        $transaction->createFromRequest($request);
        $transaction->setFixedFee(0);
        $transaction->setVersion($version_number);
        $transaction->setVariableFee(0);
        $transaction->setService($type_in.'-'.$type_out);
        $transaction->setUser($user->getId());
        $transaction->setType('swift');
        $transaction->setMethodIn($type_in);
        $transaction->setMethodOut($type_out);
        $transaction->setClient($client->getId());
        $transaction->setIp($ip);


        //TODO remove when adapter is in the puta calle
        if(!$request->request->has('force')){
            if($amount < $methodInfo['min_value']) throw new HttpException(403, 'Amount must be greater than '.$methodInfo['min_value']);

            if($amount % $methodInfo['range'] != 0) throw new HttpException(403, 'Amount must be multiple of '.$methodInfo['range']);

            if($amount > $methodInfo['max_value']) throw new HttpException(403, 'Max amount exceeded');
        }

        $clientConfig = $this->_getClientConfig($client, $transaction, $type_in, $type_out);

        //get client fees (fixed & variable)
        $clientFees = $clientConfig['fees'];

        $clientLimits = $clientConfig['limits'];

        $clientLimitsCount = $clientConfig['limits_count'];

        if($methodFees->getVariable() == 0){
            $service_fee = ($methodFees->getFixed());
        }else{
            $service_fee = ($amount_out * ($methodFees->getVariable()/100) + $methodFees->getFixed());
        }

        if($clientFees->getVariable() == 0){
            $client_fee = ($clientFees->getFixed());
        }else{
            $client_fee = ($amount_out * ($clientFees->getVariable()/100) + $clientFees->getFixed());
        }

        $total_fee = $client_fee + $service_fee;

        if($cashOutMethod->getCurrency() != $methodInfo['currency']){
            $total = round($amount_out - $total_fee, 0);
            $amount_out = $total;
            $request->request->remove('amount');
            $request->request->add(array(
                'amount'    =>  $amount_out
            ));
        }else{
            $total = round($amount + $total_fee, 0);
            $amount_in = $this->_exchange($total  , $cashOutMethod->getCurrency(), $cashInMethod->getCurrency());
        }

        //ADD AND CHECK LIMITS
        $clientLimitsCount = (new LimitAdder())->add($clientLimitsCount, $total);
        $checker = new LimitChecker();
        if(!$checker->leq($clientLimitsCount , $clientLimits))
            throw new HttpException(509,'Limit exceeded');

        $em->persist($clientLimitsCount);
        $em->flush();

        //GET PAYOUT INFO(parameters sent by user
        $pay_out_info = $cashOutMethod->getPayOutInfo($request);

        $transaction->setDataIn($pay_out_info);
        $transaction->setPayOutInfo($pay_out_info);

        //GET PAYOUT CURRENCY FOR THE TRANSATION
        $transaction->setCurrency($cashOutMethod->getCurrency());
        //TODO get scale in and out
        $transaction->setScale(Currency::$SCALE[$cashOutMethod->getCurrency()]);
        $transaction->setAmount($amount_out);
        $transaction->setTotal($amount_out);

        try{
            $pay_in_info = $cashInMethod->getPayInInfo($amount_in);

        }catch (Exception $e){
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
        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->findOneBy(array(
            'id'    =>  $id,
            'type'  =>  'swift',
            'method_in' =>  $type_in,
            'method_out'    =>  $type_out
        ));

        if(!$transaction){
            $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');
            $result = $qb
                ->field('type')->equals('swift')
                ->field('method_in')->equals($type_in)
                ->field('method_out')->equals($type_out)
                ->where("function(){
                    if (typeof this.pay_out_info.find_token !== 'undefined') {
                        if(String(this.pay_out_info.find_token).indexOf('$id') > -1){
                            return true;
                        }
                }
                }")

                ->getQuery()
                ->execute();

            foreach($result->toArray() as $d){
                $transaction = $d;
            }
        }

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        return $this->swiftTransaction($transaction, "Done");

    }

    public function checkByAddress($address){

        $dm = $this->get('doctrine_mongodb')->getManager();
        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');
        $result = $qb
            ->field('type')->equals('swift')
            ->where("function(){
                    if (typeof this.pay_in_info !== 'undefined') {
                        if(String(this.pay_in_info.address).indexOf('$address') > -1){
                            return true;
                        }
                }
                }")

            ->getQuery()
            ->execute();

        $transaction = null;
        foreach($result->toArray() as $d){
            $transaction = $d;
        }

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
                }catch (Exception $e){
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
            if($transaction->getStatus() == Transaction::$STATUS_FAILED || $transaction->getStatus() == Transaction::$STATUS_CANCELLED || $transaction->getStatus()== Transaction::$STATUS_EXPIRED){

                $previous_status = $transaction->getStatus();

                //TODO implement a resend with changed params (phone and prefix done)

                if($request->request->has('new_phone') && $request->request->get('new_phone')!=''){
                    $new_phone = $request->request->get('new_phone');
                    $payOutInfo['phone']=$new_phone;
                }

                if($request->request->has('new_prefix') && $request->request->get('new_prefix')!=''){
                    $new_prefix = $request->request->get('new_prefix');
                    $payOutInfo['prefix']=$new_prefix;
                }

                //resend out method
                try{
                    $payOutInfo = $method_out->send($payOutInfo);
                }catch (Exception $e){
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

            }else{
                throw new HttpException(403, 'Transaction can\'t be resent');
            }
        }elseif($option == 'refund'){

            if($transaction->getStatus() == Transaction::$STATUS_FAILED || $transaction->getStatus() == Transaction::$STATUS_CANCELLED || $transaction->getPayOutInfo()['status']== Transaction::$STATUS_EXPIRED){
                //for refund we need different values foreach services
                //if pay_in is with bitcoins we need btc address
                if($request->request->has('amount')){
                    $request->request->remove('amount');
                }

                $request->request->add(array(
                    'amount'    =>  $payInInfo['received']
                ));

                $refund_info = $method_in->getPayOutInfo($request);

                try{
                    $refund_info = $method_in->send($refund_info);
                }catch (Exception $e){
                    throw new HttpException($e->getStatusCode(), $e->getMessage());
                }

                $payInInfo['status'] = 'refund';
                $payInInfo['refund_info'] = $refund_info;
                $transaction->setStatus('refund');
                $transaction->setUpdated(new \DateTime());
                $transaction->setPayInInfo($payInInfo);

                $dm->persist($transaction);
                $dm->flush();

                //get fee transactions to refund.
                $this->_returnFees($transaction);

            }else{
                throw new HttpException(403, 'Transaction can\'t be refund');
            }

        }elseif($option == 'recheck'){
            if($transaction->getStatus() == Transaction::$STATUS_EXPIRED && $payInInfo['status'] == 'expired'){
                //cancel transaction
                $transaction->setStatus(Transaction::$STATUS_CREATED);
                $payInInfo['status'] = 'created';
                $payInInfo['final'] = false;

                $transaction->setPayInInfo($payInInfo);
                $transaction->setUpdated(new \DateTime());
                $message = 'Transaction updated successfully';

            }else{
                throw new HttpException(403, 'Transaction can\'t be recheck');
            }
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

        $swiftServices = $client->getSwiftList();

        $response = array();

        foreach($swiftServices as $swift){
            $method = preg_split('/:/', $swift);
            $status = $method[1];
            $service = $method[0];

            $types = preg_split('/-/', $method[0], 2);
            $type_in = $types[0];
            $type_out = $types[1];

            if($type_in == $currency || $type_out == $currency){
                //get configuration(method)
                $swift_config = $this->container->get('net.telepay.config.'.$type_in.'.'.$type_out);

                $methodFees = $swift_config->getFees();
                $swiftInfo = $swift_config->getInfo();

                //get client fees (fixed & variable)
                $clientFees = $em->getRepository('TelepayFinancialApiBundle:SwiftFee')->findOneBy(array(
                    'client'    =>  $client->getId(),
                    'cname' =>  $type_in.'-'.$type_out
                ));

                $clientLimits = $em->getRepository('TelepayFinancialApiBundle:SwiftLimit')->findOneBy(array(
                    'client'    =>  $client->getId(),
                    'cname' =>  $type_in.'-'.$type_out
                ));

                $fixed_fee = $methodFees->getFixed() + $clientFees->getFixed();
                $variable_fee = $methodFees->getVariable() + $clientFees->getVariable();

                $cashInMethod = $this->container->get('net.telepay.in.'.$type_in.'.v'.$version_number);
                $cashOutMethod = $this->container->get('net.telepay.out.'.$type_out.'.v'.$version_number);

                $currency_in = $cashInMethod->getCurrency();
                $currency_out = $cashOutMethod->getCurrency();

                if($cashOutMethod->getCurrency() == 'BTC' || $cashOutMethod->getCurrency() == 'FAC'){
                    $scale_in = Currency::$SCALE[$currency_out];

                    $amount = pow(10,$scale_in);

                    $exchange = $this->_exchange($amount , $currency_out, $currency_in);
                }else{
                    $scale_in = Currency::$SCALE[$currency_in];

                    $amount = pow(10,$scale_in);

                    $exchange = $this->_exchange($amount , $currency_in, $currency_out);
                }


                $values = array();

                if($clientLimits->getSingle() > 0 && $clientLimits->getSingle() <= $swiftInfo['max_value']){

                    for($i = $swiftInfo['min_value'];$i <= $clientLimits->getSingle(); $i+=$swiftInfo['range']){
                        array_push($values, $i);
                    }
                }else{

                    for($i = $swiftInfo['min_value'];$i <= $swiftInfo['max_value']; $i+=$swiftInfo['range']){
                        array_push($values, $i);
                    }
                }

                $response[$service] = array(
                    'orig'  =>  $cashInMethod->getName(),
                    'dst'   =>  $cashOutMethod->getName(),
                    'countries' =>  $swiftInfo['countries'],
//                'text'  =>  '',
                    'status'    =>  ($status == 1) ? 'available' : 'unavailable',
//                'message'   =>  '',
                    'delay' =>  $swiftInfo['delay'],
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
                    'expires_in'   =>  $swiftInfo['expires_in'],
                    'currency_values'   =>  $swiftInfo['currency'],
                    'scale_values'   =>  Currency::$SCALE[$swiftInfo['currency']],
                    'values'    =>  $values

                );
            }

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

        $root_id = $this->container->getParameter('admin_user_id');
        $root = $em->getRepository('TelepayFinancialApiBundle:User')->find($root_id);

        //get configuration(method)
        $swift_config = $this->container->get('net.telepay.config.'.$method_out);
        $methodFees = $swift_config->getFees();

        //get client fees (fixed & variable)
        $clientFees = $em->getRepository('TelepayFinancialApiBundle:SwiftFee')->findOneBy(array(
            'client'    =>  $client,
            'cname' =>  $method_in.'-'.$method_out
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
        $userFee->setService($method_in.'-'.$method_out);
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
        $rootFee->setService($method_in.'-'.$method_out);
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

    private function _returnFees(Transaction $transaction){

        $dm = $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();
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

        foreach($resArray as $fee){
            $fee_amount = $fee->getAmount();

            $fee->setAmount(0);
            $fee->setStatus('refund');
            $fee->setTotal(0);

            $dm->persist($fee);
            $dm->flush();

            //getWallet and discount fee
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($transaction->getUser());
            $userWallets = $user->getWallets();
            $current_wallet = null;

            foreach ( $userWallets as $wallet){
                if ($wallet->getCurrency() == $fee->getCurrency()){
                    $current_wallet = $wallet;
                }
            }

            $current_wallet->setAvailable($current_wallet->getAvailable() - $fee_amount);
            $current_wallet->setBalance($current_wallet->getBalance() - $fee_amount);

            $em->persist($current_wallet);
            $em->flush();

        }

    }

    private function _getClientConfig(Client $client, Transaction $transaction, $type_in, $type_out){

        $em = $this->getDoctrine()->getManager();
        //get client fees (fixed & variable)
        $clientFees = $em->getRepository('TelepayFinancialApiBundle:SwiftFee')->findOneBy(array(
            'client'    =>  $client->getId(),
            'cname' =>  $type_in.'-'.$type_out
        ));

        $clientLimits = $em->getRepository('TelepayFinancialApiBundle:SwiftLimit')->findOneBy(array(
            'client'    =>  $client->getId(),
            'cname' =>  $type_in.'-'.$type_out
        ));

        $clientLimitsCount = $em->getRepository('TelepayFinancialApiBundle:SwiftLimitCount')->findOneBy(array(
            'client'    =>  $client->getId(),
            'cname' =>  $type_in.'-'.$type_out
        ));

        if(!$clientFees){
            $clientFees = new SwiftFee();
            $clientFees->setFixed(0);
            $clientFees->setVariable(0);
            $clientFees->setCname($type_in.'-'.$type_out);
            $clientFees->setClient($client);
            $clientFees->setCurrency($transaction->getCurrency());
            $em->persist($clientFees);
            $em->flush();
        }
        if(!$clientLimits){
            $clientLimits = new SwiftLimit();
            $clientLimits->setCname($type_in.'-'.$type_out);
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
            $clientLimitsCount->setCname($type_in.'-'.$type_out);
            $clientLimitsCount->setSingle(0);
            $clientLimitsCount->setDay(0);
            $clientLimitsCount->setWeek(0);
            $clientLimitsCount->setMonth(0);
            $clientLimitsCount->setYear(0);
            $clientLimitsCount->setTotal(0);
            $em->persist($clientLimitsCount);
            $em->flush();
        }

        $clientConfig = array(
            'fees'  =>  $clientFees,
            'limits'    =>  $clientLimits,
            'limits_count'    =>  $clientLimitsCount
        );

        return $clientConfig;

    }

    private function _checkLimits($amount, $type_out, Request $request){

        $dm = $this->get('doctrine_mongodb')->getManager();
        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');
        //TODO check limits for hal and sepa by phone date and iban
        if($type_out == 'halcash_es' || $type_out == 'halcash_pl'){
            $search = $request->request->get('phone');
            $start_time = new \MongoDate(strtotime(date('Y-m-d 00:00:00'))-31*24*3600);
            $finish_time = new \MongoDate();
            $result = $qb
                ->field('created')->gte($start_time)
                ->field('created')->lte($finish_time)
                ->field('method_out')->equals($type_out)
                ->field('status')->in(array('created','success'))
                ->where("function(){
                    if (typeof this.pay_out_info.phone !== 'undefined') {
                        if(String(this.pay_out_info.phone).indexOf('$search') > -1){
                            return true;
                        }
                    }
                    return false;
                }")

                ->getQuery()
                ->execute();

            $pending=0;

            foreach($result->toArray() as $d){
                $pending = $pending + $d->getAmount();
            }

            if($type_out == 'halcash_es'){
                if($amount + $pending > 300000) throw new HttpException(405, 'Limit exceeded');
            }else{
                if($amount + $pending > 1200000) throw new HttpException(405, 'Limit exceeded');
            }

        }elseif($type_out == 'sepa'){
            $search = $request->request->get('iban');
            $start_time = new \MongoDate(strtotime(date('Y-m-d 00:00:00'))-31*24*3600);
            $finish_time = new \MongoDate();
            $result = $qb
                ->field('created')->gte($start_time)
                ->field('created')->lte($finish_time)
                ->field('method_out')->equals($type_out)
                ->field('status')->in(array('created','success'))
                ->where("function(){
                    if (typeof this.pay_out_info.iban !== 'undefined') {
                        if(String(this.pay_out_info.iban).indexOf('$search') > -1){
                            return true;
                        }
                    }
                    return false;
                }")

                ->getQuery()
                ->execute();

            $pending=0;

            //die(print_r($result,true));
            foreach($result->toArray() as $d){
                $pending = $pending + $d->getAmount();
            }

            if($amount + $pending >= 300000) throw new HttpException(405, 'Limit exceeded');

        }

        return true;

    }

}


