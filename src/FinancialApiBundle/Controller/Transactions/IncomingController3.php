<?php

namespace App\FinancialApiBundle\Controller\Transactions;

use App\FinancialApiBundle\Controller\Management\Admin\UsersController;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\PaymentOrder;
use App\FinancialApiBundle\Entity\SmsTemplates;
use App\FinancialApiBundle\Entity\Tier;
use App\FinancialApiBundle\Entity\UsersSmsLogs;
use App\FinancialApiBundle\Exception\AppException;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use PhpOption\None;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use App\FinancialApiBundle\DependencyInjection\App\Commons\LimitManipulator;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\CreditCard;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\ServiceFee;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Controller\Google2FA;
use App\FinancialApiBundle\Controller\SecurityTrait;

class IncomingController3 extends RestApiController{

    use SecurityTrait;

    /**
     * @Rest\View
     * @param Request $request
     * @param $version_number
     * @param $type
     * @param $method_cname
     * @return string|Response
     */
    public function make(Request $request, $type, $method_cname){
        $params = $request->request->all();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_WORKER'))
            throw new HttpException(403, 'You don\'t have the necessary permissions');
        if($request->request->has('company_id')){
            $group = $this->getDoctrine()->getManager()
                ->getRepository('FinancialApiBundle:Group')
                ->find($request->request->get('company_id'));
            if(!$group) throw new HttpException(404, 'Company not found');
        }else{
            $group = $this->_getCurrentCompany($user);
        }
        if($method_cname == 'lemonway' and $type == 'in'){
            $params["commerce_id"] = $this->setExchanger($group->getId());
        }
        //check if this user has this company
        $this->_checkPermissions($user, $group);

        return $this->createTransaction($params, 1, $type, $method_cname, $user->getId(), $group, $request->getClientIp());
    }


    public function createTransaction($data, $version_number, $type, $method_cname, $user_id, $group, $ip, $order = null){

        //check culture payment
        $this->checkCultureCampaignConstraint($data, $group, $type, $method_cname);

        $logger = $this->get('transaction.logger');
        $group_id = $group->getId();
        $logger->info('(' . $group_id . ')(T) INIT');
        $logger->info('(' . $group_id . ') Incomig transaction...Method-> '.$method_cname.' Direction -> '.$type);
        $method = $this->get('net.app.'.$type.'.'.$method_cname.'.v'.$version_number);

        /** @var DocumentManager $dm */
        $dm = $this->get('doctrine_mongodb')->getManager();

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();

        // Starting RDB transaction to avoid tx duplicates
        $em->getConnection()->beginTransaction();

        $this->ckeckKYC($type, $group, $data, $dm);

        $user = $em->getRepository('FinancialApiBundle:User')->find($user_id);
        $logger->info('(' . $user_id . ')(T) FIND USER');

        //obtain wallet and check founds for cash_out services for this group

        $logger->info("getting account wallet for {$group->getId()}, currency {$method->getCurrency()}");
        /** @var UserWallet $wallet */
        $wallet = $group->getWallet($method->getCurrency());
        if(!$wallet)
            throw new \LogicException(
                "Error: wallet for account {$group->getId()} and currency {$method->getCurrency()} doesn't exist"
            );

        if(array_key_exists('amount', $data) && $data['amount']!='' && intval($data['amount'])>0){
            $amount = $data['amount'];
        }
        else{
            throw new HttpException(400, 'Param amount not found or incorrect');
        }
        $logger->info('(' . $group_id . ')(T) CHECK AMOUNT');
        $orderRepo = $em->getRepository(PaymentOrder::class);
        if($type === 'out'){
            if($wallet->getAvailable() < $amount) {
                throw new HttpException(400, 'Not funds enough');
            }

            if($amount < $method->getMinimumAmount()){
                throw new HttpException(400, 'Amount under minimum');
            }
            $order = $orderRepo->findOneBy(
                ['payment_address' => $data['address']]
            );
            if ($order and $order->getStatus() == PaymentOrder::STATUS_FAILED){
                throw new HttpException(400, 'Failed payment transaction');
            };
            if(array_key_exists('pin', $data) && $data['pin']!='' && intval($data['pin'])>-1){
                $pin = $data['pin'];
                if($user->getPIN()!==$pin){
                    if ($order) {
                        $order->incrementRetries();
                        if ($order->getRetries() > 2) {
                            $order->setStatus(PaymentOrder::STATUS_FAILED);
                        }
                        $em->persist($order);
                        $em->flush();
                        $em->getConnection()->commit();
                    }
                    $user->setPinFailures($user->getPinFailures() + 1);
                    $em->persist($user);
                    $em->flush();

                    $error = $this->checkPinFailures($em, $user);
                    if(isset($error)){
                        return $error;
                    }

                    throw new HttpException(400, 'Incorrect Pin');
                }

            }
            else{
                throw new HttpException(400, 'Param pin not found or incorrect');

            }
            if($user->getPinFailures() > 0){
                $user->setPinFailures(0);
                $em->persist($user);
                $em->flush();
            }

        }

        //check bonissim payment
        $extra_data = $this->checkCampaignConstraint($data, $group, $type, $method_cname);

        $logger->info('(' . $group_id . ')(T) CHECK PIN');

        $transaction = Transaction::createFromRequestIP($ip);
        $transaction->setService($method_cname);
        $transaction->setMethod($method_cname);
        $transaction->setUser($user_id);
        $transaction->setGroup($group->getId());
        $transaction->setVersion(3);
        $transaction->setType($type);
        $transaction->setInternal(false);
        $dm->persist($transaction);
        $logger->info('(' . $group_id . ')(T) CREATE TRANSACTION');

        if(array_key_exists('concept', $data) && $data['concept']!=''){
            $concept = $data['concept'];
        }else{
            throw new HttpException(400, 'Param concept not found');
        }

        if(array_key_exists('url_notification', $data)) $url_notification = $data['url_notification'];
        else $url_notification = '';
        $logger->info('(' . $group_id . ') Incomig transaction...getPaymentInfo for company '.$group->getId());
        $logger->info('(' . $group_id . ')(T) URL AND CONCEPT');

        if($type === 'in'){
            $dataIn = array(
                'amount'    =>  $amount,
                'concept'   =>  $concept,
                'url_notification'  =>  $url_notification
            );
            if($method_cname === 'lemonway'){
                $logger->info('(' . $group_id . ')(T) LEMON');
                if(isset($data['commerce_id'])){
                    $commerce = $em->getRepository('FinancialApiBundle:Group')->findOneBy(array(
                        'id' => $data['commerce_id'],
                        'type' => 'COMPANY'
                    ));
                    if(!$commerce){
                        $logger->info('(' . $data['commerce_id'] . ')(T) LEMON_COMMERCE');
                        throw new HttpException(405,'Commerce selected is not available');
                    }
                }
                else{
                    throw new HttpException(400, 'Param commerce_id not found');
                }
                if(isset($data['card_id'])){
                    $logger->info('(' . $group_id . ')(T) WITH CARD');
                    if(array_key_exists('pin', $data) && $data['pin']!='' && intval($data['pin'])>=0){
                        $pin = $data['pin'];
                        if($user->getPIN()!=$pin){
                            throw new HttpException(400, 'Incorrect Pin');
                        }
                    }
                    else{
                        throw new HttpException(400, 'Param pin not found or incorrect');
                    }
                    if(array_key_exists('creditCardPertainsBeneficiary', $data) && $data['creditCardPertainsBeneficiary'] == false){
                        $credit_card = $em->getRepository('FinancialApiBundle:CreditCard')->findOneBy(array(
                            'id' => $data['card_id'],
                            'deleted'=>false
                        ));
                    }else {
                        $credit_card = $em->getRepository('FinancialApiBundle:CreditCard')->findOneBy(array(
                            'id' => $data['card_id'],
                            'company' => $group->getId(),
                            'deleted' => false,
                            'user' => $user_id
                        ));
                    }


                    if(!$credit_card){
                        throw new HttpException(405,'Credit card selected is not available');
                    }
                    $data['card_id'] = $credit_card->getExternalId();
                }
                if(isset($data['save_card']) && $data['save_card']=='1'){
                    $data['save_card']=true;
                }
                else{
                    $data['save_card']=false;
                }
                $logger->info('(' . $group_id . ')(T) SAVE CARD');
                $payment_info = $method->getPayInInfoWithCommerce($data);
                $logger->info('(' . $group_id . ')(T) GET LEMON INFO');
                $transaction->setInternal(true);
                $transaction->setStatus($payment_info['status']);
                if($transaction->getStatus() == Transaction::$STATUS_RECEIVED){
                    $logger->info('(' . $group_id . ')(T) LEMON RECEIVED');
                    $sentInfo = array(
                        'to' => $commerce->getCIF(),
                        'amount' => number_format($transaction->getAmount()/100, 2)
                    );
                    $logger->info('(' . $commerce->getCIF() . ') euros balance sent');
                    $logger->info('(' . $group_id . ')(T) LEMON SENT');
                    $method->send($sentInfo);
                }
            }
            else{
                $logger->info('(' . $group_id . ')(T) GET PAY IN INFO');
                if(!isset($data['txid'])){
                    $payment_info = $method->getPayInInfo($group->getId(), $amount);
                }
                else{
                    $payment_info = $method->getPayInInfoWithData($data);
                }
            }
            $logger->info('(' . $group_id . ')(T) CHECK CONCEPT AND EXPIRED');
            $payment_info['concept'] = $concept;
            if(isset($data['expires_in']) && $data['expires_in'] > 99){
                $payment_info['expires_in'] = $data['expires_in'];
            }
            if(isset($data['sender']) && $data['sender']!='') {
                $logger->info('(' . $group_id . ')(T) SENDER INFO');
                $sender_id = $data['sender'];
                if($sender_id == '0'){
                    $payment_info['image_sender'] = "";
                    $payment_info['name_sender'] = "Treasure account";
                    $payment_info['txid'] = '0000000000000000000000000000000000000000000000000000000000000000'; # 64 zeros
                }
                else {
                    $sender = $em->getRepository('FinancialApiBundle:Group')->findOneBy(array(
                        'id' => $sender_id
                    ));
                    $payment_info['image_sender'] = $sender->getCompanyImage();
                    $payment_info['name_sender'] = $sender->getName();
                }
            }
            $logger->info('(' . $group_id . ')(T) SET PAY IN INFO');
            $transaction->setPayInInfo($payment_info);
        }
        else{
            $logger->info('(' . $group_id . ')(T) GET PAY OUT INFO');
            $data['orig_address'] = $group->getRecAddress();
            $payment_info = $method->getPayOutInfoData($data);
            $logger->info('(' . $group_id . ')(T) SAVE PAY OUT INFO');
            $transaction->setPayOutInfo($payment_info);
            $dataIn = array(
                'amount'    =>  $amount,
                'concept'   =>  $concept,
                'url_notification'  =>  $url_notification
            );
        }
        $logger->info('(' . $group_id . ')(T) SET DATA IN');
        $transaction->setDataIn($dataIn);

        $amount = $dataIn['amount'];
        $transaction->setAmount($amount);
        $fixed_fee = 0;
        $variable_fee = 0;
        //add fee to transaction
        $transaction->setVariableFee($variable_fee);
        $transaction->setFixedFee($fixed_fee);

        /** @var LimitManipulator $limitManipulator */
        $limitManipulator = $this->get('net.app.commons.limit_manipulator');

        $logger->info('(' . $group_id . ')(T) INIT LIMITS');
        $limitManipulator->checkLimits($group, $method, $amount);
        $logger->info('(' . $group_id . ')(T) END LIMITS');

        $transaction->setCurrency($method->getCurrency());
        $transaction->setScale($wallet->getScale());

        if($type === 'out'){
            $transaction->setTotal(-$amount);
            $logger->info('(' . $group_id . ')(T) OUT');
            $logger->info('(' . $group_id . ') Incomig transaction...OUT Available = ' . $wallet->getAvailable() .  " TOTAL: " . $amount);
            $address = $payment_info['address'];
            $destination = $em->getRepository(Group::class)
                ->findOneBy(['rec_address' => $payment_info['address']]);
            $logger->info('(' . $group_id . ')(T) CHECK ADDRESS');

            if(!$destination){
                // checking if the address belongs to an order
                $orderRepo = $em->getRepository(PaymentOrder::class);

                /** @var PaymentOrder $order */
                $order = $orderRepo->findOneBy(
                    ['payment_address' => $payment_info['address'], 'status' => PaymentOrder::STATUS_EXPIRED]
                );
                if($order) {
                    throw new AppException(400, "Payment order has expired");
                }

                /** @var PaymentOrder $order */
                $order = $orderRepo->findOneBy(
                    ['payment_address' => $payment_info['address'], 'status' => PaymentOrder::STATUS_IN_PROGRESS]
                );
                if($order){
                    if($payment_info['amount'] != $order->getAmount()) {
                        throw new AppException(
                            400,
                            "Amount sent and order mismatch, (sent: {$payment_info['amount']}, order: {$order->getAmount()})"
                        );
                    }
                    $destination = $order->getPos()->getAccount();
                }
                else {
                    throw new HttpException(400,'Destination address does not exists');
                }
            }

            if($destination->getRecAddress() == $group->getRecAddress()){
                throw new HttpException(400,'Error, cannot send money to the same origin address');
            }

            if($destination->getRecAddress() == "temp" || $group->getRecAddress() == "temp"){

                $notificator = $this->container->get('com.qbitartifacts.rec.commons.notificator');
                $notificator->send('#EERROR TEMP ADDRESS' . $destination->getId() . " or " . $group->getId());

                throw new HttpException(404, 'Destination address does not exists');
            }
            $logger->info('(' . $group_id . ')(T) DEFINE PAYMENT DATA');
            $payment_info['orig_address'] = $group->getRecAddress();
            $payment_info['orig_nif'] = $user->getDNI();
            $payment_info['orig_group_nif'] = $group->getCif();
            $payment_info['orig_group_public'] = $group->getIsPublicProfile();
            $payment_info['orig_key'] = $group->getKeyChain();
            $payment_info['dest_address'] = $destination->getRecAddress();
            $payment_info['dest_group_nif'] = $destination->getCif();
            $payment_info['dest_group_public'] = $destination->getIsPublicProfile();
            $payment_info['dest_key'] = $destination->getKeyChain();

            $logger->info('(' . $group_id . ') Incomig transaction...SEND');

            $logger->info('(' . $group_id . ')(T) BLOCK MONEY');
            //Bloqueamos la pasta en el wallet
            $wallet->setAvailable($wallet->getAvailable() - $amount);
            $em->flush();
            try {
                $logger->info('(' . $group_id . ')(T) INIT SEND');
                $payment_info = $method->send($payment_info);
                $logger->info('(' . $group_id . ')(T) END SEND');
            }catch (Exception $e){

                $notificator = $this->container->get('com.qbitartifacts.rec.commons.notificator');
                $notificator->send('#ERROR IncomingController'. $method . ' ' . $group->getId());

                $logger->info('(' . $group_id . ')(T) SEND ERROR');
                if(isset($payment_info['inputs'])) {
                    $logger->info('REC_INFO_ERROR Inputs:'.$payment_info['inputs']);
                    $logger->info('REC_INFO_ERROR Outputs:'.$payment_info['outputs']);
                    $logger->info('REC_INFO_ERROR met_len:'.$payment_info['metadata_len']);
                    $logger->info('REC_INFO_ERROR in_total:'.$payment_info['input_total']);
                    if(isset($payment_info['message'])){
                        $logger->info('REC_INFO_ERROR response:'.$payment_info['message']);
                        $logger->info('REC_INFO len_message:'.$payment_info['len_message']);
                        $logger->info('REC_INFO hex_len:'.$payment_info['len']);
                    }
                }

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

                $logger->info('(' . $group_id . ')(T) INIT ERROR NOTIFICATION');
                $this->container->get('messenger')->notificate($transaction);
                $logger->info('(' . $group_id . ')(T) END ERROR NOTIFICATION');
                $logger->info('(' . $group_id . ')(T) END ALL');
                throw new HttpException($e->getCode(), $e->getMessage());
            }
            if(isset($payment_info['inputs'])) {
                if($payment_info['status']=='failed'){

                    $notificator = $this->container->get('com.qbitartifacts.rec.commons.notificator');
                    $notificator->send('#ERROR IncomingController rec:' . $group->getId());

                    $logger->info('REC_INFO_ERROR');
                }
                $logger->info('REC_INFO Inputs:'.$payment_info['inputs']);
                $logger->info('REC_INFO Outputs:'.$payment_info['outputs']);
                $logger->info('REC_INFO met_len:'.$payment_info['metadata_len']);
                $logger->info('REC_INFO in_total:'.$payment_info['input_total']);
                if(isset($payment_info['message'])){
                    $logger->info('REC_INFO response:'.$payment_info['message']);
                    $logger->info('REC_INFO len_message:'.$payment_info['len_message']);
                    $logger->info('REC_INFO hex_len:'.$payment_info['len']);
                }
            }
            $txid = $payment_info['txid'];
            $payment_info['image_receiver'] = $destination->getCompanyImage();
            $payment_info['name_receiver'] = $destination->getName();
            $payment_info['concept'] = $data['concept'];
            $logger->info('(' . $group_id . ')(T) STATUS => ' . $payment_info['status']);

            $transaction->setPayOutInfo($payment_info);
            $dm->flush();

            if( $payment_info['status'] == 'sent' || $payment_info['status'] == 'sending'){
                $logger->info('(' . $group_id . ')(T) SENT OR SENDING');
                if($payment_info['status'] == 'sent') $transaction->setStatus(Transaction::$STATUS_SUCCESS);
                else $transaction->setStatus('sending');

                //restar al grupo el amount
                $wallet->setBalance($wallet->getBalance() - $amount);

                //insert new line in the balance for this group
                $this->get('net.app.commons.balance_manipulator')->addBalance($group, -$amount, $transaction, "incoming2 contr 1");

                $dm->flush();
                $em->flush();
                $logger->info('(' . $group_id . ')(T) SAVE ALL');

                $em->getConnection()->commit();

                $params = array(
                    'amount' => $amount,
                    'concept' => $concept,
                    'address' => $address,
                    'txid' => $txid,
                    'sender' => $group->getId()
                );
                if(isset($data['internal_tx']) && $data['internal_tx']=='1') {
                    $params['internal_tx']='1';
                    $params['destionation_id']=$data['destionation_id'];
                }

                //TODO remove this call and create the in transaction directlly
                $logger->info('(' . $group_id . ')(T) Incomig transaction... Create New');
                $txFlowHandler = $this->get('net.app.commons.transaction_flow_handler');
                $txFlowHandler->receiveRecsFromOutTx($destination, $transaction);
                //$this->createTransaction($params, $version_number, 'in', $method_cname, $destination->getKycManager()->getId(), $destination, '127.0.0.1', $order);
                $logger->info('(' . $group_id . ')(T) Incomig transaction... New created');
            }
            else{
                $transaction->setStatus($payment_info['status']);
                //desbloqueamos la pasta del wallet
                $wallet->setAvailable($wallet->getAvailable() + $amount);
                $em->flush();
                $dm->flush();
                $logger->info('(' . $group_id . ')(T) SAVE DATA');

                $em->getConnection()->commit();
            }
        }
        else{
            $transaction->setTotal($amount);
            //Checking if there is an out transaction for this in tx
            //CASH - IN
            $logger->info('(' . $group_id . ') Incomig transaction...IN');
            $em->flush();
            $transaction->setUpdated(new \DateTime());
            $dm->flush();

            $em->getConnection()->commit();
        }

        $logger->info('(' . $group_id . ')(T) INIT NOTIFICATION');
        $this->container->get('messenger')->notificate($transaction);
        $logger->info('(' . $group_id . ')(T) END NOTIFICATION');
        if($transaction == false) throw new HttpException(500, "oOps, some error has occurred within the call");

        //TODO extract all of this in a method or better in a service
        //if bonificable generate inetrnal tx from a to c throug b
        //check if tx has to be bonified
        $txBonusHandler = $this->get('net.app.commons.bonus_handler');
        $txBonusHandler->bonificateTx($transaction);

        $logger->info('(' . $group_id . ')(T) Incomig transaction... return http format');
        $logger->info('(' . $group_id . ')(T) FINAL');


        $response = $this->methodTransaction(201, $transaction, "Done");
        $content = json_decode($response->getContent(), true);
        $response->setContent(json_encode($content));
        return $response;

    }

    private function _checkPermissions(User $user, Group $group){

        if(!$user->hasGroup($group->getName())) throw new HttpException(403, 'You(' . $user->getId() . ') do not have the necessary permissions in this company(' . $group->getId() . ')');

        //Check permissions for this user in this company
        $userRoles = $this->getDoctrine()->getRepository('FinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $user->getId(),
            'group' =>  $group->getId()
        ));

        if(!$userRoles->hasRole('ROLE_WORKER') && !$userRoles->hasRole('ROLE_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions in this company.');


    }

    private function _getCurrentCompany(User $user){
        $group = $user->getActiveGroup();
        return $group;
    }

    /**
     * @param $type
     * @param $group
     * @param $data
     * @param DocumentManager $dm
     */
    private function ckeckKYC($type, $group, $data, DocumentManager $dm): void
    {
        if ($type == 'out') {
            $tier = $group->getLevel();
            if (!isset($tier)) { // tier not setted
                throw new HttpException(400, 'KYC max_out limit has been reached');
            } elseif (!is_null($tier->getMaxOut())) {
                $out_amount = $data['amount'];
                if ($out_amount / 1e8 > $tier->getMaxOut()) { // 1e8 satoshi = 1REC
                    throw new HttpException(400, 'KYC max_out limit has been reached');
                }
                $out_transaccions = $dm->getRepository(Transaction::class)->findBy(['group' => $group->getId(), 'type' => 'out']);
                foreach ($out_transaccions as $out_transaccion) {
                    $out_amount += $out_transaccion->getAmount();
                }
                if ($out_amount / 1e8 > $tier->getMaxOut()) {
                    throw new HttpException(400, 'KYC max_out limit has been reached');
                }
            }
        }
    }


    /**
     * @param EntityManagerInterface $em
     * @param object|null $user
     */
    private function checkPinFailures(EntityManagerInterface $em, ?object $user)
    {
        $max_attempts = $em->getRepository('FinancialApiBundle:UserSecurityConfig')
            ->findOneBy(['type' => 'pin_failures'])->getMaxAttempts();

        if ($user->getPinFailures() >= $max_attempts) {
            $user->lockUser();
            
            $sms_template = $em->getRepository(SmsTemplates::class)->findOneBy(['type' => 'pin_max_failures']);
            if (!$sms_template) {
                throw new HttpException(404, 'SMS template not found');
            }

            $sms_text = $sms_template->getBody();
            $code = strval(random_int(100000, 999999));
            $user->setLastSmscode($code);
            $em->persist($user);

            $sms_text = str_replace("%SMS_CODE%", $code, $sms_text);
            UsersController::sendSMSv4($user->getPrefix(), $user->getPhone(), $sms_text, $this->container);

            $user_sms_log = new UsersSmsLogs();
            $user_sms_log->setUserId($user->getId());
            $user_sms_log->setType('pin_max_failures');
            $user_sms_log->setSecurityCode($code);
            $em->persist($user_sms_log);
            $em->flush();



            $headers = array(
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store',
                'Pragma' => 'no-cache',
            );

            $token = array(
                "error" => "user_locked",
                "error_description" => "Maximum pin attempts exceeded"
            );
            return new Response(json_encode($token), 403, $headers);
        }
    }


    /**
     * @param $receiver_id
     * @return int
     * @throws \Exception
     */
    private function setExchanger($receiver_id): int
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        $kyc2_id = $em->getRepository(Tier::class)->findOneBy(array('code' => 'KYC2'));

        $exchangers = $em->getRepository(Group::class)->findBy([
            'type' => 'COMPANY',
            'level' => $kyc2_id->getId(),
            'active' => 1]);

        if (count($exchangers) == 0) {
            throw new HttpException(403, '"No qualified exchanger found.');
        }

        $culture_campaign = $em->getRepository(Campaign::class)->findOneBy(array('name' => Campaign::CULTURE_CAMPAIGN_NAME));
        $receiver_account = $em->getRepository(Group::class)->find($receiver_id);

        if (in_array($culture_campaign, $receiver_account->getCampaigns()->getValues())) {
            $valid_exchangers = [];
            foreach ($exchangers as $exchanger) {
                if (in_array($culture_campaign, $exchanger->getCampaigns()->getValues())) {
                    array_push($valid_exchangers, $exchanger);
                }
            }
            if (count($valid_exchangers) == 0) {
                throw new HttpException(403, 'Exchanger in campaign not found.');
            }
            $exchanger_id = $valid_exchangers[random_int(0, count($valid_exchangers) - 1)]->getId();
        } else {
            $exchanger_id = $exchangers[random_int(0, count($exchangers) - 1)]->getId();
        }
        return  $exchanger_id;
    }

    /**
     * @param $params
     * @param object|null $group
     * @param $type
     * @param $method_cname
     */
    private function checkCampaignConstraint($params, ?object $group, $type, $method_cname)
    {
        //TODO refactor and move to service
        $extra_data = [];
        $satoshi_decimals = 1e8;
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => Campaign::BONISSIM_CAMPAIGN_NAME]);

        $now = new DateTime('NOW');
        $is_campaign_active = isset($campaign) && $campaign->getInitDate() < $now && $now < $campaign->getEndDate();
        if($is_campaign_active && $campaign->getCampaignAccount() != $group->getId() && $type == "out" &&
            $method_cname == "rec" && $group->getType() == Group::ACCOUNT_TYPE_PRIVATE){

            $orderRepo = $em->getRepository(PaymentOrder::class);
            $accountRepo = $em->getRepository(Group::class);
            /** @var PaymentOrder $order */
            $order = $orderRepo->findOneBy([
                'payment_address' => $params['address'],
                'status' => PaymentOrder::STATUS_IN_PROGRESS
            ]);

            // check if it is a POS payment or not
            /** @var Group $destination */
            $destination = $order? $order->getPos()->getAccount(): $accountRepo->findOneBy(['rec_address' => $params['address']]);

            $sender_in_campaign = $accountRepo->find($group->getId())->getCampaigns()->contains($campaign);
            $reciver_in_campaign = $accountRepo->find($destination->getId())->getCampaigns()->contains($campaign);

            $user_id = $group->getKycManager()->getId();

            // sender and reciver accounts not in campaign
            if (!$sender_in_campaign && !$reciver_in_campaign) {

                $user_private_accounts = $accountRepo->findBy(['kyc_manager' => $user_id, 'type' => Group::ACCOUNT_TYPE_PRIVATE]);
                $user_balance = 0;
                foreach ($user_private_accounts as $account) {
                    if ($account->getCampaigns()->contains($campaign)) {
                        $bonissim_account = $account;
                    } elseif(count($account->getCampaigns()) == 0) {
                        $user_balance = $user_balance + $account->getWallets()[0]->getBalance();
                    }
                }
                $user_balance = max($user_balance - $params['amount'], 0);
                if(isset($bonissim_account) && $bonissim_account->getRedeemableAmount() > $user_balance / $satoshi_decimals){ // user has bonissim account
                    $bonissim_account->setRedeemableAmount( $user_balance / $satoshi_decimals);
                    $em->persist($bonissim_account);
                    $em->flush();
                }

            }
            $destination_is_private = $destination->getType() == Group::ACCOUNT_TYPE_PRIVATE;
            $user_has_bouth_accounts = $group->getKycManager()->getId() != $destination->getKycManager()->getId();
            if ($sender_in_campaign){ //sender is bonissim
                if (!$reciver_in_campaign) { // reciver is not bonissim
                    if (!$user_has_bouth_accounts || !$destination_is_private) { // accounts are from different user or reciver campany
                        throw new AppException(Response::HTTP_BAD_REQUEST, "Receiver account not in Campaign");
                    }
                }
                if ($destination_is_private && !$user_has_bouth_accounts) { // reciver is bonissim PRIVATE and different user
                    throw new AppException(Response::HTTP_BAD_REQUEST, "This account cannot receive payments");
                }

            }
            //$extra_data = $this->bonificate_ltab($accountRepo, $group, $campaign, $destination, $user_id, $user_has_bouth_accounts, $params);
        }
        return $extra_data;
    }

    /**
     * @param $params
     * @param object|null $group
     * @param $type
     * @param $method_cname
     */
    private function checkCultureCampaignConstraint($params, ?object $group, $type, $method_cname)
    {
        //TODO move to service
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => Campaign::CULTURE_CAMPAIGN_NAME]);

        //out trx -> group is sender
        //in trx -> group is receiver
        if($method_cname == "rec"){
            $id_group_root = $this->container->getParameter('id_group_root');
            if ($type == "out" and $group->getId() != $id_group_root) {
                $receiver = $em->getRepository(Group::class)->findOneBy(['rec_address' => $params['address']]);
                $sender_in_campaign = in_array($group, $campaign->getAccounts()->toArray());
                $receiver_in_campaign = in_array($receiver, $campaign->getAccounts()->toArray());
                if($sender_in_campaign) {
                    if (!$receiver_in_campaign) {
                        throw new HttpException(Response::HTTP_BAD_REQUEST, "Receiver account not in Campaign");
                    }
                }
                if($receiver_in_campaign) {
                    if(!$sender_in_campaign) {
                        throw new HttpException(Response::HTTP_BAD_REQUEST, "Sender account not in Campaign");
                    }
                }
            }
        }
    }

}
