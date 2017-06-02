<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 12/12/15
 * Time: 8:16 AM
 */

namespace Telepay\FinancialApiBundle\Controller\Transactions;

use DateInterval;
use DateTime;
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

        $logger = $this->get('transaction.logger');
        $logger->info('SWIFT transaction');

        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();

        $admin_id = $this->container->getParameter('admin_user_id');
        $client_default_id = $this->container->getParameter('swift_client_id_default');
        $rootGroupId = $this->container->getParameter('id_group_root');

        if($type_in == "fac" || $type_out == "fac"){
            $admin_id = $this->container->getParameter('admin_user_id_fac');
            $client_default_id = $this->container->getParameter('swift_client_id_default_fac');
        }

        if($type_in == "easypay"){
            $admin_id = $this->container->getParameter('admin_user_id_easypay');
            $client_default_id = $this->container->getParameter('swift_client_id_default_easypay');
        }
        $admin = $em->getRepository('TelepayFinancialApiBundle:User')->findOneById($admin_id);

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {

            //transaction authenticated with user/pass or credentials
            $user = $this->container->get('security.context')->getToken()->getUser();
            $logger->info('SWIFT IS_AUTHENTICATED_FULLY => ');
            $tokenManager = $this->container->get('fos_oauth_server.access_token_manager.default');
            $accessToken = $tokenManager->findTokenByToken(
                $this->container->get('security.context')->getToken()->getToken()
            );

            if($type_in == "easypay"){
                $client = $em->getRepository('TelepayFinancialApiBundle:Client')->findOneById($client_default_id);
            }else{
                $client = $accessToken->getClient();
            }

            if(!$user){
//                $user = $client->getUser();
            }

        }else{
            $logger->info('SWIFT IS_NOT_AUTHENTICATED');
            $user = $admin;
            $client = $em->getRepository('TelepayFinancialApiBundle:Client')->findOneById($client_default_id);

        }

        $logger->info('SWIFT type_in=> '.$type_in.' type_out=> '.$type_out.' admin_id=> '.$admin_id.' client_id=> '.$client_default_id);

        //check if user has this service and if is active
        $services = $client->getSwiftList();

        if(!$services) throw new HttpException(403,'Method not allowed');

        if(!in_array($type_in.'-'.$type_out.':1', $services)) throw new HttpException(403, 'Service temporally unavailable');

        if(!$request->request->has('amount')) throw new HttpException(404, 'Param amount not found');

        //check concept last 30 minutes in the same company
        $company = $client->getGroup();

//        $this->checkConceptLast30Min($request, $dm, $company);

        //TODO optional url_notification param

        $amount = $request->request->get('amount');

        //check amount
        if($amount == '') throw new HttpException(400, 'Amount is empty');

        //GET METHODS
        $cashInMethod = $this->container->get('net.telepay.in.'.$type_in.'.v'.$version_number);
        $cashOutMethod = $this->container->get('net.telepay.out.'.$type_out.'.v'.$version_number);

        //check email
        $email = $request->request->get('email')?$request->request->get('email'):'';
        if($email == '' && ($cashInMethod->getEmailRequired() || $cashOutMethod->getEmailRequired())) throw new HttpException(400, 'Email is required');
        $request->request->set('email', $email);

        $logger->info('SWIFT checkinG KYC');

        $request = $cashInMethod->checkKYC($request, "in");
        $request = $cashOutMethod->checkKYC($request, "out");

        if($request->request->get('premium') != '1') {
            $request->request->remove('premium');
        }
        else{
            $request = $this->_checkFaircoop($request, $type_in.'-'.$type_out, $cashOutMethod->getCurrency());
        }

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

        $safety_currency = 'MXN';
        if($type_in == 'safetypay'){
            if($request->request->has('currency') && $request->request->get('currency')!=""){
                $safety_currency = strtoupper($request->request->get('currency'));
                if($safety_currency != 'MXN'){
                    $amount_in = $this->_exchange($amount, $safety_currency, 'MXN');
                    $amount_out = $this->_exchange($amount_in, $cashInMethod->getCurrency(), $cashOutMethod->getCurrency());
                }
            }else{
                throw new HttpException(404, 'Param currency not found');
            }
        }

        $this->_checkLimits($amount_in, $amount_out, $type_in, $type_out, $request);

        $ip = $request->server->get('REMOTE_ADDR');

        //check if method is available
        $statusMethod = $em->getRepository('TelepayFinancialApiBundle:StatusMethod')->findOneBy(array(
            'method'    =>  $type_in.'-'.$type_out,
            'type'      =>  'swift'
        ));

        if($statusMethod->getStatus() != 'available') throw new HttpException(403, 'Swift method temporally unavailable');

        $logger->info('SWIFT GENERATING TRANSACTION');
        //Create transaction
        $transaction = new Transaction();
        $transaction->createFromRequest($request);
        $transaction->setFixedFee(0);
        $transaction->setVersion($version_number);
        $transaction->setEmailNotification($email);
        $transaction->setVariableFee(0);
        $transaction->setService($type_in.'-'.$type_out);
        if($user) $transaction->setUser($user->getId());
        if($request->request->has('premium') && $request->request->has('faircoop_admin_id')){
            $transaction->setFaircoopNode($request->request->get('faircoop_admin_id'));
        }
        $transaction->setGroup($client->getGroup()->getId());
        $transaction->setType('swift');
        $transaction->setMethodIn($type_in);
        $transaction->setMethodOut($type_out);
        $transaction->setClient($client->getId());
        $transaction->setIp($ip);

        //TODO remove when adapter is in the puta calle
        if(!$request->request->has('force')){
            $check_amount = $amount_in == 0?$amount:$amount_in;
            if($type_in == 'safetypay'){
                $methodInfo['range'] = 1;
                $methodInfo['max_value']+=50000;
                $methodInfo['min_value']-=5000;
            }
            if($check_amount < $methodInfo['min_value']) throw new HttpException(403, 'Amount must be greater.');
            if($check_amount % $methodInfo['range'] != 0) throw new HttpException(403, 'Amount must be multiple of '.$methodInfo['range']);
            if($check_amount > $methodInfo['max_value']) throw new HttpException(403, 'Max amount exceeded');
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
            $client_fee = round($clientFees->getFixed(),0);
        }else{
            $client_fee = round($amount_out * ($clientFees->getVariable()/100) + $clientFees->getFixed(),0);
        }

        $total_fee = $client_fee + $service_fee;

        $logger->info('SWIFT amount=>'.$amount.' client_fee=>'.$client_fee.' root_fee=>'.$service_fee);

        if($cashOutMethod->getCurrency() != $methodInfo['currency']){
            $total = round($amount_out - $total_fee, 0);
            $amount_out = $total;
            $request->request->remove('amount');
            $request->request->add(array(
                'amount'    =>  $amount_out
            ));
        }else{
            $total = round($amount + $total_fee, 0);
            if($request->request->has('faircoop_admin_id') && $request->request->get('faircoop_admin_id')>0){
                $amount_in = $this->_exchangeInversed($total, 'FAIRP', $cashOutMethod->getCurrency());
            }
            else{
                $amount_in = $this->_exchangeInversed($total, $cashInMethod->getCurrency(), $cashOutMethod->getCurrency());
            }
        }

        //ADD AND CHECK LIMITS
        $logger->info('SWIFT checking-adding limits');
        $clientLimitsCount = (new LimitAdder())->add($clientLimitsCount, $total);
        $checker = new LimitChecker();
        if(!$checker->leq($clientLimitsCount , $clientLimits))
            throw new HttpException(405,'Limit exceeded');

        $em->persist($clientLimitsCount);
        $em->flush();

        //GET PAYOUT INFO(parameters sent by user
        $pay_out_info = $cashOutMethod->getPayOutInfo($request);

        $transaction->setDataIn($pay_out_info);
        $transaction->setPayOutInfo($pay_out_info);

        //GET PAYOUT CURRENCY FOR THE TRANSACTION
        $transaction->setCurrency($cashOutMethod->getCurrency());
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

        if($type_in == 'safetypay'){
            $pay_in_info['amount'] = $amount;
            $pay_in_info['currency'] = $safety_currency;
        }

        if($type_in == 'easypay'){
            $pay_out_info['find_token'] = $pay_in_info['reference_code'];
            if($type_out == "btc"){
                $pay_in_info['reference'] = "BUY BITCOIN " . $pay_in_info['reference_code'];
            }
            else if($type_out == "fac"){
                $pay_in_info['reference'] = "BUY FAIRCOIN " . $pay_in_info['reference_code'];
            }
            else{
                $pay_in_info['reference'] = "CHIPCHAP CODE " . $pay_in_info['reference_code'];
            }
            $transaction->setDataIn($pay_out_info);
            $transaction->setPayOutInfo($pay_out_info);
        }

        if($type_in == 'sepa'){
            $dataIn = $transaction->getDataIn();
            $dataIn['email'] = $email;

            $user = $em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
                'email' =>  $email
            ));
            $dataIn['swift_user'] = $user->getId();
            $dataIn['name'] = $user->getName();
            $dataIn['document'] = $user->getKycValidations()->getDocument();
            $transaction->setDataIn($dataIn);
        }

        $price = round($total/($pay_in_info['amount']/1e8),0);

        $transaction->setPrice($price);

        $logger->info('SWIFT transaction price=>'.$price);

        $transaction->setPayInInfo($pay_in_info);
        $transaction->setDataOut($pay_in_info);
        $transaction->setStatus(Transaction::$STATUS_CREATED);
        $dm->persist($transaction);
        $dm->flush();

        if($request->request->has('faircoop_transaction_id')){
            $this->_activeFaircoop($request->request->get('faircoop_transaction_id'));
        }

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
            if($type_in == 'safetypay'){
                $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');
                $result = $qb
                    ->field('type')->equals('swift')
                    ->field('method_in')->equals($type_in)
                    ->field('method_out')->equals($type_out)
                    ->where("function(){
                    if (typeof this.pay_in_info.reference !== 'undefined') {
                        if(String(this.pay_in_info.reference).indexOf('$id') > -1){
                            return true;
                        }
                    }
                }")

                    ->getQuery()
                    ->execute();

                foreach($result->toArray() as $d){
                    $transaction = $d;
                }
            }else{
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

        $group = $user->getActiveGroup();
        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();

        if(!$request->request->has('option')) throw new HttpException(404, 'Missing parameter \'option\'');

        $option = $request->request->get('option');

        $transaction = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->findOneBy(array(
            'id'    =>  $id,
            'type'  =>  'swift',
            'group'  =>  $group->getId()
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
            if($transaction->getStatus() == Transaction::$STATUS_FAILED
                || $transaction->getStatus() == Transaction::$STATUS_CANCELLED
                || $transaction->getStatus() == Transaction::$STATUS_LOCKED
                || $transaction->getStatus()== Transaction::$STATUS_EXPIRED){

                $previous_status = $transaction->getStatus();

                //resend with changed params (phone and prefix done)

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
                if($payOutInfo['status'] == Transaction::$STATUS_SENT){
                    $transaction->setStatus(Transaction::$STATUS_SUCCESS);
                    $message = 'Transaction resend successfully';
                }else{
                    $transaction->setStatus(Transaction::$STATUS_FAILED);
                    $message = 'Transaction has failed';
                }

                $transaction->setUpdated(new \DateTime());

                $dm->persist($transaction);
                $dm->flush();

                //if previous status == failed generate fees transactions
                if($previous_status == Transaction::$STATUS_FAILED){
                    //check if exists previous fees
                    if(!$this->_existPreviousFee($transaction))
                        $this->_generateFees($transaction, $transaction->getMethodIn(), $transaction->getMethodOut());
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

                if($transaction->getFaircoopNode() && $transaction->getFaircoopNode()>0) {
                    $exchanger = $this->container->get('net.telepay.commons.exchange_manipulator');
                    $amount = $transaction->getAmount();
                    $faircoopNode = $transaction->getFaircoopNode();
                    $userGroup = $em->getRepository('TelepayFinancialApiBundle:Group')->find($faircoopNode);
                    $from = $method_in->getCurrency();
                    $to = $method_out->getCurrency();
                    $amount_ex = $exchanger->exchange($amount, $to==Currency::$FAC?Currency::$FAIRP:$to, $from==Currency::$FAC?Currency::$FAIRP:$from);
                    $exchanger->doExchange($amount_ex, $from, $to, $userGroup, $user, true);
                }

                //get fee transactions to refund.
                $this->_returnFeesV2($transaction, $transaction->getMethodIn(), $transaction->getMethodOut());

            }else{
                throw new HttpException(403, 'Transaction can\'t be refund');
            }

        }elseif($option == 'recheck'){
            if(($transaction->getStatus() == Transaction::$STATUS_EXPIRED && $payInInfo['status'] == 'expired') || ($payInInfo['status'] == 'success' && $payOutInfo['status'] == 'failed' || $payOutInfo['status'] == false)){
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

        $transaction = $this->get('notificator')->notificate($transaction);
        $dm->persist($transaction);
        $dm->flush();

        return $this->restV2(204,"ok", $message);

    }

    public function hello(Request $request, $version_number, $currency){
        if($version_number == '2'){
            return $this->helloV2($request, $version_number, $currency);
        }

        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();

        $admin_id = $this->container->getParameter('admin_user_id');
        $client_default_id = $this->container->getParameter('swift_client_id_default');
        if($currency == "fac"){
            $admin_id = $this->container->getParameter('admin_user_id_fac');
            $client_default_id = $this->container->getParameter('swift_client_id_default_fac');
        }

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

                    $exchange = round($exchange + ($exchange * ($variable_fee/100)) + $fixed_fee, 0);
                }else{
                    $scale_in = Currency::$SCALE[$currency_in];

                    $amount = pow(10,$scale_in);

                    $exchange = $this->_exchange($amount , $currency_in, $currency_out);

                    $exchange = round($exchange - ($exchange * ($variable_fee/100)) - $fixed_fee, 0);
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

    public function helloV2(Request $request, $version_number, $currency){

        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();
        $version_number = '1';

        $admin_id = $this->container->getParameter('admin_user_id');
        $client_default_id = $this->container->getParameter('swift_client_id_default');
        if($currency == "fac"){
            $admin_id = $this->container->getParameter('admin_user_id_fac');
            $client_default_id = $this->container->getParameter('swift_client_id_default_fac');
        }

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
//                $user = $client->getUser();
            }

        }else{
//            $user = $admin;
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

                if($currency_out == 'BTC' || $currency_out == 'FAC'){
                    $scale_in = Currency::$SCALE[$currency_out];

                    $amount = pow(10,$scale_in);

                    if($cashInMethod->getCname() == "safetypay"){
                        $exchange = array();
                        $list_currencies = array("MXN", "EUR", "USD", "PLN");
                        foreach($list_currencies as $cur){
                            $exchange_value = $this->_exchange($amount , $currency_out, $cur);
                            $exchange[$cur] = round($exchange_value + ($exchange_value * ($variable_fee/100)) + $fixed_fee, 0);
                        }
                    }
                    else{
                        $exchange = $this->_exchange($amount , $currency_out, $currency_in);

                        $exchange = round($exchange + ($exchange * ($variable_fee/100)) + $fixed_fee, 0);
                    }
                }else{
                    $scale_in = Currency::$SCALE[$currency_in];

                    $amount = pow(10,$scale_in);

                    $exchange = $this->_exchange($amount , $currency_in, $currency_out);

                    $exchange = round($exchange - ($exchange * ($variable_fee/100)) - $fixed_fee, 0);
                }

                if($clientLimits->getSingle() > 0 && $clientLimits->getSingle() <= $swiftInfo['max_value']){
                    $max = $clientLimits->getSingle();
                }else{
                    $max = $swiftInfo['max_value'];
                }

                $values = array();
                if($cashInMethod->getCname() == "safetypay"){
                    $list_currencies = array("MXN", "EUR", "USD", "PLN");
                    foreach($list_currencies as $cur){
                        $cur_values = array();
                        if($cur == "MXN"){
                            $min = $swiftInfo['min_value'];
                            $range = $swiftInfo['range'];
                            $max_cur = $max;
                        }
                        else {
                            $min = $this->_exchange($swiftInfo['min_value'], $currency_in, $cur);
                            $range = $this->_exchange($swiftInfo['range'], $currency_in, $cur);
                            $max_cur = $this->_exchange($max, $currency_in, $cur);
                        }
                        for($i = $min; $i <= $max_cur; $i+=$range){
                            array_push($cur_values, $this->_roundUpToAny($i, 500));
                        }
                        $values[$cur] = $cur_values;
                    }
                }
                else{
                    for($i = $swiftInfo['min_value'];$i <= $max; $i+=$swiftInfo['range']){
                        array_push($values, $i);
                    }
                }

                $response[$service] = array(
                    'orig'  =>  $cashInMethod->getName(),
                    'dst'   =>  $cashOutMethod->getName(),
                    'countries' =>  $swiftInfo['countries'],
                    'status'    =>  ($status == 1) ? 'available' : 'unavailable',
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

    private function _roundUpToAny($n,$x=5) {
        return (ceil($n)%$x === 0) ? ceil($n) : round(($n+$x/2)/$x)*$x;
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

    private function _exchangeInversed($amount,$curr_in,$curr_out){
        $dm=$this->getDoctrine()->getManager();
        $exchangeRepo=$dm->getRepository('TelepayFinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$curr_in,'dst'=>$curr_out),
            array('id'=>'DESC')
        );
        if(!$exchange) throw new HttpException(404,'Exchange not found -> '.$curr_in.' TO '.$curr_out);
        $price = 1.0/$exchange->getPrice();
        $total = round($amount * $price,0);
        return $total;
    }

    private function _generateFees(Transaction $transaction, $method_in, $method_out){

        $em = $this->getDoctrine()->getManager();
        $dm = $this->get('doctrine_mongodb')->getManager();

        $client = $em->getRepository('TelepayFinancialApiBundle:Client')->find($transaction->getClient());

        $clientGroup = $client->getGroup();
        $amount = $transaction->getAmount();

        $root_id = $this->container->getParameter('admin_user_id');
        $rootGroupId = $this->container->getParameter('id_group_root');
        $root = $em->getRepository('TelepayFinancialApiBundle:User')->find($root_id);
        $rootGroup = $em->getRepository('TelepayFinancialApiBundle:Group')->find($rootGroupId);

        //get configuration(method)
        $swift_config = $this->container->get('net.telepay.config.'.$method_in.'.'.$method_out);
        $methodFees = $swift_config->getFees();

        //get client fees (fixed & variable)
        $clientFees = $em->getRepository('TelepayFinancialApiBundle:SwiftFee')->findOneBy(array(
            'client'    =>  $client,
            'cname' =>  $method_in.'-'.$method_out
        ));

        $client_fee = ($amount * ($clientFees->getVariable()/100)) + $clientFees->getFixed();
        $service_fee = ($amount * ($methodFees->getVariable()/100)) + $methodFees->getFixed();

        //client fees goes to the user
        $userFee = new Transaction();
        if($transaction->getUser()) $userFee->setUser($transaction->getUser());
        $userFee->setGroup($transaction->getGroup());
        $userFee->setType(Transaction::$TYPE_FEE);
        $userFee->setCurrency($transaction->getCurrency());
        $userFee->setScale($transaction->getScale());
        $userFee->setAmount($client_fee);
        $userFee->setFixedFee($clientFees->getFixed());
        $userFee->setVariableFee($amount * ($clientFees->getVariable()/100));
        $userFee->setService($method_in.'-'.$method_out);
        $userFee->setMethod($method_in.'-'.$method_out);
        $userFee->setStatus(Transaction::$STATUS_SUCCESS);
        $userFee->setTotal($client_fee);
        $userFee->setDataIn(array(
            'previous_transaction'  =>  $transaction->getId(),
            'transaction_amount'    =>  $transaction->getAmount(),
            'total_fee' =>  $client_fee + $service_fee
        ));
        $userFee->setFeeInfo(array(
            'previous_transaction'  =>  $transaction->getId(),
            'previous_amount'    =>  $transaction->getAmount(),
            'amount'    =>  $client_fee,
            'currency'  =>  $transaction->getCurrency(),
            'scale'     =>  $transaction->getScale(),
            'concept'           =>  $method_in.'-'.$method_out.'->fee',
            'status'    =>  Transaction::$STATUS_SUCCESS
        ));

        $userFee->setClient($client);

        //service fees goes to root
        $rootFee = new Transaction();
        $rootFee->setUser($root->getId());
        $rootFee->setGroup($rootGroupId);
        $rootFee->setType(Transaction::$TYPE_FEE);
        $rootFee->setCurrency($transaction->getCurrency());
        $rootFee->setScale($transaction->getScale());
        $rootFee->setAmount($service_fee);
        $rootFee->setFixedFee($methodFees->getFixed());
        $rootFee->setVariableFee($amount * ($methodFees->getVariable()/100));
        $rootFee->setService($method_in.'-'.$method_out);
        $rootFee->setMethod($method_in.'-'.$method_out);
        $rootFee->setStatus('success');
        $rootFee->setTotal($service_fee);
        $rootFee->setDataIn(array(
            'previous_transaction'  =>  $transaction->getId(),
            'transaction_amount'    =>  $transaction->getAmount(),
            'total_fee' =>  $client_fee + $service_fee
        ));
        $rootFee->setFeeInfo(array(
            'previous_transaction'  =>  $transaction->getId(),
            'previous_amount'    =>  $transaction->getAmount(),
            'scale'     =>  $transaction->getScale(),
            'concept'           =>  $method_in.'-'.$method_out.'->fee',
            'amount' =>  $service_fee,
            'status'    =>  Transaction::$STATUS_SUCCESS,
            'currency'  =>  $transaction->getCurrency()
        ));

        $rootFee->setClient($client);
        $dm->persist($userFee);
        $dm->persist($rootFee);
        $dm->flush();

        //get wallets and add fees to both, user and wallet
        $current_wallet = $rootGroup->getWallet($rootFee->getCurrency());

        $current_wallet->setAvailable($current_wallet->getAvailable() + $service_fee);
        $current_wallet->setBalance($current_wallet->getBalance() + $service_fee);

        $em->persist($current_wallet);
        $em->flush();

        $current_wallet_client = $clientGroup->getWallet($userFee->getCurrency());

        $current_wallet_client->setAvailable($current_wallet_client->getAvailable() + $client_fee);
        $current_wallet_client->setBalance($current_wallet_client->getBalance() + $client_fee);

        $em->persist($current_wallet_client);
        $em->flush();

    }

    private function _returnFeesV2(Transaction $transaction, $method_in, $method_out){

        $em = $this->getDoctrine()->getManager();
        $dm = $this->get('doctrine_mongodb')->getManager();

        $client = $em->getRepository('TelepayFinancialApiBundle:Client')->find($transaction->getClient());

        $clientGroup = $client->getGroup();
        $amount = $transaction->getAmount();

        $root_id = $this->container->getParameter('admin_user_id');
        $rootGroupId = $this->container->getParameter('id_group_root');
        $root = $em->getRepository('TelepayFinancialApiBundle:User')->find($root_id);
        $rootGroup = $em->getRepository('TelepayFinancialApiBundle:Group')->find($rootGroupId);

        //get configuration(method)
        $swift_config = $this->container->get('net.telepay.config.'.$method_in.'.'.$method_out);
        $methodFees = $swift_config->getFees();

        //get client fees (fixed & variable)
        $clientFees = $em->getRepository('TelepayFinancialApiBundle:SwiftFee')->findOneBy(array(
            'client'    =>  $client,
            'cname' =>  $method_in.'-'.$method_out
        ));

        $client_fee = ($amount * ($clientFees->getVariable()/100)) + $clientFees->getFixed();
        $service_fee = ($amount * ($methodFees->getVariable()/100)) + $methodFees->getFixed();

        $userGroup = $em->getRepository('TelepayFinancialApiBundle:Group')->find($transaction->getGroup());
        $balancer = $this->get('net.telepay.commons.balance_manipulator');

        if($client_fee > 0){
            //client fees goes to the user
            $userFee = new Transaction();
            if($transaction->getUser()) $userFee->setUser($transaction->getUser());
            $userFee->setGroup($transaction->getGroup());
            $userFee->setType(Transaction::$TYPE_FEE);
            $userFee->setCurrency($transaction->getCurrency());
            $userFee->setScale($transaction->getScale());
            $userFee->setAmount($client_fee);
            $userFee->setFixedFee($clientFees->getFixed());
            $userFee->setVariableFee($amount * ($clientFees->getVariable()/100));
            $userFee->setService($method_in.'-'.$method_out);
            $userFee->setMethod($method_in.'-'.$method_out);
            $userFee->setStatus(Transaction::$STATUS_SUCCESS);
            $userFee->setTotal(-$client_fee);
            $userFee->setDataIn(array(
                'previous_transaction'  =>  $transaction->getId(),
                'transaction_amount'    =>  $transaction->getAmount(),
                'total_fee' =>  $client_fee + $service_fee
            ));
            $userFee->setFeeInfo(array(
                'previous_transaction'  =>  $transaction->getId(),
                'previous_amount'    =>  $transaction->getAmount(),
                'amount'    =>  -$client_fee,
                'currency'  =>  $transaction->getCurrency(),
                'scale'     =>  $transaction->getScale(),
                'concept'           =>  'refund '.$method_in.'-'.$method_out.'->fee',
                'status'    =>  Transaction::$STATUS_SUCCESS
            ));

            $userFee->setClient($client);
            $dm->persist($userFee);
            $current_wallet_client = $clientGroup->getWallet($userFee->getCurrency());

            $current_wallet_client->setAvailable($current_wallet_client->getAvailable() - $client_fee);
            $current_wallet_client->setBalance($current_wallet_client->getBalance() - $client_fee);

            $balancer->addBalance($userGroup, -$client_fee, $transaction);
        }


        if($service_fee > 0){
            //service fees goes to root
            $rootFee = new Transaction();
            $rootFee->setUser($root->getId());
            $rootFee->setGroup($rootGroupId);
            $rootFee->setType(Transaction::$TYPE_FEE);
            $rootFee->setCurrency($transaction->getCurrency());
            $rootFee->setScale($transaction->getScale());
            $rootFee->setAmount($service_fee);
            $rootFee->setFixedFee($methodFees->getFixed());
            $rootFee->setVariableFee($amount * ($methodFees->getVariable()/100));
            $rootFee->setService($method_in.'-'.$method_out);
            $rootFee->setMethod($method_in.'-'.$method_out);
            $rootFee->setStatus(Transaction::$STATUS_SUCCESS);
            $rootFee->setTotal(-$service_fee);
            $rootFee->setDataIn(array(
                'previous_transaction'  =>  $transaction->getId(),
                'transaction_amount'    =>  $transaction->getAmount(),
                'total_fee' =>  $client_fee + $service_fee
            ));
            $rootFee->setFeeInfo(array(
                'previous_transaction'  =>  $transaction->getId(),
                'previous_amount'    =>  $transaction->getAmount(),
                'scale'     =>  $transaction->getScale(),
                'concept'           =>  'refund ' .$method_in.'-'.$method_out.'->fee',
                'amount' =>  -$service_fee,
                'status'    =>  Transaction::$STATUS_SUCCESS,
                'currency'  =>  $transaction->getCurrency()
            ));

            $rootFee->setClient($client);
            $dm->persist($rootFee);
            //get wallets and add fees to both, user and admin
            $current_wallet = $rootGroup->getWallet($rootFee->getCurrency());
            $current_wallet->setAvailable($current_wallet->getAvailable() - $service_fee);
            $current_wallet->setBalance($current_wallet->getBalance() - $service_fee);

            $balancer->addBalance($rootGroup, -$service_fee, $transaction);
        }

        $dm->flush();
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
                                if (typeof this.fee_info !== 'undefined') {
                                    if (typeof this.fee_info.previous_transaction !== 'undefined') {
                                        if(String(this.fee_info.previous_transaction).indexOf('$transaction_id') > -1){
                                            return true;
                                        }
                                    }
                                }else{
                                    if (typeof this.dataIn !== 'undefined') {
                                        if (typeof this.dataIn.previous_transaction !== 'undefined') {
                                            if(String(this.dataIn.previous_transaction).indexOf('$transaction_id') > -1){
                                                return true;
                                            }
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

            $feeInfo = $fee->getFeeInfo();
            $feeInfo['status'] = 'refund';
            $fee->setFeeInfo($feeInfo);

            $dm->persist($fee);
            $dm->flush();

            //getWallet and discount fee
            $clientGroup = $em->getRepository('TelepayFinancialApiBundle:Group')->find($transaction->getGroup());
            $userWallets = $clientGroup->getWallets();
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

        //TODO crec que faltara descontarli la fee al admin tab

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

    private function _checkLimits($amount_in, $amount_out, $type_in, $type_out, Request $request){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');

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
                if($amount_out + $pending > 300000) throw new HttpException(405, 'Limit exceeded');
            }else{
                if($amount_out + $pending > 1200000) throw new HttpException(405, 'Limit exceeded');
            }
        }
        elseif($type_out == 'sepa'){
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

            if($amount_out + $pending >= 300000) throw new HttpException(405, 'Limit exceeded');

        }
        elseif($type_in == 'easypay' || $type_in == 'teleingreso'){
            $search = $request->request->get('email');
            $start_time = new \MongoDate(strtotime(date('Y-m-d 00:00:00'))-31*24*3600);
            $finish_time = new \MongoDate();
            $result = $qb
                ->field('created')->gte($start_time)
                ->field('created')->lte($finish_time)
                ->field('method_in')->equals($type_in)
                ->field('status')->in(array('created','success'))
                ->where("function(){
                    if (typeof this.email_notification !== 'undefined') {
                        if(String(this.email_notification).indexOf('$search') > -1){
                            return true;
                        }
                    }
                    return false;
                }")

                ->getQuery()
                ->execute();

            $pending=0;
            $count_success = 0;
            foreach($result->toArray() as $d){
                $payInInfo = $d->getPayInInfo();
                if($d->getStatus() == 'success'){
                    $count_success++;
                }
                $pending = $pending + $payInInfo['amount'];
            }
            if($amount_in + $pending > 300000) throw new HttpException(405, 'Limit exceeded');

            if($type_in == 'teleingreso'){
                $search = $request->request->get('email');
                $start_time = new \MongoDate(strtotime(date('Y-m-d 00:00:00')));
                $result = $qb
                    ->field('created')->gte($start_time)
                    ->field('method_in')->equals($type_in)
                    ->field('status')->in(array('created','success'))
                    ->where("function(){
                    if (typeof this.email_notification !== 'undefined') {
                        if(String(this.email_notification).indexOf('$search') > -1){
                            return true;
                        }
                    }
                    return false;
                }")

                    ->getQuery()
                    ->execute();

                $pending=0;

                foreach($result->toArray() as $d){
                    if($d->getStatus() == 'success'){
                        $count_success--;
                    }
                    $payInInfo = $d->getPayInInfo();
                    $pending = $pending + $payInInfo['amount'];
                }

                $count_success=($count_success<0)?0:$count_success;
                $count_success=($count_success>4)?4:$count_success;
                $day_limit = 20000 * ($count_success +1);

                if($amount_in + $pending > $day_limit) throw new HttpException(405, 'Day Limit exceeded(' . $day_limit/100 . ' euros)');
            }
        }

        return true;
    }

    public function _checkFaircoop($request, $method, $curr_out){
        $fairApiDriver = $this->get('net.telepay.driver.fairtoearth');
        $email = $request->request->get('email');
        $amount = $request->request->get('amount');
        $checkbalance = $fairApiDriver->checkBalance($email, $method, $amount);
        if($checkbalance->status == 'error'){
            throw new HttpException(400, $checkbalance->message);
        }
        else{
            if(isset($checkbalance->data->url_notification) && isset($checkbalance->data->company_id) && $checkbalance->data->company_id>0){
                $request->request->set('url_notification', $checkbalance->data->url_notification);
                $admin_id = $checkbalance->data->company_id;
                $request->request->set('faircoop_admin_id', $admin_id);
                $request->request->set('faircoop_transaction_id', $checkbalance->data->id);
            }
            else{
                throw new HttpException(400, "Partner not found");
            }
        }

        $em = $this->getDoctrine()->getManager();
        $admin_wallet = $em->getRepository('TelepayFinancialApiBundle:UserWallet')->findOneBy(array(
            'group' => $admin_id,
            'currency' => $curr_out
        ));
        $balance = $admin_wallet->getBalance();
        if($amount > $balance){
            throw new HttpException(400, "Admin without enough balance");
        }

        $dm = $this->get('doctrine_mongodb')->getManager();
        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');
        $result = $qb
            ->field('currency')->equals($curr_out)
            ->field('status')->in(array('created','received'))
            ->field('faircoopNode')->equals(intval($admin_id))
            ->getQuery()
            ->execute();

        $pending=0;
        foreach($result->toArray() as $d){
            $pending = $pending + $d->getAmount();
        }
        if($amount + $pending > $balance ) {
            throw new HttpException(403, "Admin without enough balance");
        }
        return $request;
    }

    public function _activeFaircoop($id){
        $fairApiDriver = $this->get('net.telepay.driver.fairtoearth');
        $fairApiDriver->active($id);
    }

    public function notification(Request $request, $version_number, $type_in, $type_out){

    }

    private function checkConceptLast30Min(Request $request, $dm, $company){
        if(!$request->request->has('concept')) throw new HttpException(404, 'Param concept not found');
        $concept = $request->request->get('concept');
        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');
        $fecha = new DateTime();
        $fecha->sub(new DateInterval('PT30M'));
        $start_time = new \MongoDate($fecha->getTimestamp());
        $finish_time = new \MongoDate();
        $result = $qb
            ->field('type')->equals('swift')
            ->field('group')->equals($company->getId())
            ->field('created')->gte($start_time)
            ->field('created')->lte($finish_time)
            ->where("function(){
                    if (typeof this.pay_out_info.concept !== 'undefined') {
                        if(String(this.pay_out_info.concept).indexOf('$concept') > -1){
                            return true;
                        }
                    }
                }")

            ->getQuery()
            ->execute();

        $total = count($result);

        if($total >=1) throw new HttpException(409, 'concept duplicated before 30 minutes');
    }

    private function _existPreviousFee(Transaction $transaction){

        $dm = $dm = $this->get('doctrine_mongodb')->getManager();
        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');
        $transaction_id = $transaction->getId();
        $transactions = $qb
            ->field('type')->equals(Transaction::$TYPE_FEE)
            ->where("function() {
                                if (typeof this.fee_info !== 'undefined') {
                                    if (typeof this.fee_info.previous_transaction !== 'undefined') {
                                        if(String(this.fee_info.previous_transaction).indexOf('$transaction_id') > -1){
                                            return true;
                                        }
                                    }
                                }

                                return false;
                                }")
            ->getQuery()
            ->execute();

        if(count($transactions) >=1){
            return true;
        }else{
            return false;
        }

    }

}


