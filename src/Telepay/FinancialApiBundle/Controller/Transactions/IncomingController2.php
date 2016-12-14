<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/22/15
 * Time: 8:16 PM
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
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\LimitCount;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Entity\User;
use Telepay\FinancialApiBundle\Entity\UserWallet;

class IncomingController2 extends RestApiController{

    /**
     * @Rest\View
     */
    public function make(Request $request, $version_number, $type, $method_cname){
        $user = $this->get('security.context')->getToken()->getUser();
        if (!$this->get('security.context')->isGranted('ROLE_WORKER')) throw new HttpException(403, 'You don\' have the necessary permissions');
        $group = $this->_getCurrentCompany($user);
        //check if this user has this company
        $this->_checkPermissions($user, $group);
        $params = $request->request->all();
        return $this->createTransaction($params, $version_number, $type, $method_cname, $user->getId(), $group, $request->getClientIp());
    }

    public function createTransaction($data, $version_number, $type, $method_cname, $user_id, $group, $ip){
        //TODO inyectar driver con las credenciales correspondientes al DbWallet
        $logger = $this->get('transaction.logger');
        $logger->info('Incomig transaction...Method-> '.$method_cname.' Direction -> '.$type);

        $method = $this->get('net.telepay.'.$type.'.'.$method_cname.'.v'.$version_number);

        $method_list = $group->getMethodsList();

        if (!in_array($method_cname.'-'.$type, $method_list)) {
            throw $this->createAccessDeniedException();
        }

        $logger->info('Get mongo service');

        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();

        //check if method is available
        $statusMethod = $em->getRepository('TelepayFinancialApiBundle:StatusMethod')->findOneBy(array(
            'method'    =>  $method_cname,
            'type'      =>  $type
        ));

        if($statusMethod->getStatus() != 'available') throw new HttpException(403, 'Method temporally unavailable');

        //Si viene del scheduled no hay IP
        $transaction = Transaction::createFromRequestIP($ip);
        $transaction->setService($method_cname);
        $transaction->setMethod($method_cname);
        $admin_id = $this->container->getParameter('admin_user_id');
        $transaction->setUser($user_id==-1?$admin_id:$user_id);
        $transaction->setGroup($group->getId());
        $transaction->setVersion($version_number);
        $transaction->setType($type);
        $dm->persist($transaction);

        if(array_key_exists('concept', $data) && $data['concept']!=''){
            $concept = $data['concept'];
        }else{
            throw new HttpException(400, 'Param concept not found');
        }

        if(array_key_exists('url_notification', $data)) $url_notification = $data['url_notification'];
        else $url_notification = '';

        if(array_key_exists('amount', $data) && $data['amount']!=''){
            $amount = $data['amount'];
        }
        else{
            throw new HttpException(400, 'Param amount not found');
        }

        //TODO get currency_in and currency_out
        $exchange_done = false;
        $exchange_out = false;
        if((array_key_exists('currency_in', $data) && $data['currency_in'] != '' || array_key_exists('currency_out', $data) && $data['currency_out'] != '')
            && ($method_cname == 'btc' || $method_cname == 'fac') && $type = 'in'){

            $request_amount = $amount;
            $cur_in = $method->getCurrency();
            $cur_out = $method->getCurrency();
            if($data['currency_in']) $cur_in = strtoupper($data['currency_in']);
            if($data['currency_out']) $cur_out = strtoupper($data['currency_out']);

            $logger->info('Currency IN-> '.$cur_in.' Currency OUT-> '.$cur_out.' Method cur => '.$method->getCurrency());
            if(strtoupper($cur_in) != $method->getCurrency()){
                $exchange_done = true;
                $method_currency = $method->getCurrency();
                if($method_currency == 'FAC' && $group->getId() == '211'){
                    $method_currency = 'FAIRP';
                }
                $amount = $this->get('net.telepay.commons.exchange_manipulator')->exchangeInverse($amount, $cur_in, $method_currency);
            }
            if(strtoupper($cur_out) != $method->getCurrency() ){
                $exchange_out = true;
            }
        }
        unset($data['currency']);

        $logger->info('Incomig transaction...getPaymentInfo');

        //Aqui hay que distinguir entre in i out
        //para in es getPayInInfo y para out es getPayOutInfo
        if($type == 'in'){

            $dataIn = array(
                'amount'    =>  $amount,
                'concept'   =>  $concept,
                'url_notification'  =>  $url_notification
            );
            if($exchange_done){
                $dataIn['request_amount'] = $request_amount;
                $dataIn['request_currency_in'] = $cur_in;
            }
            if($exchange_out){
                $dataIn['request_currency_out'] = $cur_out;
            }
            $payment_info = $method->getPayInInfo($amount);
            $payment_info['concept'] = $concept;
            $transaction->setPayInInfo($payment_info);

        }else{
            $payment_info = $method->getPayOutInfoData($data);
            $transaction->setPayOutInfo($payment_info);
            $dataIn = array(
                'amount'    =>  $amount,
                'concept'   =>  $concept,
                'url_notification'  =>  $url_notification
            );
            if($exchange_done){
                $dataIn['request_amount'] = $request_amount;
                $dataIn['request_currency_in'] = $cur_in;
            }
            if($exchange_out){
                $dataIn['request_currency_out'] = $cur_out;
            }
        }

        $transaction->setDataIn($dataIn);

        $logger->info('Incomig transaction...FEES');

        //TODO crear FeeManipulator

        $group_commission = $this->_getFees($group, $method);

        $amount = $dataIn['amount'];
        $transaction->setAmount($amount);

        //add commissions to check
        $fixed_fee = $group_commission->getFixed();
        $variable_fee = round(($group_commission->getVariable()/100) * $amount, 0);
        $total_fee = $fixed_fee + $variable_fee;

        //add fee to transaction
        $transaction->setVariableFee($variable_fee);
        $transaction->setFixedFee($fixed_fee);
        $dm->persist($transaction);

        //check if is cash-out
        if($type == 'out'){
            //le cambiamos el signo para guardarla i marcarla como salida en el wallet
            $transaction->setTotal(-$amount);
            $total = $amount + $variable_fee + $fixed_fee;
        }else{
            $total = $amount - $variable_fee - $fixed_fee;
            $transaction->setTotal($amount);
        }

        $logger->info('Incomig transaction...LIMITS');

        //obtain group limitsCount for this method
        $groupLimitCount = $this->_getLimitCount($group, $method);

        //get limit manipulator
        $limitManipulator = $this->get('net.telepay.commons.limit_manipulator');

        //obtain group limit
        $group_limit = $limitManipulator->getMethodLimits($group, $method);

        //update group limit counters
        $newGroupLimitCount = (new LimitAdder())->add( $groupLimitCount, $total);

        $checker = new LimitChecker();

        if(!$checker->leq($newGroupLimitCount, $group_limit)) throw new HttpException(405,'Limit exceeded');

        //obtain wallet and check founds for cash_out services for this group
        $wallet = $group->getWallet($method->getCurrency());

        $transaction->setCurrency($method->getCurrency());
        $transaction->setScale($wallet->getScale());

        //******    CHECK IF THE TRANSACTION IS CASH-OUT     ********
        if($type == 'out'){
            $logger->info('Incomig transaction...OUT');
            $logger->info('Available = ' . $wallet->getAvailable() .  " TOTAL: " . $total);
            if($wallet->getAvailable() <= $total) throw new HttpException(405,'Not founds enough');
                    //Bloqueamos la pasta en el wallet
            $wallet->setAvailable($wallet->getAvailable() - $total);
            $em->persist($wallet);
            $em->flush();

            $logger->info('Incomig transaction...SEND');

            try {
                $payment_info = $method->send($payment_info);
            }catch (Exception $e){
                $logger->error('Incomig transaction...ERROR '.$e->getMessage());

                if($e->getCode() >= 500){
                    $transaction->setStatus(Transaction::$STATUS_FAILED);
                }else{
                    $transaction->setStatus( Transaction::$STATUS_ERROR );
                }
                //desbloqueamos la pasta del wallet
                $wallet->setAvailable($wallet->getAvailable() + $total);
                //descontamos del counter
                $newGroupLimitCount = (new LimitAdder())->restore( $groupLimitCount, $total);
                $em->persist($wallet);
                $em->flush();
                $dm->persist($transaction);
                $dm->flush();

                $this->container->get('notificator')->notificate($transaction);

                throw new HttpException($e->getCode(), $e->getMessage());

            }
            $logger->info('Incomig transaction...PAYMENT STATUS: '.$payment_info['status']);

            $transaction->setPayOutInfo($payment_info);
            $dm->persist($transaction);
            $dm->flush();

            //pay fees and dealer always and set new balance
            if( $payment_info['status'] == 'sent' || $payment_info['status'] == 'sending'){

                if($payment_info['status'] == 'sent') $transaction->setStatus(Transaction::$STATUS_SUCCESS);
                else $transaction->setStatus('sending');

                $dm->persist($transaction);
                $dm->flush();

                $this->container->get('notificator')->notificate($transaction);

                //restar al grupo el amount + comisiones
                $wallet->setBalance($wallet->getBalance() - $total);

                //insert new line in the balance fro this group
                $this->get('net.telepay.commons.balance_manipulator')->addBalance($group, -$amount, $transaction);

                $em->persist($wallet);
                $em->flush();

                if($payment_info['status'] == 'sending'){
                    $method->sendMail($transaction->getId(), $transaction->getType(), $payment_info);
                }


                if( $total_fee != 0){
                    //nueva transaccion restando la comision al user
                    $dealer = $this->container->get('net.telepay.commons.fee_deal');
                    try{
                        $logger->info('Init Dealer: ' . $transaction->getAmount() . " : " . $wallet->getBalance() . " : " . $total);
                        $dealer->createFees($transaction, $wallet);
                    }catch (HttpException $e){
                        throw $e;
                    }
//                    try{
//                        //TODO modificar dealer
//                        $logger->info('Init Dealer: ' . $transaction->getAmount() . " : " . $wallet->getBalance() . " : " . $total);
//                        $this->_dealer($transaction, $wallet);
//                    }catch (HttpException $e){
//                        throw $e;
//                    }
                }

            }else{
                $transaction->setStatus($payment_info['status']);
                //desbloqueamos la pasta del wallet
                $wallet->setAvailable($wallet->getAvailable() + $total);
                $em->persist($wallet);
                $em->flush();
                $dm->persist($transaction);
                $dm->flush();

                $this->container->get('notificator')->notificate($transaction);

            }

        }else{     //CASH - IN
            $logger->info('Incomig transaction...IN');

            $dm->persist($transaction);
            $dm->flush();

            $transaction = $this->get('notificator')->notificate($transaction);
            $em->flush();

            $transaction->setUpdated(new \DateTime());

            $dm->persist($transaction);
            $dm->flush();

        }

        $logger->info('Incomig transaction...FINAL');

        if($transaction == false) throw new HttpException(500, "oOps, some error has occurred within the call");

        if($user_id == -1){
            return 'Transaction generate successfully';
        }else{
            return $this->methodTransaction(201, $transaction, "Done");
        }

    }

    /**
     * @Rest\View
     */
    public function update(Request $request, $version_number, $type, $method_cname, $id){

        $method = $this->get('net.telepay.'.$type.'.'.$method_cname.'.v'.$version_number);

        if(!$this->get('security.context')->isGranted('ROLE_WORKER')) throw new HttpException(403, 'You don\' have the necessary permissions');

        $logger = $this->get('transaction.logger');
        $logger->info('Update transaction');

        $user = $this->get('security.context')->getToken()->getUser();
        $group = $this->_getCurrentCompany($user);
        $this->_checkPermissions($user, $group);

        $method_list = $group->getMethodsList();

        if (!in_array($method_cname.'-'.$type, $method_list)) {
            throw $this->createAccessDeniedException();
        }

        $data = $request->request->all();

        $mongo = $this->get('doctrine_mongodb')->getManager();
        $transaction = $mongo->getRepository('TelepayFinancialApiBundle:Transaction')->findOneBy(array(
            'id'        =>  $id,
            'method'    =>  $method_cname,
            'group'      =>  $group->getId(),
            'type'      =>  $type
        ));

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        //retry=true y cancel=true aqui
        if( isset( $data['retry'] ) || isset ( $data ['cancel'] )){

            if($transaction->getType() != 'out') throw new HttpException(403, 'Forbidden action for this transaction ');

            $em = $this->getDoctrine()->getManager();

            $currency = $transaction->getCurrency();

            //Search wallet
            $current_wallet = $group->getWallet($currency);

            if($current_wallet == null) throw new HttpException(404,'Wallet not found');

            $amount = $transaction->getAmount();
            $total_fee = $transaction->getFixedFee() + $transaction->getVariableFee();
            $total_amount = $amount + $total_fee;

            $payment_info = $transaction->getPayOutInfo();

            //    RETRY
            if( isset( $data['retry'] ) && $data['retry'] == true ){

                $logger->info('Update transaction -> retry');
                if( $transaction->getStatus()== Transaction::$STATUS_FAILED ){
                    $logger->info('Update transaction -> status->failed');
                    //discount available
                    $current_wallet->setAvailable($current_wallet->getAvailable() - $total_amount);
                    $em->persist($current_wallet);
                    $em->flush();
                    try {
                        $payment_info = $method->send($payment_info);
                    }catch (Exception $e){

                        if($e->getStatusCode() >= 500){
                            $transaction->setStatus(Transaction::$STATUS_FAILED);
                        }else{
                            $transaction->setStatus( Transaction::$STATUS_ERROR );

                        }
                        $mongo->persist($transaction);
                        $mongo->flush();
                        //devolver la pasta de la transaccion al wallet si es cash out (al available)
                        $current_wallet->setAvailable($current_wallet->getAvailable() + $total_amount );

                        $transaction = $this->get('notificator')->notificate($transaction);

                        $em->persist($current_wallet);
                        $em->flush();

                        throw $e;

                    }

                    $transaction->setPayOutInfo($payment_info);

                    $transaction->setUpdated(new \DateTime());
                    $transaction->setStatus(Transaction::$STATUS_CREATED);
                    $transaction = $this->get('notificator')->notificate($transaction);
                    $mongo->persist($transaction);
                    $mongo->flush();

                    $logger->info('Update transaction -> addBalance');
                    //restamos la pasta al wallet
                    $balancer = $this->get('net.telepay.commons.balance_manipulator');
                    $balancer->addBalance($group, -$amount, $transaction);

                    $current_wallet->setBalance($current_wallet->getBalance() - $total_amount );
                    $em->persist($current_wallet);
                    $em->flush();

                    if($total_fee != 0){
                        $logger->info('Update transaction -> dealer');
                        $dealer = $this->container->get('net.telepay.commons.fee_deal');
                        try{
                            $dealer->createFees($transaction, $current_wallet);
                        }catch (HttpException $e){
                            throw $e;
                        }

                    }

                }elseif( $transaction->getStatus()== Transaction::$STATUS_CANCELLED ){
                    $logger->info('Update transaction -> status->cancelled');

                    $current_wallet->setAvailable($current_wallet->getAvailable() - $amount );
                    $em->persist($current_wallet);
                    $em->flush();
                    //send transaction
                    try {
                        $payment_info = $method->send($payment_info);
                    }catch (Exception $e){

                        if($e->getStatusCode() >= 500){
                            $transaction->setStatus(Transaction::$STATUS_FAILED);
                            $transaction = $this->get('notificator')->notificate($transaction);
                            $current_wallet->setAvailable($current_wallet->getAvailable() + $amount );
                            $em->persist($current_wallet);
                            $em->flush();
                        }else{
                            $transaction->setStatus( Transaction::$STATUS_ERROR );
                            $mongo->persist($transaction);
                            $mongo->flush();
                            //devolver la pasta de la transaccion al wallet si es cash out (al available)
                            $current_wallet->setAvailable($current_wallet->getAvailable() + $amount );
                            $em->persist($current_wallet);
                            $em->flush();

                            $transaction = $this->get('notificator')->notificate($transaction);
                            $mongo->persist($transaction);
                            $mongo->flush();

                            $em->persist($current_wallet);
                            $em->flush();

                            throw $e;
                        }

                    }

                    $logger->info('Update transaction -> addBalance');

                    $transaction->setUpdated(new \DateTime());
                    $transaction->setPayOutInfo($payment_info);
                    $transaction->setStatus(Transaction::$STATUS_CREATED);
                    $current_wallet->setBalance($current_wallet->getBalance() - $amount );

                    $transaction = $this->get('notificator')->notificate($transaction);
                    $em->persist($current_wallet);
                    $em->flush();
                    $mongo->persist($transaction);
                    $mongo->flush();

                    if($total_fee != 0){
                        $dealer = $this->container->get('net.telepay.commons.fee_deal');
                        $logger->info('Update transaction -> dealer');
                        try{
                            $dealer->createFees($transaction, $current_wallet);
                        }catch (HttpException $e){
                            throw $e;
                        }

                    }


                }else{
                    throw new HttpException(409,"This transaction can't be retried. First has to be cancelled");
                }

            }

            if( isset( $data['cancel'] ) && $data['cancel'] == true ){
                $logger->info('Update transaction -> cancel');
                //el cash-out solo se puede cancelar si esta en created review o success
                //el cash-in de momento no se puede cancelar
                if($transaction->getStatus()== Transaction::$STATUS_CREATED || $transaction->getStatus() == Transaction::$STATUS_REVIEW || ( ($method_cname == "halcash_es" || $method_cname == "halcash_pl") && $transaction->getStatus() == Transaction::$STATUS_SUCCESS && $transaction->getPayOutInfo()['status'] == Transaction::$STATUS_SENT )){
                    if($transaction->getStatus() == Transaction::$STATUS_REVIEW){
                        throw new HttpException(405, 'Mothod not implemented');
                    }else{
                        $logger->info('Update transaction -> canceling');
                        try {
                            $payment_info = $method->cancel($payment_info);
                        }catch (Exception $e){
                            throw $e;
                        }

                        $transaction->setStatus(Transaction::$STATUS_CANCELLED );
                        $transaction->setPayOutInfo($payment_info);
                        $transaction->setUpdated(new \DateTime());
                        $mongo->persist($transaction);
                        $mongo->flush();

                        //desbloquear pasta del wallet
                        $current_wallet->setAvailable($current_wallet->getAvailable() + $amount );
                        $current_wallet->setBalance($current_wallet->getBalance() + $amount );
                        $balancer = $this->get('net.telepay.commons.balance_manipulator');
                        $balancer->addBalance($group, $total_amount, $transaction);
                        $logger->info('Update transaction -> addBalance');

                        $em->persist($current_wallet);
                        $em->flush();

                        $transaction = $this->get('notificator')->notificate($transaction);

                        //return fees
                        if($total_fee != 0){
                            $logger->info('Update transaction -> inverseDealer2');
                            try{
                                $this->_inverseDealerV2($transaction, $current_wallet);
                            }catch (HttpException $e){
                                throw $e;
                            }

                        }

                    }

                }else{
                    throw new HttpException(403, "This transaction can't be cancelled.");
                }

            }
        }elseif( isset( $data['recheck'] ) && $data['recheck'] == true ){
            $logger->info('Update transaction -> recheck');
            $transaction->setStatus(Transaction::$STATUS_CREATED);

            $payment_info = $transaction->getPayInInfo();
            $payment_info['status'] = 'created';
            $payment_info['final'] = false;

            $transaction->setPayInInfo($payment_info);
            $transaction->setUpdated(new \DateTime());
        }else{
//            $transaction = $service->update($transaction,$data);
        }

        $mongo->persist($transaction);
        $mongo->flush();

        return $this->methodTransaction(200, $transaction, "Got ok");
    }

    /**
     * @Rest\View
     */
    public function check(Request $request, $version_number, $type, $method_cname, $id){
        $method = $this->get('net.telepay.'.$type.'.'.$method_cname.'.v'.$version_number);

        $user = $this->get('security.context')->getToken()->getUser();
        $group = $this->_getCurrentCompany($user);
        $this->_checkPermissions($user, $group);

        $method_list = $group->getMethodsList();

        if (!in_array($method_cname.'-'.$type, $method_list)) {
            throw $this->createAccessDeniedException();
        }

        $mongo = $this->get('doctrine_mongodb')->getManager();

        $transaction = $mongo->getRepository('TelepayFinancialApiBundle:Transaction')->findOneBy(array(
            'id'        => $id,
            'method'   =>  $method_cname,
            'group'      =>  $group->getId(),
            'type'      =>  $type
        ));

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        if($transaction->getStatus() == Transaction::$STATUS_CREATED ||
            $transaction->getStatus() == Transaction::$STATUS_RECEIVED ||
            $transaction->getStatus() == Transaction::$STATUS_FAILED ||
            $transaction->getStatus() == Transaction::$STATUS_REVIEW ){

            $previuos_status = $transaction->getStatus();
            if($transaction->getType() == 'in'){
                $payment_info = $transaction->getPayInInfo();
                $payment_info = $method->getPayInStatus($payment_info);
                $transaction->setPayInInfo($payment_info);
            }else{
                $payment_info = $transaction->getPayOutInfo();
                $payment_info = $method->getPayOutStatus($payment_info);
                $transaction->setPayOutInfo($payment_info);
            }

            if($payment_info['status'] != $previuos_status){
                $transaction->setStatus($payment_info['status']);
            }
            $mongo->persist($transaction);
            $mongo->flush();

            //if previous status != current status update wallets
            if( $previuos_status != $transaction->getStatus()){
                $transaction->setUpdated(new \DateTime());
                $mongo->persist($transaction);
                $mongo->flush();

                $transaction = $this->get('notificator')->notificate($transaction);

                $em = $this->getDoctrine()->getManager();

                $wallets = $group->getWallets();
                $current_wallet = null;
                foreach( $wallets as $wallet){
                    if($wallet->getCurrency() == $transaction->getCurrency()){
                        $current_wallet = $wallet;
                    }
                }

                if(!$current_wallet) throw new HttpException(404,'Wallet not found');

                if($transaction->getStatus() == Transaction::$STATUS_CANCELLED ||
                    $transaction->getStatus() == Transaction::$STATUS_EXPIRED ||
                    $transaction->getStatus() == Transaction::$STATUS_ERROR){
                    //unblock available wallet if cash-out
                    if($type == 'out'){
                        $current_wallet->setAvailable($current_wallet->getAvailable() + $transaction->getAmount());
                        $em->persist($current_wallet);
                        $em->flush();

                    }

                }elseif($transaction->getStatus() == Transaction::$STATUS_SUCCESS ){
                    //Update balance
                    if($type == 'out'){
                        $current_wallet->setBalance($current_wallet->getBalance() - $transaction->getAmount());
                        $em->persist($current_wallet);
                        $em->flush();
                    }else{
                        $current_wallet->setAvailable($current_wallet->getAvailable() + $transaction->getAmount());
                        $current_wallet->setBalance($current_wallet->getBalance() + $transaction->getAmount());
                        $balancer = $this->get('net.telepay.commons.balance_manipulator');
                        $balancer->addBalance($group, $transaction->getAmount(), $transaction);
                        $em->persist($current_wallet);
                        $em->flush();
                    }
                }
            }

        }

        return $this->methodTransaction(200,$transaction, "Got ok");

    }

    /**
     * @Rest\View
     */
    public function find(Request $request, $version_number, $type, $method_cname){

        $method = $this->get('net.telepay.'.$type.'.'.$method_cname.'.v'.$version_number);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $user = $this->get('security.context')
            ->getToken()->getUser();
        //TODO change this for active group
        $group = $user->getGroups()[0];

        //TODO quitar cuando haya algo mejor montado
        if($user->getId() == $this->container->getParameter('read_only_user_id')){
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($this->container->getParameter('chipchap_user_id'));
        }

        $method_list = $user->getMethodsList();

        if (!in_array($method_cname.'-'.$type, $method_list)) {
            throw $this->createAccessDeniedException();
        }

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');

        if($request->query->get('query') != ''){
            $query = $request->query->get('query');
            $search = $query['search'];
            $order = $query['order'];
            $dir = $query['dir'];
            $start_time = new \MongoDate(strtotime(date($query['start_date'].' 00:00:00')));//date('Y-m-d 00:00:00')
            $finish_time = new \MongoDate(strtotime(date($query['finish_date'].' 23:59:59')));

            $transactions = $qb
                ->field('group')->equals($group->getId())
                //->field('method')->equals($method->getCname())
                //->field('type')->equals($type)
                ->field('created')->gte($start_time)
                ->field('created')->lte($finish_time)
                ->where("function() {
            if (typeof this.payInInfo !== 'undefined') {
                if (typeof this.payInInfo.amount !== 'undefined') {
                    if(String(this.payInInfo.amount).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payInInfo.address !== 'undefined') {
                    if(String(this.payInInfo.address).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payInInfo.status !== 'undefined') {
                    if(String(this.payInInfo.status).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payInInfo.concept !== 'undefined') {
                    if(String(this.payInInfo.concept).indexOf('$search') > -1){
                        return true;
                    }
                }

            }
            if (typeof this.payOutInfo !== 'undefined') {
                if (typeof this.payOutInfo.amount !== 'undefined') {
                    if(String(this.payOutInfo.amount).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payOutInfo.halcashticket !== 'undefined') {
                    if(String(this.payOutInfo.halcashticket).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payOutInfo.txid !== 'undefined') {
                    if(String(this.payOutInfo.txid).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payOutInfo.address !== 'undefined') {
                    if(String(this.payOutInfo.address).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payOutInfo.concept !== 'undefined') {
                    if(String(this.payOutInfo.concept).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payOutInfo.email !== 'undefined') {
                    if(String(this.payOutInfo.email).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payOutInfo.find_token !== 'undefined') {
                    if(String(this.payOutInfo.find_token).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payOutInfo.phone !== 'undefined') {
                    if(String(this.payOutInfo.phone).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.payOutInfo.pin !== 'undefined') {
                    if(String(this.payOutInfo.pin).indexOf('$search') > -1){
                        return true;
                    }
                }

            }
            if ('$search') {
                if(typeof this.status !== 'undefined' && String(this.status).indexOf('$search') > -1){ return true;}
                if(typeof this.service !== 'undefined' && String(this.service).indexOf('$search') > -1){ return true;}
                if(typeof this.method !== 'undefined' && String(this.method).indexOf('$search') > -1){ return true;}
                if(typeof this.methodIn !== 'undefined' && String(this.methodIn).indexOf('$search') > -1){ return true;}
                if(typeof this.methodOut !== 'undefined' && String(this.methodOut).indexOf('$search') > -1){ return true;}
                if(String(this._id).indexOf('$search') > -1){ return true;}
                return false;
            }
            return true;
            }")
                ->sort($order,$dir)
                ->getQuery()
                ->execute();

        }else{
            $order = "id";
            $dir = "desc";

            $transactions = $qb
                ->field('group')->equals($group->getId())
                //->field('service')->equals($method->getCname())
                //->field('type')->equals($method->getType())
                ->sort($order,$dir)
                ->getQuery()
                ->execute();
        }
        $resArray = [];
        foreach($transactions->toArray() as $res){
            $resArray []= $res;

        }

        $total = count($resArray);

        $page_amount = 0;
        $total_amount = 0;

        foreach ($resArray as $array){
            if($array->getStatus() == 'success'){
                $total_amount = $total_amount + $array->getAmount();
            }
        }
        $wallets = $group->getWallets();
        $service_currency = $method->getCurrency();

        foreach ( $wallets as $wallet){
            if ($wallet->getCurrency() == $service_currency){
                $current_wallet = $wallet;
            }
        }

        $scale = $current_wallet->getScale();

        $entities = array_slice($resArray, $offset, $limit);

        foreach ($entities as $ent){
            if($ent->getStatus() == 'success'){
                $page_amount = $page_amount + $ent->getAmount();
            }
        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'start' => intval($offset),
                'end' => count($entities)+$offset,
                'elements' => $entities,
                'page_amount' => $page_amount,
                'total_amount' => $total_amount,
                'scale' =>  $scale
            )
        );
    }

    /**
     * @Rest\View
     */
    public function notificate(Request $request, $version_number, $type, $method_cname, $id) {

        throw new HttpException(503, 'Method not Implemented');
        $method = $this->get('net.telepay.'.$method_cname.'.'.$type.'.v'.$version_number);

        $mongo = $this->get('doctrine_mongodb')->getManager();
        $transaction =$mongo->getRepository('TelepayFinancialApiBundle:Transaction')->findOneBy(array(
            'id'        => $id,
            'service'   =>  $method_cname,
            'type'      =>  $type
        ));

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        if( $transaction->getStatus() != Transaction::$STATUS_CREATED ) throw new HttpException(409,'Tranasction already processed.');

        $transaction = $method->notificate($transaction, $request->request->all());

        $mongo = $this->get('doctrine_mongodb')->getManager();
        $transaction->setUpdated(new \DateTime());
        $mongo->persist($transaction);
        $mongo->flush();

        if(!$transaction) throw new HttpException(500, "oOps, the notification failed");

        if($transaction->getStatus() == Transaction::$STATUS_SUCCESS ){
            //update wallet
            $user_id = $transaction->getUser();

            $em = $this->getDoctrine()->getManager();

            $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($user_id);
            $currency = $transaction->getCurrency();

            $wallets = $user->getWallets();

            $current_wallet = null;
            foreach($wallets as $wallet ){
                if($wallet->getCurrency() == $currency){
                    $current_wallet = $wallet;
                }
            }

            if($current_wallet == null) throw new HttpException(404,'Wallet not found');

            $transaction_amount = $transaction->getTotal();
            $total_fee = $transaction->getFixedFee() + $transaction->getVariableFee();
            $total_amount = $transaction_amount - $total_fee ;

            if( $service->getcashDirection() == 'out' ){
                $current_wallet->setBalance($current_wallet->getBalance() + $total_amount );
            }else{
                $current_wallet->setAvailable($current_wallet->getAvailable() + $total_amount );
                $current_wallet->setBalance($current_wallet->getBalance() + $total_amount);
                $balancer = $this->get('net.telepay.commons.balance_manipulator');
                $balancer->addBalance($user, $transaction->getAmount(), $transaction);
            }

            $em->persist($current_wallet);
            $em->flush();

            if($total_fee != 0){
                //cobramos comisiones al user y hacemos el reparto

                try{
                    $this->_dealer($transaction,$current_wallet);
                }catch (HttpException $e){
                    throw $e;
                }

            }

        }

        //notificar al cliente
        $transaction = $this->get('notificator')->notificate($transaction);

        return $this->restV2(200, "ok", "Notification successful");

    }

//    private function _dealer(Transaction $transaction, UserWallet $current_wallet){
//
//        $logger = $this->get('transaction.logger');
//        $logger->info('make transaction -> dealer');
//        $amount = $transaction->getAmount();
//        $currency = $transaction->getCurrency();
//        $method_cname = $transaction->getMethod() . "-" . $transaction->getType();
//
//        $em = $this->getDoctrine()->getManager();
//
//        $total_fee = $transaction->getFixedFee() + $transaction->getVariableFee();
//        $group = $em->getRepository('TelepayFinancialApiBundle:Group')->find($transaction->getGroup());
//        $creator = $group->getGroupCreator();
//        $logger->info('Group: ' . $group->getId() . " created by " . $creator->getId() . " : -" . $total_fee);
//
//        //Fee negativa para el usuario que ha hecho la transaction
//        $feeTransaction = Transaction::createFromTransaction($transaction);
//        $feeTransaction->setMethod($method_cname);
//        $feeTransaction->setAmount($total_fee);
//        $feeTransaction->setDataIn(array(
//            'previous_transaction'  =>  $transaction->getId(),
//            'amount'                =>  -$total_fee,
//            'concept'           =>  $method_cname.'->fee',
//            'admin'                 =>  $creator->getName()
//        ));
//        $feeTransaction->setData(array(
//            'previous_transaction'  =>  $transaction->getId(),
//            'amount'                =>  -$total_fee,
//            'type'                  =>  'resta_fee'
//        ));
//        $feeTransaction->setDebugData(array(
//            'previous_balance'  =>  $current_wallet->getBalance(),
//            'previous_transaction'  =>  $transaction->getId()
//        ));
//
//        $feeTransaction->setPayOutInfo(array(
//            'previous_transaction'  =>  $transaction->getId(),
//            'amount'                =>  -$total_fee,
//            'concept'           =>  $method_cname.'->fee',
//            'admin'                 =>  $creator->getName()
//        ));
//        $feeTransaction->setFeeInfo(array(
//            'previous_transaction'  =>  $transaction->getId(),
//            'previous_amount'   =>  $transaction->getAmount(),
//            'amount'                =>  -$total_fee,
//            'currency'      =>  $currency,
//            'scale'     =>  $transaction->getScale(),
//            'concept'           =>  $method_cname.'->fee',
//            'status'    =>  Transaction::$STATUS_SUCCESS
//        ));
//
//        $feeTransaction->setType(Transaction::$TYPE_FEE);
//
//        $feeTransaction->setTotal(-$total_fee);
//
//        $mongo = $this->get('doctrine_mongodb')->getManager();
//        $mongo->persist($feeTransaction);
//        $mongo->flush();
//
//        $logger->info('make transaction -> feeTransaction id => '.$feeTransaction->getId());
//
//        $logger->info('make transaction -> addBalance');
//        $balancer = $this->get('net.telepay.commons.balance_manipulator');
//
//        $balancer->addBalance($group, -$total_fee, $feeTransaction );
//        //empezamos el reparto
//
//        if(!$creator) throw new HttpException(404,'Creator not found');
//
//        $transaction_id = $transaction->getId();
//        $dealer = $this->get('net.telepay.commons.fee_deal');
//        $dealer->deal(
//            $creator,
//            $amount,
//            $transaction->getMethod(),
//            $transaction->getType(),
//            $currency,
//            $total_fee,
//            $transaction_id,
//            $transaction->getVersion()
//        );
//
//    }
//
//    private function _inverseDealer(Transaction $transaction, UserWallet $current_wallet){
//
//        $amount = $transaction->getAmount();
//        $currency = $transaction->getCurrency();
//        $method_cname = $transaction->getMethod() . "-" . $transaction->getType();
//
//        $em = $this->getDoctrine()->getManager();
//
//        $total_fee = $transaction->getFixedFee() + $transaction->getVariableFee();
//        $group = $em->getRepository('TelepayFinancialApiBundle:User')->find($transaction->getGroup());
//
//        $feeTransaction = Transaction::createFromTransaction($transaction);
//        $feeTransaction->setAmount($total_fee);
//        $feeTransaction->setDataIn(array(
//            'previous_transaction'  =>  $transaction->getId(),
//            'amount'                =>  $total_fee,
//            'description'           =>  'refund'.$method_cname.'->fee'
//        ));
//        $feeTransaction->setData(array(
//            'previous_transaction'  =>  $transaction->getId(),
//            'amount'                =>  $total_fee,
//            'type'                  =>  'refund_fee'
//        ));
//        $feeTransaction->setDebugData(array(
//            'previous_balance'  =>  $current_wallet->getBalance(),
//            'previous_transaction'  =>  $transaction->getId()
//        ));
//
//        $feeTransaction->setPayInInfo(array(
//            'previous_transaction'  =>  $transaction->getId(),
//            'amount'                =>  $total_fee,
//            'description'           =>  'refund'.$method_cname.'->fee'
//        ));
//
//        $feeTransaction->setType('fee');
//
//        $feeTransaction->setTotal($total_fee);
//
//        $mongo = $this->get('doctrine_mongodb')->getManager();
//        $mongo->persist($feeTransaction);
//        $mongo->flush();
//
//        $balancer = $this->get('net.telepay.commons.balance_manipulator');
//        $balancer->addBalance($group, $total_fee, $feeTransaction );
//
//        //empezamos el reparto
//        $creator = $group->getCreator();
//
//        if(!$creator) throw new HttpException(404,'Creator not found');
//
//        $transaction_id = $transaction->getId();
//        $dealer = $this->get('net.telepay.commons.fee_deal');
//        $dealer->inversedDeal(
//            $creator,
//            $amount,
//            $method_cname,
//            $transaction->getType(),
//            $currency,
//            $total_fee,
//            $transaction_id,
//            $transaction->getVersion()
//        );
//
//    }

    private function _inverseDealerV2(Transaction $transaction_cancelled, UserWallet $current_wallet){
        $logger = $this->get('logger');
        $logger->info('Update transaction -> inversedDealer2');
        $em = $this->getDoctrine()->getManager();
        $mongo = $this->get('doctrine_mongodb')->getManager();

        $qb = $mongo->createQueryBuilder('TelepayFinancialApiBundle:Transaction');
        $transaction_id = $transaction_cancelled->getId();
        $transactions = $qb
            ->field('type')->equals('fee')
            ->field('group')->equals($transaction_cancelled->getGroup())
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

//        $exist = false;
//        $method_cname = $transaction_cancelled->getMethod();
        foreach($transactions->toArray() as $transaction){
//            $exist = true;

            $total_fee = $transaction->getAmount();

            $group = $em->getRepository('TelepayFinancialApiBundle:Group')->find($transaction->getGroup());

            $logger->info('Update transaction -> cancel fees => '.$transaction->getId());
            $logger->info('Update transaction -> cancel fees => '.$transaction->getAmount());
            $transaction->setAmount(0);
            $transaction->setTotal(0);
            $feeInfo = $transaction->getFeeInfo();
            $feeInfo['status'] = Transaction::$STATUS_CANCELLED;
            $transaction->setFeeInfo($feeInfo);
//            $transaction->setPayOutInfo(array(
//                'previous_transaction'  =>  $transaction->getId(),
//                'amount'                =>  -$total_fee,
//                'description'           =>  'refund'.$method_cname.'->fee'
//            ));
            $transaction->setStatus(Transaction::$STATUS_CANCELLED);

            $mongo->persist($transaction);
            $mongo->flush();

            $logger->info('Update transaction -> addBalance inversedDealer');
            $balancer = $this->get('net.telepay.commons.balance_manipulator');
            $balancer->addBalance($group, $total_fee, $transaction );

            $wallets = $group->getwallets();
            foreach($wallets as $wallet){
                if($transaction->getCurrency() == $wallet->getCurrency()){
                    $wallet->setAvailable($wallet->getAvailable() + $total_fee);
                    $wallet->setBalance($wallet->getBalance() + $total_fee);

                    $em->persist($wallet);
                    $em->flush();
                }
            }

        }

    }

    private function _getCurrentWallet(){

    }

    private function _getFees(Group $group, $method){
        $em = $this->getDoctrine()->getManager();

        $group_commissions = $group->getCommissions();
        $group_commission = false;

        foreach ( $group_commissions as $commission ){
            if ( $commission->getServiceName() == $method->getCname().'-'.$method->getType() ){
                $group_commission = $commission;
            }
        }

        //if group commission not exists we create it
        if(!$group_commission){
            $group_commission = ServiceFee::createFromController($method->getCname().'-'.$method->getType(), $group);
            $group_commission->setCurrency($method->getCurrency());
            $em->persist($group_commission);
            $em->flush();
        }

        return $group_commission;
    }

    public function _getLimitCount(Group $group, $method){
        $em = $this->getDoctrine()->getManager();

        $limits = $group->getLimitCounts();
        $group_limit = false;
        foreach ( $limits as $limit ){
            if($limit->getCname() == $method->getCname().'-'.$method->getType()){
                $group_limit = $limit;
            }
        }

        //if user hasn't limit create it
        if(!$group_limit){
            $group_limit = LimitCount::createFromController($method->getCname().'-'.$method->getType(), $group);
            $em->persist($group_limit);
            $em->flush();
        }

        return $group_limit;
    }

    public function _getLimits(Group $group, $method){
        $em = $this->getDoctrine()->getManager();

        $group_limits = $group->getLimits();
        $group_limit = false;
        foreach ( $group_limits as $limit ){
            if( $limit->getCname() == $method->getCname().'-'.$method->getType()){
                $group_limit = $limit;
            }
        }

        //if limit doesn't exist search in tierLimit
        if(!$group_limit){
            $tier = $group->getTier();
            $group_limit = $em->getRepository('TelepayFinancialApiBundle:TierLimit')->findOneBy(array(
                'tier'  =>  $tier,
                'method'    =>  $method->getCname().'-'.$method->getType()
            ));
        }

        return $group_limit;
    }

    private function _checkPermissions(User $user, Group $group){

        if(!$user->hasGroup($group->getName())) throw new HttpException(403, 'You(' . $user->getId() . ') do not have the necessary permissions in this company(' . $group->getId() . ')');

        //Check permissions for this user in this company
        $userRoles = $this->getDoctrine()->getRepository('TelepayFinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $user->getId(),
            'group' =>  $group->getId()
        ));

        if(!$userRoles->hasRole('ROLE_WORKER') && !$userRoles->hasRole('ROLE_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions in this company. Only ROLE_WORKER allowed');


    }

    private function _getCurrentCompany(User $user){
        $tokenManager = $this->container->get('fos_oauth_server.access_token_manager.default');

        try{
            $accessToken = $tokenManager->findTokenByToken(
                $this->get('security.context')->getToken()->getToken()
            );

            $commerce_client = $this->container->getParameter('commerce_client_id');
            $android_pos_client = $this->container->getParameter('android_pos_client_id');

            $client = $accessToken->getClient();
            if($commerce_client == $client->getId() || $android_pos_client == $client->getId()){
                $group = $user->getActiveGroup();
            }else{
                $group = $client->getGroup();
            }
        }catch (Exception $e){
            $group = $user->getActiveGroup();
        }

        return $group;
    }



}


