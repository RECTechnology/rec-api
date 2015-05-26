<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/22/15
 * Time: 8:16 PM
 */



namespace Telepay\FinancialApiBundle\Controller\Transactions;


use Symfony\Component\EventDispatcher\Tests\Service;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;

use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\FeeDeal;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\LimitAdder;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\LimitChecker;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\LimitCount;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Entity\User;
use Telepay\FinancialApiBundle\Entity\UserWallet;

class IncomingController extends RestApiController{

    /**
     * @Rest\View
     */
    public function make(Request $request, $version_number, $service_cname, $id = null){

        $service = $this->get('net.telepay.services.'.$service_cname.'.v'.$version_number);

        if (false === $this->get('security.authorization_checker')->isGranted($service->getRole())) {
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

        //obtener group
        $group=$user->getGroups()[0];

        //obtener comissiones del grupo
        $group_commissions=$group->getCommissions();
        $group_commission=false;
        foreach ( $group_commissions as $commission ){
            if ( $commission->getServiceName() == $service_cname ){
                $group_commission = $commission;
            }
        }

        //if group commission not exists we create it
        if(!$group_commission){
            $group_commission = ServiceFee::createFromController($service_cname, $group);
            $em->persist($group_commission);
            $em->flush();
        }

        $amount=$dataIn['amount'];
        $transaction->setAmount($amount);

        //add commissions to check
        $fixed_fee = $group_commission->getFixed();
        $variable_fee = $group_commission->getVariable()*$amount;
        $total_fee = $fixed_fee + $variable_fee;

        //add fee to transaction
        $transaction->setVariableFee($variable_fee);
        $transaction->setFixedFee($fixed_fee);
        $dm->persist($transaction);

        //check if is cash-out
        if($service->getcashDirection()=='out'){
            //le cambiamos el signo para guardarla i marcarla como salida en el wallet
            $transaction->setTotal(-$amount);
            $total=$amount+$variable_fee+$fixed_fee;
        }else{
            $total=$amount-$variable_fee-$fixed_fee;
            $transaction->setTotal($amount);
        }

        //obtain user limits
        $limits=$user->getLimitCount();
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
        $group_limits=$group->getLimits();
        $group_limit = false;
        foreach ( $group_limits as $limit ){
            if( $limit->getCname() == $service_cname){
                $group_limit = $limit;
            }
        }

        //if limit doesn't exist create it
        if(!$group_limit){
            $group_limit = LimitDefinition::createFromController($service_cname,$group);
            $em->persist($group_limit);
            $em->flush();
        }

        $new_user_limit=(new LimitAdder())->add($user_limit,$total);

        $checker = new LimitChecker();

        if(!$checker->leq($new_user_limit,$group_limit))
            throw new HttpException(509,'Limit exceeded');

        //obtain wallet and check founds for cash_out services
        $wallets=$user->getWallets();

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

        $current_wallet=null;

        $transaction->setCurrency($service_currency);

        //******    CHECK IF THE TRANSACTION IS CASH-OUT     ********
        if($service->getcashDirection()=='out'){

            foreach ( $wallets as $wallet){
                if ($wallet->getCurrency() == $service_currency){
                    if($wallet->getAvailable()<=$total) throw new HttpException(509,'Not founds enough');
                    //Bloqueamos la pasta en el wallet
                    $actual_available=$wallet->getAvailable();
                    $new_available=$actual_available-$total;
                    $wallet->setAvailable($new_available);
                    $em->persist($wallet);
                    $em->flush();
                    $current_wallet=$wallet;
                }
            }

            $scale=$current_wallet->getScale();
            $transaction->setScale($scale);

            try {
                $transaction = $service->create($transaction);
            }catch (HttpException $e){
                if( $transaction->getStatus() === Transaction::$STATUS_CREATED ){
                    if($e->getStatusCode()>=500){
                        $transaction->setStatus(Transaction::$STATUS_FAILED);
                        $this->container->get('notificator')->notificate($transaction);
                    }else{
                        $transaction->setStatus( Transaction::$STATUS_ERROR );
                        //desbloqueamos la pasta del wallet
                        $current_wallet->setAvailable($current_wallet->getAvailable()+$total);
                        $em->persist($current_wallet);
                        $em->flush();
                        $dm->persist($transaction);
                        $dm->flush();

                        $this->container->get('notificator')->notificate($transaction);

                        throw $e;
                    }

                }

            }
            $dm->persist($transaction);
            $dm->flush();

            //pay fees and dealer always
            if( $transaction->getStatus() === Transaction::$STATUS_CREATED || $transaction->getStatus() === Transaction::$STATUS_SUCCESS ){
                $this->container->get('notificator')->notificate($transaction);
                if( $service_cname != 'echo'){
                    //restar al usuario el amount + comisiones
                    $current_wallet->setBalance($current_wallet->getBalance()-$total);
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

            $em->flush();

            foreach ( $wallets as $wallet){
                if ($wallet->getCurrency() === $transaction->getCurrency()){
                    $current_wallet=$wallet;
                }
            }

            $scale=$current_wallet->getScale();
            $transaction->setScale($scale);


            $transaction->setUpdated(new \DateTime());

            $dm->persist($transaction);
            $dm->flush();

            //si la transaccion se finaliza se suma al wallet i se reparten las comisiones
            if($transaction->getStatus() === Transaction::$STATUS_SUCCESS && $service_cname != 'echo' ){
                $transaction = $this->get('notificator')->notificate($transaction);
                //sumar al usuario el amount completo
                $current_wallet->setAvailable($current_wallet->getAvailable()+$total);
                $current_wallet->setBalance($current_wallet->getBalance()+$total);
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

        if (false === $this->get('security.authorization_checker')->isGranted($service->getRole())) {
            throw $this->createAccessDeniedException();
        }

        $data=$request->request->all();

        $mongo = $this->get('doctrine_mongodb')->getManager();

        $transaction =$mongo->getRepository('TelepayFinancialApiBundle:Transaction')->find($id);

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        if($transaction->getService() != $service->getCname()) throw new HttpException(404, 'Transaction not found');



        //retry=true y cancel=true aqui
        if( isset( $data['retry'] ) || isset ( $data ['cancel'] )){

            //Search user
            $user_id = $transaction->getUser();

            $em=$this->getDoctrine()->getManager();

            $user =$em->getRepository('TelepayFinancialApiBundle:User')->find($user_id);
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

                    $mongo->persist($transaction);
                    $mongo->flush();
                    //transaccion exitosa
                    //actualizar el wallet del user if success

                    if( $transaction->getStatus() == Transaction::$STATUS_CREATED && $service->getcashDirection() == 'out' && $service_cname != 'echo'){
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

                    }

                    if( $transaction->getStatus() == Transaction::$STATUS_SUCCESS && $service_cname != 'echo' ){
                        $transaction = $this->get('notificator')->notificate($transaction);

                        if( $service->getcashDirection() == 'out' ){
                            $current_wallet->setBalance($current_wallet->getBalance() - $amount - $total_fee );
                        }else{
                            $current_wallet->setAvailable($current_wallet->getAvailable() + $total_amount );
                            $current_wallet->setBalance($current_wallet->getBalance() + $total_amount);
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

                }else{
                    throw new HttpException(409,"This transaction can't be retried");
                }

            }

            if( isset( $data['cancel'] ) && $data['cancel'] == true ){

                //el cash-out solo se puede cancelar si esta en created review o success
                //el cash-in de momento no se puede cancelar
                if($transaction->getStatus()== Transaction::$STATUS_CREATED || $transaction->getStatus() == Transaction::$STATUS_REVIEW || $transaction->getStatus() == Transaction::$STATUS_FAILED){

                    if($transaction->getStatus() == Transaction::$STATUS_FAILED){
                        $transaction->setStatus(Transaction::$STATUS_CANCELLED );

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

        if (false === $this->get('security.authorization_checker')->isGranted($service->getRole())) {
            throw $this->createAccessDeniedException();
        }

        $mongo = $this->get('doctrine_mongodb')->getManager();
        $transaction =$mongo->getRepository('TelepayFinancialApiBundle:Transaction')->find($id);

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        if($transaction->getStatus() == Transaction::$STATUS_CREATED ||
            $transaction->getStatus() == Transaction::$STATUS_RECEIVED ||
            $transaction->getStatus() == Transaction::$STATUS_FAILED ||
            $transaction->getStatus() == Transaction::$STATUS_REVIEW ){

            if($transaction->getService() != $service->getCname()) throw new HttpException(404, 'Transaction not found');
            $previuos_status = $transaction->getStatus();
            $transaction = $service->check($transaction);

            $transaction->setUpdated(new \DateTime());
            $mongo->persist($transaction);
            $mongo->flush();

            //if previous status != current status update wallets
            if( $previuos_status != $transaction->getStatus()){
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

                if($transaction->getStatus() == Transaction::$STATUS_CANCELLED || $transaction->getStatus() == Transaction::$STATUS_EXPIRED || $transaction->getStatus() == Transaction::$STATUS_ERROR){
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

        if (false === $this->get('security.authorization_checker')->isGranted($service->getRole())) {
            throw $this->createAccessDeniedException();
        }

        if($request->query->has('start_time') && is_numeric($request->query->get('start_time')))
            $start_time = new \MongoDate($request->query->get('start_time'));
        else $start_time = new \MongoDate(time()-3*31*24*3600); // 3 month ago

        if($request->query->has('end_time') && is_numeric($request->query->get('end_time')))
            $end_time = new \MongoDate($request->query->get('end_time'));
        else $end_time = new \MongoDate(); // now

        if($request->query->has('limit')) $limit = intval($request->query->get('limit'));
        else $limit = 10;

        if($request->query->has('offset')) $offset = intval($request->query->get('offset'));
        else $offset = 0;

        $userId = $this->get('security.context')->getToken()->getUser()->getId();

        $dm = $this->get('doctrine_mongodb')->getManager();

        $transactions = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('user')->equals($userId)
            ->field('service')->equals($service->getCname())
            ->field('created')->gt($start_time)
            ->field('created')->lt($end_time)
            ->sort('created', 'desc')
            ->skip($offset)
            ->limit($limit)
            ->getQuery()->execute();

        $transArray = [];
        foreach($transactions->toArray() as $transaction){
            $transArray []= $transaction;
        }

        if(array_key_exists($service->getCname(),static::$OLD_CNAME_ID_MAPPINGS)) {
            $transactionsOld = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('user')->equals($userId)
                ->field('service')->equals(static::$OLD_CNAME_ID_MAPPINGS[$service->getCname()])
                ->field('created')->gt($start_time)
                ->field('created')->lt($end_time)
                ->sort('created', 'desc')
                ->skip($offset)
                ->limit($limit)
                ->getQuery()->execute();
            foreach($transactionsOld->toArray() as $transaction){
                $transArray []= $transaction;
            }
        }

        //esto es asi porque hemos cambiado la respuesta en restV2 ( ahora tiene algunos campos más ).
        return $this->restV2(
            200,
            "ok",
            "Request successful",
            $transArray
        );
    }

    /**
     * @Rest\View
     */
    public function notificate(Request $request, $version_number, $service_cname, $id) {

        $service = $this->get('net.telepay.services.'.$service_cname.'.v'.$version_number);

        $mongo = $this->get('doctrine_mongodb')->getManager();

        $transaction =$mongo->getRepository('TelepayFinancialApiBundle:Transaction')->find($id);

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        if($transaction->getService() != $service->getCname()) throw new HttpException(404, 'Transaction not found');

        if( $transaction->getStatus() != Transaction::$STATUS_CREATED ) throw new HttpException(409,'Tranasction already processed.');

        $transaction = $service->notificate($transaction, $request->request->all());

        $transaction->setUpdated(new \DateTime());
        $mongo->persist($transaction);
        $mongo->flush();

        if(!$transaction) throw new HttpException(500, "oOps, the notification failed");

        if($transaction->getStatus() == Transaction::$STATUS_SUCCESS ){
            //update wallet
            $user_id = $transaction->getUser();

            $em=$this->getDoctrine()->getManager();

            $user =$em->getRepository('TelepayFinancialApiBundle:User')->find($user_id);
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
            $amount = $transaction->getAmount();
            $total_fee = $transaction->getFixedFee() + $transaction->getVariableFee();
            $total_amount = $transaction_amount - $total_fee ;

            if( $service->getcashDirection() == 'out' ){
                $current_wallet->setBalance($current_wallet->getBalance() + $total_amount );
            }else{
                $current_wallet->setAvailable($current_wallet->getAvailable() + $total_amount );
                $current_wallet->setBalance($current_wallet->getBalance() + $total_amount);
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

        return $this->restV2(200, "ok", "Notification successful");

    }

    public function _dealer(Transaction $transaction, UserWallet $current_wallet){

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
            'amount'                =>  -$total_fee
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

        //empezamos el reparto
        $group = $user->getGroups()[0];
        $creator = $group->getCreator();

        if(!$creator) throw new HttpException(404,'Creator not found');

        $transaction_id = $transaction->getId();
        $dealer = $this->get('net.telepay.commons.fee_deal');
        $dealer->deal($creator,$amount,$service_cname,$currency,$total_fee,$transaction_id,$transaction->getVersion());

    }

}


