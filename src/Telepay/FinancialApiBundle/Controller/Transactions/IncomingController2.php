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
use Telepay\FinancialApiBundle\Entity\NFCCard;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Security\Authentication\Token\SignatureToken;

class IncomingController2 extends RestApiController{

    /**
     * @Rest\View
     */
    public function make(Request $request, $version_number, $type, $method_cname){
        $user = $this->get('security.context')->getToken()->getUser();
        if (!$this->get('security.context')->isGranted('ROLE_WORKER')) throw new HttpException(403, 'You don\' have the necessary permissions');
        if($request->request->has('company_id')){
            $group = $this->getDoctrine()->getManager()
                ->getRepository('TelepayFinancialApiBundle:Group')
                ->find($request->request->get('company_id'));
            if(!$group) throw new HttpException(404, 'Company not found');
        }else{
            $group = $this->_getCurrentCompany($user);
        }
        //check if this user has this company
        $this->_checkPermissions($user, $group);
        $params = $request->request->all();
        return $this->createTransaction($params, $version_number, $type, $method_cname, $user->getId(), $group, $request->getClientIp());
    }

    public function checkReceiverData(Request $request){
        $em = $this->getDoctrine()->getManager();
        if($request->query->has('address') && $request->query->get('address')!=''){
            $address = $request->query->get('address');
            $destination = $em->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
                'rec_address' => $address
            ));
            if(!$destination){
                throw new HttpException(400, 'Incorrect address');
            }
            $data = array(
                $destination->getName(),
                $destination->getCompanyImage()
            );
            return $this->restV2(201,"ok", "Vendor information", $data);
        }
        throw new HttpException(400, 'Incorrect address');
    }

    public function checkSenderData(Request $request){
        $em = $this->getDoctrine()->getManager();
        if($request->query->has('card_id') && $request->query->get('card_id')!=''){
            $card_id = $request->query->get('card_id');
            $sender = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->findOneBy(array(
                'id_card' => $card_id
            ));
            $customer = $sender->getUser();
            $data = array(
                $customer->getName(),
                $customer->getProfileImage()
            );
            return $this->restV2(201,"ok", "Sender information", $data);
        }
        throw new HttpException(400, 'Incorrect address');
    }


    public function createTransaction($data, $version_number, $type, $method_cname, $user_id, $group, $ip){
        $logger = $this->get('transaction.logger');
        $logger->info('Incomig transaction...Method-> '.$method_cname.' Direction -> '.$type);

        $method = $this->get('net.telepay.'.$type.'.'.$method_cname.'.v'.$version_number);

        $logger->info('Get mongo service');

        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
            'id' => $user_id
        ));

        //obtain wallet and check founds for cash_out services for this group
        $wallet = $group->getWallet($method->getCurrency());

        if(array_key_exists('amount', $data) && $data['amount']!='' && intval($data['amount'])>0){
            $amount = $data['amount'];
        }
        else{
            throw new HttpException(400, 'Param amount not found or incorrect');
        }

        if($type == 'out'){
            if($wallet->getAvailable() < $amount) throw new HttpException(405,'Not founds enough');
            if(array_key_exists('pin', $data) && $data['pin']!='' && intval($data['pin'])>0){
                $pin = $data['pin'];
                if($user->getPIN()!=$pin){
                    throw new HttpException(400, 'Incorrect Pin');
                }
            }
            else{
                throw new HttpException(400, 'Param pin not found or incorrect');
            }
        }

        $transaction = Transaction::createFromRequestIP($ip);
        $transaction->setService($method_cname);
        $transaction->setMethod($method_cname);
        $admin_id = $this->container->getParameter('admin_user_id');
        $transaction->setUser($user_id==-1?$admin_id:$user_id);
        $transaction->setGroup($group->getId());
        $transaction->setVersion($version_number);
        $transaction->setType($type);
        $transaction->setInternal(false);
        $dm->persist($transaction);

        if(array_key_exists('concept', $data) && $data['concept']!=''){
            $concept = $data['concept'];
        }else{
            throw new HttpException(400, 'Param concept not found');
        }

        if(array_key_exists('url_notification', $data)) $url_notification = $data['url_notification'];
        else $url_notification = '';
        $logger->info('Incomig transaction...getPaymentInfo for company '.$group->getId());

        if($type == 'in'){
            $dataIn = array(
                'amount'    =>  $amount,
                'concept'   =>  $concept,
                'url_notification'  =>  $url_notification
            );
            if(isset($data['commerce_id'])){
                $payment_info = $method->getPayInInfoWithCommerce($data);
                $transaction->setInternal(true);
            }
            elseif(!isset($data['txid'])){
                $payment_info = $method->getPayInInfo($amount);
            }
            else{
                $payment_info = $method->getPayInInfoWithData($data);
            }
            $payment_info['concept'] = $concept;
            if(isset($data['expires_in']) && $data['expires_in'] > 99){
                $payment_info['expires_in'] = $data['expires_in'];
            }
            if(isset($data['sender']) && $data['sender']!='') {
                $sender_id = $data['sender'];
                $sender = $em->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
                    'id' => $sender_id
                ));
                $payment_info['image_sender'] = $sender->getCompanyImage();
                $payment_info['name_sender'] = $sender->getName();
            }
            $transaction->setPayInInfo($payment_info);

        }else{
            $logger->info('else');
            $payment_info = $method->getPayOutInfoData($data);
            $transaction->setPayOutInfo($payment_info);
            $dataIn = array(
                'amount'    =>  $amount,
                'concept'   =>  $concept,
                'url_notification'  =>  $url_notification
            );
        }
        $transaction->setDataIn($dataIn);

        $logger->info('Incomig transaction...FEES');

        $fee_handler = $this->container->get('net.telepay.commons.fee_manipulator');
        $group_commission = $fee_handler->getMethodFees($group, $method);

        $amount = $dataIn['amount'];
        $transaction->setAmount($amount);

        //add commissions to check
        $fixed_fee = $group_commission->getFixed();
        $variable_fee = round(($group_commission->getVariable()/100) * $amount, 0);
        $total_fee = $fixed_fee + $variable_fee;

        //add fee to transaction
        $transaction->setVariableFee($variable_fee);
        $transaction->setFixedFee($fixed_fee);

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

        //check limits with 30 days success/received/created transactions
        //get limit manipulator
        $limitManipulator = $this->get('net.telepay.commons.limit_manipulator');

        $limitManipulator->checkLimits($group, $method, $amount);

        $transaction->setCurrency($method->getCurrency());
        $transaction->setScale($wallet->getScale());

        //******    CHECK IF THE TRANSACTION IS CASH-OUT     ********
        if($type == 'out'){
            $logger->info('Incomig transaction...OUT Available = ' . $wallet->getAvailable() .  " TOTAL: " . $total);
            $address = $payment_info['address'];
            $destination = $em->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
                'rec_address' => $payment_info['address']
            ));

            if(!$destination){
                throw new HttpException(405,'Destination address does not exists');
            }

            if($destination->getRecAddress() == $group->getRecAddress()){
                throw new HttpException(405,'Error, destination address is equal than origin address');
            }

            $payment_info['orig_address'] = $group->getRecAddress();
            $payment_info['orig_nif'] = $user->getDNI();
            $payment_info['orig_group_nif'] = $group->getCif();
            $payment_info['orig_key'] = $group->getKeyChain();
            $payment_info['dest_address'] = $destination->getRecAddress();
            $payment_info['dest_group_nif'] = $destination->getCif();
            $payment_info['dest_key'] = $destination->getKeyChain();

            $logger->info('Incomig transaction...SEND');

            //Bloqueamos la pasta en el wallet
            $wallet->setAvailable($wallet->getAvailable() - $amount);
            $em->flush();

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
                $wallet->setAvailable($wallet->getAvailable() + $amount);
                //descontamos del counter
                $em->flush();
                $dm->flush();

                $this->container->get('notificator')->notificate($transaction);
                throw new HttpException($e->getCode(), $e->getMessage());
            }
            $txid = $payment_info['txid'];
            $payment_info['image_receiver'] = $destination->getCompanyImage();
            $payment_info['name_receiver'] = $destination->getName();
            $logger->info('Incomig transaction...PAYMENT STATUS: '.$payment_info['status']);

            $transaction->setPayOutInfo($payment_info);
            $dm->flush();

            //pay fees and dealer always and set new balance
            if( $payment_info['status'] == 'sent' || $payment_info['status'] == 'sending'){
                if($payment_info['status'] == 'sent') $transaction->setStatus(Transaction::$STATUS_SUCCESS);
                else $transaction->setStatus('sending');

                //restar al grupo el amount
                $wallet->setBalance($wallet->getBalance() - $amount);

                //insert new line in the balance for this group
                $this->get('net.telepay.commons.balance_manipulator')->addBalance($group, -$amount, $transaction, "incoming2 contr 1");

                $dm->flush();
                $em->flush();

                $params = array(
                    'amount' => $amount,
                    'concept' => $concept,
                    'address' => $address,
                    'txid' => $txid,
                    'sender' => $group->getId()
                );
                $this->createTransaction($params, $version_number, 'in', $method_cname, $destination->getKycManager()->getId(), $destination, $ip);
            }
            else{
                $transaction->setStatus($payment_info['status']);
                //desbloqueamos la pasta del wallet
                $wallet->setAvailable($wallet->getAvailable() + $amount);
                $em->flush();
                $dm->flush();
            }
        }
        else{     //CASH - IN
            $logger->info('Incomig transaction...IN');
            $em->flush();
            $transaction->setUpdated(new \DateTime());
            $dm->flush();
        }

        $this->container->get('notificator')->notificate($transaction);
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

        $dealer = $this->container->get('net.telepay.commons.fee_deal');

//        $method_list = $group->getMethodsList();
//
//        if (!in_array($method_cname.'-'.$type, $method_list)) {
//            throw $this->createAccessDeniedException();
//        }

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
                    //discount available, only amount because the fees are created in createFees
                    $current_wallet->setAvailable($current_wallet->getAvailable() - $amount);
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
                        $current_wallet->setAvailable($current_wallet->getAvailable() + $amount );

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
                    $balancer->addBalance($group, -$amount, $transaction, "incoming2 contr 2");

                    $current_wallet->setBalance($current_wallet->getBalance() - $amount );
                    $em->persist($current_wallet);
                    $em->flush();

                    $logger->info('Update transaction -> dealer');
                    $dealer = $this->container->get('net.telepay.commons.fee_deal');
                    try{
                        $dealer->createFees2($transaction, $current_wallet);
                    }catch (HttpException $e){
                        throw $e;
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


                    $logger->info('Update transaction -> dealer');
                    try{
                        $dealer->createFees2($transaction, $current_wallet);
                    }catch (HttpException $e){
                        throw $e;
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
                        throw new HttpException(405, 'Method not implemented');
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
                        $balancer->addBalance($group, $amount, $transaction, "incoming2 contr 3");
                        $logger->info('Update transaction -> addBalance');

                        $em->persist($current_wallet);
                        $em->flush();

                        $transaction = $this->get('notificator')->notificate($transaction);

                        //return fees
                        $logger->info('Update transaction -> returnFees');
                        try{
                            $dealer->returnFees($transaction, $current_wallet);
                        }catch (HttpException $e){
                            throw $e;
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

//        $method_list = $group->getMethodsList();
//
//        if (!in_array($method_cname.'-'.$type, $method_list)) {
//            throw $this->createAccessDeniedException();
//        }



        $mongo = $this->get('doctrine_mongodb')->getManager();

        $transaction = $mongo->getRepository('TelepayFinancialApiBundle:Transaction')->findOneBy(array(
            'id'        => $id,
            'method'   =>  $method_cname,
            'group'      =>  $group->getId(),
            'type'      =>  $type
        ));

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        $this->get('net.telepay.commons.permissions_checker')->checkMethodPermissions($transaction);

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
                        $balancer->addBalance($group, $transaction->getAmount(), $transaction, "incoming2 contr 4");
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

        /*
        $method_list = $user->getMethodsList();

        if (!in_array($method_cname.'-'.$type, $method_list)) {
            throw $this->createAccessDeniedException();
        }
        */

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

            $transactions = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->findTransactions($group, $start_time, $finish_time, $search, $order, $dir);
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
                $balancer->addBalance($user, $transaction->getAmount(), $transaction, "incoming2 contr 5");
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
            $group_commission->setFixed($method->getDefaultFixedFee());
            $group_commission->setVariable($method->getDefaultVariableFee());
            $em->persist($group_commission);
            $em->flush();
        }

        return $group_commission;
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
            $token = $this->get('security.context')->getToken();
            if($token instanceof SignatureToken) return $user->getActiveGroup();
            $accessToken = $tokenManager->findTokenByToken($token->getToken());

            $commerce_client = $this->container->getParameter('commerce_client_id');
            $android_pos_client = $this->container->getParameter('android_pos_client_id');
            $fairpay_android_pos_client = $this->container->getParameter('fairpay_android_pos_client_id');

            $client = $accessToken->getClient();
            if($commerce_client == $client->getId() || $android_pos_client == $client->getId() || $fairpay_android_pos_client == $client->getId()){
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