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
    public function make(Request $request, $version_number, $type, $method_cname, $id = null){

        $method = $this->get('net.telepay.'.$type.'.'.$method_cname.'.v'.$version_number);

        $method_list = $this->get('security.context')->getToken()->getUser()->getMethodsList();

        if (!in_array($method_cname.'-'.$type, $method_list)) {
            throw $this->createAccessDeniedException();
        }

        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();

        $logger = $this->get('logger');
        $logger->info('Incomig transaction...Method-> '.$method_cname.' Direction -> '.$type);

        $user = $this->container->get('security.context')->getToken()->getUser();
        $transaction = Transaction::createFromRequest($request);
        $transaction->setService($method_cname);
        $transaction->setMethod($method_cname);
        $transaction->setUser($user->getId());
        $transaction->setVersion($version_number);
        $transaction->setType($type);
        $dm->persist($transaction);

        if($request->request->has('concept')){
            $concept = $request->request->get('concept');
        }else{
            $concept = '';
            $request->request->add(array(
                'concept'   =>  $concept
            ));
        }

        if($request->request->has('url_notification')) $url_notification = $request->request->get('url_notification');
        else $url_notification = '';

        if($request->request->has('amount')) $amount = $request->request->get('amount');
        else throw new HttpException(400, 'Param amount not found');

        $logger->info('Incomig transaction...getPaymentInfo');

        //Aqui hay que distinguir entre in i out
        //para in es getPayInInfo y para out es getPayOutInfo
        if($type == 'in'){

            $dataIn = array(
                'amount'    =>  $amount,
                'concept'   =>  $concept,
                'url_notification'  =>  $url_notification
            );
            $payment_info = $method->getPayInInfo($amount);
            $transaction->setPayInInfo($payment_info);

        }else{

            $payment_info = $method->getPayOutInfo($request);
            $transaction->setPayOutInfo($payment_info);
            $dataIn = array(
                'amount'    =>  $amount,
                'concept'   =>  $concept,
                'url_notification'  =>  $url_notification
            );
        }

        $transaction->setDataIn($dataIn);

        //obtain user and check limits
        $user = $this->getUser();

        //obtener group
        $group = $user->getGroups()[0];

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

        //obtain user limits
        $user_limit = $this->_getLimitCount($user, $method);

        //obtain group limit
        $group_limit = $this->_getLimits($group, $method);

        $new_user_limit = (new LimitAdder())->add( $user_limit, $total);

        $checker = new LimitChecker();

        if(!$checker->leq($new_user_limit, $group_limit)) throw new HttpException(509,'Limit exceeded');

        //obtain wallet and check founds for cash_out services
        $wallets = $user->getWallets();

        $current_wallet = null;

        $transaction->setCurrency($method->getCurrency());

        //******    CHECK IF THE TRANSACTION IS CASH-OUT     ********
        if($type == 'out'){
            $logger->info('Incomig transaction...OUT');
            foreach ( $wallets as $wallet){
                if ($wallet->getCurrency() == $method->getCurrency()){
                    if($wallet->getAvailable() <= $total) throw new HttpException(509,'Not founds enough');
                    //Bloqueamos la pasta en el wallet
                    $actual_available = $wallet->getAvailable();
                    $new_available = $actual_available - $total;
                    $wallet->setAvailable($new_available);
                    $em->persist($wallet);
                    $em->flush();
                    $current_wallet = $wallet;
                }
            }

            $scale = $current_wallet->getScale();
            $transaction->setScale($scale);
            $dm->persist($transaction);
            $dm->flush();

            $logger->info('Incomig transaction...SEND');

            try {
                $payment_info = $method->send($payment_info);
            }catch (HttpException $e){
                $logger->error('Incomig transaction...ERROR '.$e->getMessage());

                if($e->getStatusCode()>=500){
                    $transaction->setStatus(Transaction::$STATUS_FAILED);
                }else{
                    $transaction->setStatus( Transaction::$STATUS_ERROR );
                }
                //desbloqueamos la pasta del wallet
                $current_wallet->setAvailable($current_wallet->getAvailable() + $total);
                $em->persist($current_wallet);
                $em->flush();
                $dm->persist($transaction);
                $dm->flush();

                $this->container->get('notificator')->notificate($transaction);

                throw new HttpException($e->getStatusCode(), $e->getMessage());

            }
            $logger->info('Incomig transaction...PAYMENT STATUS: '.$payment_info['status']);

            $transaction->setPayOutInfo($payment_info);
            $dm->persist($transaction);
            $dm->flush();

            //pay fees and dealer always and set new balance
            if( $payment_info['status'] == 'sent' ){

                if($payment_info['final'] == true) $transaction->setStatus(Transaction::$STATUS_SUCCESS);
                else $transaction->setStatus(Transaction::$STATUS_CREATED);

                $dm->persist($transaction);
                $dm->flush();

                $this->container->get('notificator')->notificate($transaction);

                //restar al usuario el amount + comisiones
                $current_wallet->setBalance($current_wallet->getBalance() - $total);

                //insert new line in the balance
                $balancer = $this->get('net.telepay.commons.balance_manipulator');
                $balancer->addBalance($user, -$amount, $transaction);

                $em->persist($current_wallet);
                $em->flush();

                if( $total_fee != 0){
                    //nueva transaccion restando la comision al user
                    try{
                        $this->_dealer($transaction, $current_wallet);
                    }catch (HttpException $e){
                        throw $e;
                    }
                }
            }else{

                $transaction->setStatus($payment_info['status']);
                //desbloqueamos la pasta del wallet
                $current_wallet->setAvailable($current_wallet->getAvailable() + $total);
                $em->persist($current_wallet);
                $em->flush();
                $dm->persist($transaction);
                $dm->flush();

                $this->container->get('notificator')->notificate($transaction);

            }

        }else{     //CASH - IN
            $logger->info('Incomig transaction...IN');
            foreach ( $wallets as $wallet){
                if ($wallet->getCurrency() === $transaction->getCurrency()){
                    $current_wallet = $wallet;
                }
            }

            $scale = $current_wallet->getScale();
            $transaction->setScale($scale);
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

        return $this->methodTransaction(201, $transaction, "Done");
    }

    /**
     * @Rest\View
     */
    public function update(Request $request, $version_number, $type, $method_cname, $id){

        $method = $this->get('net.telepay.'.$type.'.'.$method_cname.'.v'.$version_number);

        $method_list = $this->get('security.context')->getToken()->getUser()->getMethodsList();

        if (!in_array($method_cname.'-'.$type, $method_list)) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->get('security.context')->getToken()->getUser();

        $data = $request->request->all();

        $mongo = $this->get('doctrine_mongodb')->getManager();
        $transaction = $mongo->getRepository('TelepayFinancialApiBundle:Transaction')->findOneBy(array(
            'id'        =>  $id,
            'method'    =>  $method_cname,
            'user'      =>  $user->getId(),
            'type'      =>  $type
        ));

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        //retry=true y cancel=true aqui
        if( isset( $data['retry'] ) || isset ( $data ['cancel'] )){

            if($transaction->getType() != 'out') throw new HttpException(403, 'Forbidden action for this transaction ');

            //Search user
            $user_id = $transaction->getUser();

            $em = $this->getDoctrine()->getManager();

            $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($user_id);
            $currency = $transaction->getCurrency();

            //Search wallet
            $wallets = $user->getWallets();

            $current_wallet = null;
            foreach($wallets as $wallet ){
                if($wallet->getCurrency() == $currency){
                    $current_wallet = $wallet;

                }
            }

            if($current_wallet == null) throw new HttpException(404,'Wallet not found');

            $transaction_amount = $transaction->getTotal();
            $amount = $transaction->getAmount();
            $total_fee = $transaction->getFixedFee() + $transaction->getVariableFee();
            $total_amount = $amount + $total_fee ;

            $payment_info = $transaction->getPayOutInfo();

            //    RETRY
            if( isset( $data['retry'] ) && $data['retry'] == true ){

                if( $transaction->getStatus()== Transaction::$STATUS_FAILED ){
                    //discount available
                    $current_wallet->setAvailable($current_wallet->getAvailable() - $total_amount );
                    $em->persist($current_wallet);
                    $em->flush();
                    try {
                        $payment_info = $method->send($payment_info);
                    }catch (HttpException $e){

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

                    //restamos la pasta al wallet
                    $balancer = $this->get('net.telepay.commons.balance_manipulator');
                    $balancer->addBalance($user, -$amount, $transaction);

                    $current_wallet->setBalance($current_wallet->getBalance() - $total_amount );
                    $em->persist($current_wallet);
                    $em->flush();

                    if($total_fee != 0){

                        try{
                            $this->_dealer($transaction,$current_wallet);
                        }catch (HttpException $e){
                            throw $e;
                        }

                    }

                }elseif( $transaction->getStatus()== Transaction::$STATUS_CANCELLED ){

                    $current_wallet->setAvailable($current_wallet->getAvailable() - $amount );
                    $em->persist($current_wallet);
                    $em->flush();
                    //send transaction
                    try {
                        $payment_info = $method->send($payment_info);
                    }catch (HttpException $e){

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

                        try{
                            $this->_dealer($transaction,$current_wallet);
                        }catch (HttpException $e){
                            throw $e;
                        }

                    }


                }else{
                    throw new HttpException(409,"This transaction can't be retried. First has to be cancelled");
                }

            }

            if( isset( $data['cancel'] ) && $data['cancel'] == true ){

                //el cash-out solo se puede cancelar si esta en created review o success
                //el cash-in de momento no se puede cancelar
                if($transaction->getStatus()== Transaction::$STATUS_CREATED
                    || $transaction->getStatus() == Transaction::$STATUS_REVIEW){

                    if($transaction->getStatus() == Transaction::$STATUS_REVIEW){
                        throw new HttpException(403, 'Mothod not implemented');
                    }else{
                        try {
                            $payment_info = $method->cancel($payment_info);
                        }catch (HttpException $e){
                            throw $e;
                        }

                        $transaction->setStatus(Transaction::$STATUS_CANCELLED );
                        $transaction->setPayOutInfo($payment_info);
                        $transaction->setUpdated(new \DateTime());
                        $mongo->persist($transaction);
                        $mongo->flush();

                        //desbloquear pasta del wallet
                        $current_wallet->setAvailable($current_wallet->getAvailable() + $total_amount );
                        $current_wallet->setBalance($current_wallet->getBalance() + $total_amount );
                        $balancer = $this->get('net.telepay.commons.balance_manipulator');
                        $balancer->addBalance($user, $total_amount, $transaction);

                        $em->persist($current_wallet);
                        $em->flush();

                        $transaction = $this->get('notificator')->notificate($transaction);

                        //return fees
                        if($total_fee != 0){

                            try{
                                $this->_inverseDealer($transaction, $current_wallet);
                            }catch (HttpException $e){
                                throw $e;
                            }

                        }

                    }

                }else{
                    throw new HttpException(403, "This transaction can't be cancelled.");
                }

            }
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

        //TODO quitar cuando haya algo mejor montado
        if($user->getId() == $this->container->getParameter('read_only_user_id')){
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($this->container->getParameter('chipchap_user_id'));
        }

        $method_list = $user->getMethodsList();

        if (!in_array($method_cname.'-'.$type, $method_list)) {
            throw $this->createAccessDeniedException();
        }

        $mongo = $this->get('doctrine_mongodb')->getManager();

        $transaction =$mongo->getRepository('TelepayFinancialApiBundle:Transaction')->findOneBy(array(
            'id'        => $id,
            'method'   =>  $method_cname,
            'user'      =>  $user->getId(),
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
                $user_id = $transaction->getUser();
                $em = $this->getDoctrine()->getManager();
                $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($user_id);
                $wallets = $user->getWallets();
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
                        $balancer->addBalance($user, $transaction->getAmount(), $transaction);
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

        $userId = $user->getId();

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');

        if($request->query->get('query') != ''){
            $query = $request->query->get('query');
            $search = $query['search'];
            $order = $query['order'];
            $dir = $query['dir'];
            $start_time = new \MongoDate(strtotime(date($query['start_date'].' 00:00:00')));//date('Y-m-d 00:00:00')
            $finish_time = new \MongoDate(strtotime(date($query['finish_date'].' 23:59:59')));

            $transactions = $qb
                ->field('user')->equals($userId)
                //->field('method')->equals($method->getCname())
                //->field('type')->equals($type)
                ->field('created')->gte($start_time)
                ->field('created')->lte($finish_time)
                ->where("function() {
            if (typeof this.dataIn !== 'undefined') {
                if (typeof this.dataIn.phone_number !== 'undefined') {
                    if(String(this.dataIn.phone_number).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataIn.address !== 'undefined') {
                    if(String(this.dataIn.address).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataIn.reference !== 'undefined') {
                    if(String(this.dataIn.reference).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataIn.pin !== 'undefined') {
                    if(String(this.dataIn.pin).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataIn.order_id !== 'undefined') {
                    if(String(this.dataIn.order_id).indexOf('$search') > -1){
                        return true;
                    }
                }
            }
            if (typeof this.dataOut !== 'undefined') {
                if (typeof this.dataOut.transaction_pos_id !== 'undefined') {
                    if(String(this.dataOut.transaction_pos_id).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataOut.halcashticket !== 'undefined') {
                    if(String(this.dataOut.halcashticket).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataOut.txid !== 'undefined') {
                    if(String(this.dataOut.txid).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataOut.address !== 'undefined') {
                    if(String(this.dataOut.address).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataOut.id !== 'undefined') {
                    if(String(this.dataOut.id).indexOf('$search') > -1){
                        return true;
                    }
                }
            }
            if ('$search') {
                if(typeof this.status !== 'undefined' && String(this.status).indexOf('$search') > -1){ return true;}
                if(typeof this.service !== 'undefined' && String(this.service).indexOf('$search') > -1){ return true;}
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
                ->field('user')->equals($userId)
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
        $wallets = $user->getWallets();
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

    private function _dealer(Transaction $transaction, UserWallet $current_wallet){

        $amount = $transaction->getAmount();
        $currency = $transaction->getCurrency();
        $method_cname = $transaction->getMethod();

        $em = $this->getDoctrine()->getManager();

        $total_fee = $transaction->getFixedFee() + $transaction->getVariableFee();

        $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($transaction->getUser());

        $group = $user->getGroups()[0];
        $creator = $group->getCreator();

        $feeTransaction = Transaction::createFromTransaction($transaction);
        $feeTransaction->setAmount($total_fee);
        $feeTransaction->setDataIn(array(
            'previous_transaction'  =>  $transaction->getId(),
            'amount'                =>  -$total_fee,
            'description'           =>  $method_cname.'->fee',
            'admin'                 =>  $creator->getUsername()
        ));
        $feeTransaction->setData(array(
            'previous_transaction'  =>  $transaction->getId(),
            'amount'                =>  -$total_fee,
            'type'                  =>  'resta_fee'
        ));
        $feeTransaction->setDebugData(array(
            'previous_balance'  =>  $current_wallet->getBalance(),
            'previous_transaction'  =>  $transaction->getId()
        ));

        $feeTransaction->setPayOutInfo(array(
            'previous_transaction'  =>  $transaction->getId(),
            'amount'                =>  -$total_fee,
            'description'           =>  $method_cname.'->fee',
            'admin'                 =>  $creator->getUsername()
        ));

        $feeTransaction->setType('fee');

        $feeTransaction->setTotal(-$total_fee);

        $mongo = $this->get('doctrine_mongodb')->getManager();
        $mongo->persist($feeTransaction);
        $mongo->flush();

        $balancer = $this->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($user, -$total_fee, $feeTransaction );

        //empezamos el reparto


        if(!$creator) throw new HttpException(404,'Creator not found');

        $transaction_id = $transaction->getId();
        $dealer = $this->get('net.telepay.commons.fee_deal');
        $dealer->deal(
            $creator,
            $amount,
            $method_cname,
            $transaction->getType(),
            $currency,
            $total_fee,
            $transaction_id,
            $transaction->getVersion()
        );

    }

    private function _inverseDealer(Transaction $transaction, UserWallet $current_wallet){

        $amount = $transaction->getAmount();
        $currency = $transaction->getCurrency();
        $method_cname = $transaction->getMethod();

        $em = $this->getDoctrine()->getManager();

        $total_fee = $transaction->getFixedFee() + $transaction->getVariableFee();

        $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($transaction->getUser());

        $feeTransaction = Transaction::createFromTransaction($transaction);
        $feeTransaction->setAmount($total_fee);
        $feeTransaction->setDataIn(array(
            'previous_transaction'  =>  $transaction->getId(),
            'amount'                =>  $total_fee,
            'description'           =>  'refund'.$method_cname.'->fee'
        ));
        $feeTransaction->setData(array(
            'previous_transaction'  =>  $transaction->getId(),
            'amount'                =>  $total_fee,
            'type'                  =>  'refund_fee'
        ));
        $feeTransaction->setDebugData(array(
            'previous_balance'  =>  $current_wallet->getBalance(),
            'previous_transaction'  =>  $transaction->getId()
        ));

        $feeTransaction->setPayInInfo(array(
            'previous_transaction'  =>  $transaction->getId(),
            'amount'                =>  $total_fee,
            'description'           =>  'refund'.$method_cname.'->fee'
        ));

        $feeTransaction->setType('fee');

        $feeTransaction->setTotal($total_fee);

        $mongo = $this->get('doctrine_mongodb')->getManager();
        $mongo->persist($feeTransaction);
        $mongo->flush();

        $balancer = $this->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($user, $total_fee, $feeTransaction );

        //empezamos el reparto
        $group = $user->getGroups()[0];
        $creator = $group->getCreator();

        if(!$creator) throw new HttpException(404,'Creator not found');

        $transaction_id = $transaction->getId();
        $dealer = $this->get('net.telepay.commons.fee_deal');
        $dealer->inversedDeal(
            $creator,
            $amount,
            $method_cname,
            $transaction->getType(),
            $currency,
            $total_fee,
            $transaction_id,
            $transaction->getVersion()
        );

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

    public function _getLimitCount(User $user, $method){
        $em = $this->getDoctrine()->getManager();

        $limits = $user->getLimitCount();
        $user_limit = false;
        foreach ( $limits as $limit ){
            if($limit->getCname() == $method->getCname().'-'.$method->getType()){
                $user_limit = $limit;
            }
        }

        //if user hasn't limit create it
        if(!$user_limit){
            $user_limit = LimitCount::createFromController($method->getCname().'-'.$method->getType(), $user);
            $em->persist($user_limit);
            $em->flush();
        }

        return $user_limit;
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

        //if limit doesn't exist create it
        if(!$group_limit){
            $group_limit = LimitDefinition::createFromController($method->getCname().'-'.$method->getType(), $group);
            $group_limit->setCurrency($method->getCurrency());
            $em->persist($group_limit);
            $em->flush();
        }

        return $group_limit;
    }

}


