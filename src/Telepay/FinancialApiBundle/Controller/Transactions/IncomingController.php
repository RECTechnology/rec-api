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
use Telepay\FinancialApiBundle\Entity\UserWallet;

class IncomingController extends RestApiController{

    static $OLD_CNAME_ID_MAPPINGS = array(
        "sample" => 1,
        "aaa" => 3,
        "aaaa" => 4
    );

    /**
     * @Rest\View
     */
    public function make(Request $request, $version_number, $service_cname, $id = null){

        $service = $this->get('net.telepay.services.'.$service_cname.'.v'.$version_number);

        $service_list = $this->get('security.context')->getToken()->getUser()->getServicesList();

        if (!in_array($service_cname, $service_list)) {
            throw $this->createAccessDeniedException();
        }

        $dataIn = array();
        foreach($service->getFields() as $field){
            if(!$request->request->has($field))
                throw new HttpException(400, "Parameter '".$field."' not found");
            else $dataIn[$field] = $request->get($field);
        }

        //si en service->getFields no esta url_notification y si esta en el request lo añadimos al data in
        if(!isset($service->getFields()['url_notification']) && $request->request->has('url_notification')){
            $dataIn['url_notification'] = $request->request->get('url_notification');
        }

        if($request->request->has('sms_language')){
            $dataIn['sms_language']=$request->request->get('sms_language');
        }

        $concept = '';
        if($request->request->has('description')) $concept = $request->request->get('description');
        if($request->request->has('concept')) $concept = $request->request->get('concept');
        if($request->request->has('reference')) $concept = $request->request->get('reference');

        if(isset($dataIn['email'])){
            if (!filter_var($dataIn['email'], FILTER_VALIDATE_EMAIL)) {
                throw new HttpException(400, 'Invalid email');
            }
        }

        $dataIn['description'] = $concept;

        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();

        $user = $this->container->get('security.context')->getToken()->getUser();
        $transaction = Transaction::createFromRequest($request);
        $transaction->setService($service_cname);
        $transaction->setUser($user->getId());
        $transaction->setVersion($version_number);
        $transaction->setDataIn($dataIn);
        $dm->persist($transaction);

        //TODO posible millora en un query molon
        //obtain and check limits
        $user = $this->getUser();

        //obtener group
        $group = $user->getGroups()[0];

        //obtener comissiones del grupo
        $group_commissions = $group->getCommissions();
        $group_commission = false;
        foreach ( $group_commissions as $commission ){
            if ( $commission->getServiceName() == $service_cname ){
                $group_commission = $commission;
            }
        }

        //if group commission not exists we create it
        if(!$group_commission){
            $group_commission = ServiceFee::createFromController($service_cname, $group);
            $group_commission->setCurrency($service->getCurrency());
            $em->persist($group_commission);
            $em->flush();
        }

        $amount = $dataIn['amount'];
        $transaction->setAmount($amount);

        //add commissions to check
        $fixed_fee = $group_commission->getFixed();
        $variable_fee = round(($group_commission->getVariable()/100)*$amount, 0);
        $total_fee = $fixed_fee + $variable_fee;

        //add fee to transaction
        $transaction->setVariableFee($variable_fee);
        $transaction->setFixedFee($fixed_fee);
        $dm->persist($transaction);

        //check if is cash-out
        if($service->getcashDirection()=='out'){
            //le cambiamos el signo para guardarla i marcarla como salida en el wallet
            $transaction->setTotal(-$amount);
            $total = $amount + $variable_fee + $fixed_fee;
        }else{
            $total = $amount - $variable_fee - $fixed_fee;
            $transaction->setTotal($amount);
        }

        //obtain user limits
        $limits = $user->getLimitCount();
        $user_limit = false;
        foreach ( $limits as $limit ){
            if($limit->getCname() == $service_cname){
                $user_limit=$limit;
            }
        }

        //if user hasn't limit create it
        if(!$user_limit){
            $user_limit = LimitCount::createFromController($service_cname,$user);
            $em->persist($user_limit);
            $em->flush();
        }

        //obtain group limit
        $group_limits = $group->getLimits();
        $group_limit = false;
        foreach ( $group_limits as $limit ){
            if( $limit->getCname() == $service_cname){
                $group_limit = $limit;
            }
        }

        //if limit doesn't exist create it
        if(!$group_limit){
            $group_limit = LimitDefinition::createFromController($service_cname,$group);
            $group_limit->setCurrency($service->getCurrency());
            $em->persist($group_limit);
            $em->flush();
        }

        $new_user_limit=(new LimitAdder())->add($user_limit,$total);

        $checker = new LimitChecker();

        if(!$checker->leq($new_user_limit,$group_limit))
            throw new HttpException(509,'Limit exceeded');

        //obtain wallet and check founds for cash_out services
        $wallets = $user->getWallets();

        //check if the service is halcash because we have various currencys
        if($service_cname == 'halcash_send'){
            if(isset($dataIn) && $dataIn['country'] == 'PL'){
                $service_currency = 'PLN';
            }else{
                $service_currency = $service->getCurrency();
            }

        }else{
            $service_currency = $service->getCurrency();
        }

        $current_wallet = null;

        $transaction->setCurrency($service_currency);

        //******    CHECK IF THE TRANSACTION IS CASH-OUT     ********
        if($service->getcashDirection() == 'out'){

            foreach ( $wallets as $wallet){
                if ($wallet->getCurrency() == $service_currency){
                    if($wallet->getAvailable() <= $total) throw new HttpException(509,'Not founds enough');
                    //Bloqueamos la pasta en el wallet
                    $actual_available = $wallet->getAvailable();
                    $new_available = $actual_available-$total;
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

            try {
                $transaction = $service->create($transaction);
            }catch (HttpException $e){
                if( $transaction->getStatus() === Transaction::$STATUS_CREATED ){

                    if($e->getStatusCode()>=500){
                        $transaction->setStatus(Transaction::$STATUS_FAILED);
                    }else{
                        $transaction->setStatus( Transaction::$STATUS_ERROR );
                    }
                    //desbloqueamos la pasta del wallet
                    $current_wallet->setAvailable($current_wallet->getAvailable()+$total);
                    $em->persist($current_wallet);
                    $em->flush();
                    $dm->persist($transaction);
                    $dm->flush();

                    $this->container->get('notificator')->notificate($transaction);

                    if ($transaction->getStatus() == Transaction::$STATUS_ERROR){
                        throw $e;
                    }

                }

            }

            $dm->persist($transaction);
            $dm->flush();

            //pay fees and dealer always and set new balance
            if( $transaction->getStatus() === Transaction::$STATUS_CREATED || $transaction->getStatus() === Transaction::$STATUS_SUCCESS ){
                $this->container->get('notificator')->notificate($transaction);

                if( $service_cname != 'echo'){
                    //restar al usuario el amount + comisiones
                    $current_wallet->setBalance($current_wallet->getBalance()-$total);
                    //TODO insert new line in the balance
                    $balancer = $this->get('net.telepay.commons.balance_manipulator');
                    $balancer->addBalance($user, -$amount, $transaction);

                    $em->persist($current_wallet);
                    $em->flush();
                }

                if( $total_fee != 0){
                    //nueva transaccion restando la comision al user
                    try{
                        $this->_dealer($transaction,$current_wallet);
                    }catch (HttpException $e){
                        throw $e;
                    }
                }
            }

        }else{     //CASH - IN

            foreach ( $wallets as $wallet){
                if ($wallet->getCurrency() === $transaction->getCurrency()){
                    $current_wallet=$wallet;
                }
            }

            $scale=$current_wallet->getScale();
            $transaction->setScale($scale);
            $dm->persist($transaction);
            $dm->flush();
            try {
                $transaction = $service->create($transaction);
            }catch (HttpException $e){
                if($transaction->getStatus() === Transaction::$STATUS_CREATED)
                    $transaction->setStatus(Transaction::$STATUS_FAILED);
                $this->container->get('notificator')->notificate($transaction);
                $dm->persist($transaction);
                $dm->flush();
                throw $e;
            }

            $transaction = $this->get('notificator')->notificate($transaction);
            $em->flush();

            $transaction->setUpdated(new \DateTime());

            $dm->persist($transaction);
            $dm->flush();

            //si la transaccion se finaliza se suma al wallet i se reparten las comisiones
            if($transaction->getStatus() === Transaction::$STATUS_SUCCESS && $service_cname != 'echo' ){
                $transaction = $this->get('notificator')->notificate($transaction);
                //sumar al usuario el amount completo
                $current_wallet->setAvailable($current_wallet->getAvailable()+$total);
                $current_wallet->setBalance($current_wallet->getBalance()+$total);

                $balancer = $this->get('net.telepay.commons.balance_manipulator');
                $balancer->addBalance($user, $amount, $transaction);

                $em->persist($current_wallet);
                $em->flush();

                if($total_fee != 0){
                    // nueva transaccion restando la comision al user
                    try{
                        $this->_dealer($transaction,$current_wallet);
                    }catch (HttpException $e){
                        throw $e;
                    }
                }

            }

        }

        if($transaction == false) throw new HttpException(500, "oOps, some error has occurred within the call");

        return $this->restTransaction($transaction, "Done");
    }

    /**
     * @Rest\View
     */
    public function update(Request $request, $version_number, $service_cname, $id){

        $service = $this->get('net.telepay.services.'.$service_cname.'.v'.$version_number);

        $user = $this->get('security.context')->getToken()->getUser();
        $service_list = $user->getServicesList();

        if (!in_array($service_cname, $service_list)) {
            throw $this->createAccessDeniedException();
        }

        $data=$request->request->all();

        $mongo = $this->get('doctrine_mongodb')->getManager();
        $transaction =$mongo->getRepository('TelepayFinancialApiBundle:Transaction')->findOneBy(array(
            'id'        => $id,
            'service'   =>  $service_cname,
            'user'      =>  $user->getId()
        ));

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        if($transaction->getService() != $service->getCname()) throw new HttpException(404, 'Transaction not found');

        //retry=true y cancel=true aqui
        if( isset( $data['retry'] ) || isset ( $data ['cancel'] )){

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
            $total_amount = $transaction_amount - $total_fee ;

            //    RETRY
            if( isset( $data['retry'] ) && $data['retry'] == true ){

                if( $transaction->getStatus()== Transaction::$STATUS_FAILED ){
                    //discount available
                    $current_wallet->setAvailable($current_wallet->getAvailable() - $amount );
                    $em->persist($current_wallet);
                    $em->flush();
                    try {
                        $transaction = $service->create($transaction);
                    }catch (HttpException $e){

                        if($e->getStatusCode()>=500){
                            $transaction->setStatus(Transaction::$STATUS_FAILED);
                        }else{
                            $transaction->setStatus( Transaction::$STATUS_ERROR );

                        }
                        $mongo->persist($transaction);
                        $mongo->flush();
                        //devolver la pasta de la transaccion al wallet si es cash out (al available)
                        if( $service->getcashDirection() == 'out' ){
                            $current_wallet->setAvailable($current_wallet->getAvailable() + $amount );
                        }

                        $transaction = $this->get('notificator')->notificate($transaction);

                        $em->persist($current_wallet);
                        $em->flush();

                        if($transaction->getStatus() == Transaction::$STATUS_ERROR){
                            throw $e;
                        }


                    }

                    $mongo->persist($transaction);
                    $mongo->flush();
                    //transaccion exitosa
                    //actualizar el wallet del user if success

                    if( $transaction->getStatus() == Transaction::$STATUS_CREATED
                        && $service->getcashDirection() == 'out'
                        && $service_cname != 'echo'){
                        $transaction = $this->get('notificator')->notificate($transaction);
                        //sumamos la pasta al wallet
                        $balancer = $this->get('net.telepay.commons.balance_manipulator');
                        $balancer->addBalance($user, -$amount, $transaction);
                        if($total_fee != 0){
                            //cobramos comisiones al user y hacemos el reparto

                            try{
                                $this->_dealer($transaction,$current_wallet);
                            }catch (HttpException $e){
                                throw $e;
                            }

                        }

                    }

                    if( $transaction->getStatus() == Transaction::$STATUS_SUCCESS && $service_cname != 'echo' ){
                        $transaction = $this->get('notificator')->notificate($transaction);

                        if( $service->getcashDirection() == 'out' ){
                            $current_wallet->setBalance($current_wallet->getBalance() - $amount - $total_fee );
                            $balancer = $this->get('net.telepay.commons.balance_manipulator');
                            $balancer->addBalance($user, -$amount, $transaction);
                        }else{
                            $current_wallet->setAvailable($current_wallet->getAvailable() + $total_amount );
                            $current_wallet->setBalance($current_wallet->getBalance() + $total_amount);
                            $balancer = $this->get('net.telepay.commons.balance_manipulator');
                            $balancer->addBalance($user, $amount, $transaction);
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

                }elseif( $transaction->getStatus()== Transaction::$STATUS_CANCELLED ){

                    //send transaction
                    try {
                        $transaction = $service->create($transaction);
                    }catch (HttpException $e){

                        if($e->getStatusCode()>=500){
                            $transaction->setStatus(Transaction::$STATUS_FAILED);
                            $transaction = $this->get('notificator')->notificate($transaction);
                        }else{
                            $transaction->setStatus( Transaction::$STATUS_ERROR );
                            $mongo->persist($transaction);
                            $mongo->flush();
                            //devolver la pasta de la transaccion al wallet si es cash out (al available)
                            if( $service->getcashDirection() == 'out' ){
                                $current_wallet->setAvailable($current_wallet->getAvailable() + $amount );
                            }

                            $transaction = $this->get('notificator')->notificate($transaction);

                            $em->persist($current_wallet);
                            $em->flush();

                            throw $e;
                        }

                    }

                    if( $transaction->getStatus() == Transaction::$STATUS_CREATED
                        && $service->getcashDirection() == 'out'
                        && $service_cname != 'echo'){
                        $transaction = $this->get('notificator')->notificate($transaction);
                        //sumamos la pasta al wallet
                        if($total_fee != 0){
                            //cobramos comisiones al user y hacemos el reparto

                            try{
                                $this->_dealer($transaction,$current_wallet);
                            }catch (HttpException $e){
                                throw $e;
                            }

                        }

                    }elseif( $transaction->getStatus() != Transaction::$STATUS_CREATED
                        && $service->getcashDirection() == 'out'
                        && $service_cname != 'echo'){
                        $transaction->setStatus(Transaction::$STATUS_CANCELLED);
                        $current_wallet->setAvailable($current_wallet->getAvailable() + $amount );
                        $balancer = $this->get('net.telepay.commons.balance_manipulator');
                        $balancer->addBalance($user, $amount, $transaction);
                        $em->persist($current_wallet);
                        $em->flush();
                        $mongo->persist($transaction);
                        $mongo->flush();
                    }

                }else{
                    throw new HttpException(409,"This transaction can't be retried. First has to be cancelled");
                }

            }

            if( isset( $data['cancel'] ) && $data['cancel'] == true ){

                //el cash-out solo se puede cancelar si esta en created review o success
                //el cash-in de momento no se puede cancelar
                if($transaction->getStatus()== Transaction::$STATUS_CREATED
                    || $transaction->getStatus() == Transaction::$STATUS_REVIEW
                    || $transaction->getStatus() == Transaction::$STATUS_FAILED){

                    if($transaction->getStatus() == Transaction::$STATUS_FAILED){
                        $transaction->setStatus(Transaction::$STATUS_CANCELLED );
                        $transaction = $this->get('notificator')->notificate($transaction);

                        $mongo->persist($transaction);
                        $mongo->flush();
                        //desbloquear pasta del wallet
                        if( $service->getcashDirection() == 'out' ){
                            $current_wallet->setAvailable($current_wallet->getAvailable() + $amount );
                        }

                        $em->persist($current_wallet);
                        $em->flush();
                    }else{
                        try {
                            $transaction = $service->cancel($transaction);
                        }catch (HttpException $e){
                            throw $e;
                        }

                        $transaction->setStatus(Transaction::$STATUS_CANCELLED );
                        $mongo->persist($transaction);
                        $mongo->flush();
                        //desbloquear pasta del wallet
                        if( $service->getcashDirection() == 'out' ){
                            $current_wallet->setAvailable($current_wallet->getAvailable() + $amount );
                            $balancer = $this->get('net.telepay.commons.balance_manipulator');
                            $balancer->addBalance($user, $amount, $transaction);
                        }

                        $em->persist($current_wallet);
                        $em->flush();

                        $transaction = $this->get('notificator')->notificate($transaction);

                    }

                }else{
                    throw new HttpException(403, "This transaction can't be cancelled.");
                }

            }
        }else{
            $transaction = $service->update($transaction,$data);
        }


        $mongo->persist($transaction);
        $mongo->flush();

        return $this->restTransaction($transaction, "Got ok");
    }

    /**
     * @Rest\View
     */
    public function check(Request $request, $version_number, $service_cname, $id){

        $service = $this->get('net.telepay.services.'.$service_cname.'.v'.$version_number);

        $user = $this->get('security.context')->getToken()->getUser();
        $service_list = $user->getServicesList();

        if (!in_array($service_cname, $service_list)) {
            throw $this->createAccessDeniedException();
        }

        $mongo = $this->get('doctrine_mongodb')->getManager();
        $transaction =$mongo->getRepository('TelepayFinancialApiBundle:Transaction')->findOneBy(array(
            'id'        => $id,
            'service'   =>  $service_cname,
            'user'      =>  $user->getId()
        ));

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        if($transaction->getStatus() == Transaction::$STATUS_CREATED ||
            $transaction->getStatus() == Transaction::$STATUS_RECEIVED ||
            $transaction->getStatus() == Transaction::$STATUS_FAILED ||
            $transaction->getStatus() == Transaction::$STATUS_REVIEW ){

            if($transaction->getService() != $service->getCname()) throw new HttpException(404, 'Transaction not found');
            $previuos_status = $transaction->getStatus();
            $transaction = $service->check($transaction);

            $mongo->persist($transaction);
            $mongo->flush();

            //if previous status != current status update wallets
            if( $previuos_status != $transaction->getStatus()){
                $transaction->setUpdated(new \MongoDate());
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
                    if($service->getcashDirection() == 'out'){
                        $current_wallet->setAvailable($current_wallet->getAvailable() + $transaction->getAmount());
                        $em->persist($current_wallet);
                        $em->flush();

                    }

                }elseif($transaction->getStatus() == Transaction::$STATUS_SUCCESS ){
                    //Update balance
                    if($service->getcashDirection() == 'out'){
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

        return $this->restTransaction($transaction, "Got ok");

    }

    /**
     * @Rest\View
     */
    public function find(Request $request, $version_number, $service_cname){

        $service = $this->get('net.telepay.services.'.$service_cname.'.v'.$version_number);

        $service_list = $this->get('security.context')->getToken()->getUser()->getServicesList();

        if (!in_array($service_cname, $service_list)) {
            throw $this->createAccessDeniedException();
        }

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        $dm = $this->get('doctrine_mongodb')->getManager();
        $user = $this->get('security.context')
            ->getToken()->getUser();

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
                ->field('service')->equals($service->getCname())
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
            if(typeof this.status !== 'undefined' && String(this.status).indexOf('$search') > -1){ return true;}
            if(typeof this.service !== 'undefined' && String(this.service).indexOf('$search') > -1){ return true;}
            if(String(this._id).indexOf('$search') > -1){ return true;}

            return false;
            }")
                ->sort($order,$dir)
                ->getQuery()
                ->execute();

        }else{
            $order = "id";
            $dir = "desc";

            $transactions = $qb
                ->field('user')->equals($userId)
                ->field('service')->equals($service->getCname())
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
        $service_currency = $service->getCurrency();

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
    public function notificate(Request $request, $version_number, $service_cname, $id) {

        $service = $this->get('net.telepay.services.'.$service_cname.'.v'.$version_number);

        $mongo = $this->get('doctrine_mongodb')->getManager();
        $transaction =$mongo->getRepository('TelepayFinancialApiBundle:Transaction')->findOneBy(array(
            'id'        => $id,
            'service'   =>  $service_cname
        ));

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        if($transaction->getService() != $service->getCname()) throw new HttpException(404, 'Transaction not found');

        if( $transaction->getStatus() != Transaction::$STATUS_CREATED ) throw new HttpException(409,'Tranasction already processed.');

        $transaction = $service->notificate($transaction, $request->request->all());

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
        $service_cname = $transaction->getService();

        $em = $this->getDoctrine()->getManager();

        $total_fee = $transaction->getFixedFee() + $transaction->getVariableFee();

        $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($transaction->getUser());

        $feeTransaction = Transaction::createFromTransaction($transaction);
        $feeTransaction->setAmount($total_fee);
        $feeTransaction->setDataIn(array(
            'previous_transaction'  =>  $transaction->getId(),
            'amount'                =>  -$total_fee,
            'description'           =>  $service_cname.'->fee'
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

        $feeTransaction->setTotal(-$total_fee);

        $mongo = $this->get('doctrine_mongodb')->getManager();
        $mongo->persist($feeTransaction);
        $mongo->flush();

        $balancer = $this->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($user, -$total_fee, $feeTransaction );

        //empezamos el reparto
        $group = $user->getGroups()[0];
        $creator = $group->getCreator();

        if(!$creator) throw new HttpException(404,'Creator not found');

        $transaction_id = $transaction->getId();
        $dealer = $this->get('net.telepay.commons.fee_deal');
        $dealer->deal(
            $creator,
            $amount,
            $service_cname,
            $currency,
            $total_fee,
            $transaction_id,
            $transaction->getVersion()
        );

    }

}


