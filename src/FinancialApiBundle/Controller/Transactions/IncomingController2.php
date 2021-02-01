<?php

namespace App\FinancialApiBundle\Controller\Transactions;

use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\PaymentOrder;
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

class IncomingController2 extends RestApiController{

    use SecurityTrait;

    /**
     * @Rest\View
     * @param Request $request
     * @param $version_number
     * @param $type
     * @param $method_cname
     * @return string|Response
     */
    public function make(Request $request, $version_number, $type, $method_cname){
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
        //check if this user has this company
        $this->_checkPermissions($user, $group);
        $params = $request->request->all();
        return $this->createTransaction($params, $version_number, $type, $method_cname, $user->getId(), $group, $request->getClientIp());
    }

    public function checkReceiverData(Request $request){
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        if($request->query->has('address') && $request->query->get('address')!=''){
            $address = $request->query->get('address');
            if($address == 'temp'){
                throw new HttpException(400, 'Incorrect address');
            }
            $destination = $em->getRepository('FinancialApiBundle:Group')->findOneBy(array(
                'rec_address' => $address
            ));
            if(!$destination){
                /** @var PaymentOrder $order */
                $order = $em->getRepository(PaymentOrder::class)->findOneBy(
                    ['payment_address' => $address, 'status' => PaymentOrder::STATUS_IN_PROGRESS]
                );
                if($order) $destination = $order->getPos()->getAccount();
                else throw new HttpException(400, 'Incorrect address');
            }
            $data = array(
                $destination->getName(),
                $destination->getCompanyImage(),
                $destination->getType(),
                $this->secureOutput($destination->getCampaigns())
            );

            return $this->restV2(200,"ok", "Vendor information", $data);
        }
        throw new HttpException(400, 'Incorrect address');
    }

    public function checkSenderData(Request $request){
        $em = $this->getDoctrine()->getManager();
        if($request->query->has('card_id') && $request->query->get('card_id')!=''){
            $card_id = $request->query->get('card_id');
            $sender = $em->getRepository('FinancialApiBundle:NFCCard')->findOneBy(array(
                'id_card' => $card_id
            ));
            $customer = $sender->getUser();
            $data = array(
                $customer->getName(),
                $customer->getProfileImage()
            );
            return $this->restV2(201,"ok", "Sender information", $data);
        }
        throw new HttpException(400, 'Incorrect sending data');
    }


    public function createTransaction($data, $version_number, $type, $method_cname, $user_id, $group, $ip, $order = null){
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
        if($type == 'out'){
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
                if($user->getPIN()!=$pin){
                    if ($order) {
                        $order->incrementRetries();
                        if ($order->getRetries() > 2) {
                            $order->setStatus(PaymentOrder::STATUS_FAILED);
                        }
                        $em->persist($order);
                        $em->flush();
                        $em->getConnection()->commit();
                    }
                    throw new HttpException(400, 'Incorrect Pin');
                }

            }
            else{
                throw new HttpException(400, 'Param pin not found or incorrect');

            }
        }

        //check bonissim payment
        $this->checkCampaignConstraint($data, $group, $type, $method_cname);

        $logger->info('(' . $group_id . ')(T) CHECK PIN');

        $transaction = Transaction::createFromRequestIP($ip);
        $transaction->setService($method_cname);
        $transaction->setMethod($method_cname);

        if($user_id==-1){
            $id_group_root = $this->container->getParameter('id_group_root');
            $destination = $em->getRepository('FinancialApiBundle:Group')->find($id_group_root);
            $id_user_root = $destination->getKycManager()->getId();
            $transaction->setUser($id_user_root);
        }
        else{
            $transaction->setUser($user_id);
        }
        $transaction->setGroup($group->getId());
        $transaction->setVersion($version_number);
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

        if($type == 'in'){
            $dataIn = array(
                'amount'    =>  $amount,
                'concept'   =>  $concept,
                'url_notification'  =>  $url_notification
            );
            if($method_cname == 'lemonway'){
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
        $logger->info('(' . $group_id . ')(T) FEES');
        //$fee_handler = $this->container->get('net.app.commons.fee_manipulator');
        //$group_commission = $fee_handler->getMethodFees($group, $method);

        $amount = $dataIn['amount'];
        $transaction->setAmount($amount);

        //add commissions to check
        //$fixed_fee = $group_commission->getFixed();
        $fixed_fee = 0;
        //$variable_fee = round(($group_commission->getVariable()/100) * $amount, 0);
        $variable_fee = 0;
        $total_fee = $fixed_fee + $variable_fee;

        //add fee to transaction
        $transaction->setVariableFee($variable_fee);
        $transaction->setFixedFee($fixed_fee);
        $logger->info('(' . $group_id . ')(T) FEES SET');

        //check if is cash-out
        if($type == 'out'){
            //le cambiamos el signo para guardarla y marcarla como salida en el wallet
            $transaction->setTotal(-$amount);
            $total = $amount + $variable_fee + $fixed_fee;
        }else{
            $total = $amount - $variable_fee - $fixed_fee;
            $transaction->setTotal($amount);
        }

        $logger->info('(' . $group_id . ')(T) LIMITS');

        //check limits with 30 days success/received/created transactions
        //get limit manipulator

        /** @var LimitManipulator $limitManipulator */
        $limitManipulator = $this->get('net.app.commons.limit_manipulator');

        $logger->info('(' . $group_id . ')(T) INIT LIMITS');
        $limitManipulator->checkLimits($group, $method, $amount);
        $logger->info('(' . $group_id . ')(T) END LIMITS');

        $transaction->setCurrency($method->getCurrency());
        $transaction->setScale($wallet->getScale());

        if(isset($data['internal_tx']) && $data['internal_tx']=='1') {
            $transaction->setInternal(true);
        }
        if($type == 'out'){
            $logger->info('(' . $group_id . ')(T) OUT');
            if(isset($data['internal_out']) && $data['internal_out']=='1') {
                $transaction->setInternal(true);
            }
            $logger->info('(' . $group_id . ') Incomig transaction...OUT Available = ' . $wallet->getAvailable() .  " TOTAL: " . $total);
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
                $logger->info('(' . $group_id . ')(T) Incomig transaction... Create New');
                $this->createTransaction($params, $version_number, 'in', $method_cname, $destination->getKycManager()->getId(), $destination, '127.0.0.1', $order);
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

            //Checking if there is an out transaction for this in tx


            $logger->info('(' . $group_id . ')(T) IS INTERNAL?');
            if(isset($data['internal_in']) && $data['internal_in']=='1') {
                $transaction->setInternal(true);
            }
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

        if($user_id == -1 || $ip == '127.0.0.1'){ // this is executed in the recursive call
            $logger->info('(' . $group_id . ')(T) Incomig transaction... return string');
            $logger->info('(' . $group_id . ')(T) FINAL');
            $logger->info('(' . $group_id . ')(T) TXID: ' . $transaction->getId());

            if(isset($order) && $order instanceof PaymentOrder){
                $order->setPaymentTransaction($transaction);
                $order->setStatus(PaymentOrder::STATUS_DONE);
                $em = $this->getDoctrine()->getManager();
                $em->persist($order);
                $em->flush();
            }

            return 'Transaction generated: ' . $transaction->getStatus() . ", ID: " . $transaction->getId();
        }
        else { // this is executed in the normal call (non recursive)
            $logger->info('(' . $group_id . ')(T) Incomig transaction... return http format');
            $logger->info('(' . $group_id . ')(T) FINAL');

            return $this->methodTransaction(201, $transaction, "Done");
        }
    }



    public function adminThirdTransaction(Request $request, $method_cname){
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if (!$user->hasRole('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'Permission error');
        if (!$user->getTwoFactorAuthentication()) throw new HttpException(403, '2FA must be active');

        if($method_cname != 'rec'){
            throw new HttpException(400, 'Bad method');
        }
        $paramNames = array(
            'sender',
            'receiver',
            'sec_code',
            'concept',
            'amount'
        );
        $params = array();
        foreach ( $paramNames as $paramName){
            if($request->request->has($paramName)){
                $params[$paramName] = $request->request->get($paramName);
            }else{
                throw new HttpException(400,'Missing parameter '.$paramName);
            }
        }
        $code = $params['sec_code'];
        $Google2FA = new Google2FA();
        $twoFactorCode = $user->getTwoFactorCode();
        if (!$Google2FA->verify_key($twoFactorCode, $code)) {
            throw new HttpException(400,'The security code is incorrect.');
        }


        $em = $this->getDoctrine()->getManager();
        $group_sender = $em->getRepository('FinancialApiBundle:Group')->findOneBy(array('id'=>$params['sender'], 'active'=>true));
        if(!$group_sender){
            throw new HttpException(400,'Sender not found: ' . $params['sender']);
        }
        $group_receiver = $em->getRepository('FinancialApiBundle:Group')->findOneBy(array('id'=>$params['receiver'], 'active'=>true));
        if(!$group_receiver){
            throw new HttpException(400,'Receiver not found: ' . $params['receiver']);
        }

        $request = array();
        $request['concept'] = $params['concept'];
        $request['amount'] = $params['amount'];
        $request['pin'] = $user->getPin();
        $request['address'] = $group_receiver->getRecAddress();
        return $this->createTransaction($request, 1, 'out', $method_cname, $user->getId(), $group_sender, '127.0.0.2');
    }

    public function remoteDelegatedTransactionPlain($params, $ip = "127.0.0.1"){

        $em = $this->getDoctrine()->getManager();

        /** @var User $user */
        $user = $em->getRepository('FinancialApiBundle:User')
            ->findOneBy(['dni' => $params['dni']]);

        if(!$user){
            throw new HttpException(400,'User not found: ' . $params['dni']);
        }

        /** @var Group $account */
        $account = $em->getRepository('FinancialApiBundle:Group')
            ->findOneBy(['cif' => $params['dni'], 'active' => true, 'type' => 'PRIVATE']);

        if(!$account){
            throw new HttpException(400,'User is not a particular: ' . $params['dni']);
        }
        $account->setSubtype('BMINCOME');
        $em->persist($account);
        $em->flush();

        /** @var CreditCard $card */
        $card = $em->getRepository('FinancialApiBundle:CreditCard')->findOneBy(
            [
                'user' => $user->getId(),
                'deleted' => false,
                'company' => $account->getId()
            ]
        );
        if($card){
            $card->setDeleted(true);
            $em->persist($card);
            $em->flush();
        }

        /** @var Group $exchanger */
        $exchanger = $em->getRepository('FinancialApiBundle:Group')->findOneBy(
            [
                'cif' => $params['cif'],
                'type' => 'COMPANY'
            ]
        );
        if(!$exchanger){
            throw new HttpException(400,'Commerce not found: ' . $params['cif']);
        }

        $request = [];
        $request['concept'] = 'Internal exchange';
        $request['amount'] = $params['amount'];
        $request['commerce_id'] = $exchanger->getId();
        $request['save_card'] = 1;
        return $this->createTransaction($request, 1, 'in', "lemonway", $user->getId(), $account, $ip);
    }

    public function remoteDelegatedTransaction(Request $request, $method_cname){
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if (!$user->hasRole('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'Permission error');

        if($method_cname != 'lemonway'){
            throw new HttpException(400, 'Bad method');
        }
        $paramNames = array(
            'dni',
            'cif',
            'amount'
        );
        $params = array();
        foreach ( $paramNames as $paramName){
            if($request->request->has($paramName)){
                $params[$paramName] = $request->request->get($paramName);
            }else{
                throw new HttpException(400,'Missing parameter '.$paramName);
            }
        }

        return $this->remoteDelegatedTransactionPlain($params, '127.0.0.2');
    }


    /**
     * @Rest\View
     */
    public function update(Request $request, $version_number, $type, $method_cname, $id){
        $method = $this->get('net.app.'.$type.'.'.$method_cname.'.v'.$version_number);
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_WORKER')) throw new HttpException(403, 'You don\' have the necessary permissions');

        $logger = $this->get('transaction.logger');
        $logger->info('Update transaction');
        $message = 'NAN';

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $mongo = $this->get('doctrine_mongodb')->getManager();
        $dealer = $this->container->get('net.app.commons.fee_deal');

        if($user->getId()!=1){
            $group = $this->_getCurrentCompany($user);
            $this->_checkPermissions($user, $group);

            $transaction = $mongo->getRepository('FinancialApiBundle:Transaction')->findOneBy(array(
                'id'        =>  $id,
                'method'    =>  $method_cname,
                'group'      =>  $group->getId(),
                'type'      =>  $type
            ));
        }
        else{
            $transaction = $mongo->getRepository('FinancialApiBundle:Transaction')->findOneBy(array(
                'id'        =>  $id,
                'method'    =>  $method_cname,
                'type'      =>  $type
            ));
            $group = $transaction->getGroup();
        }
        if(!$transaction) throw new HttpException(404, 'Transaction not found');
        $data = $request->request->all();

        //retry=true y cancel=true aqui
        if( isset( $data['retry'] ) || isset ( $data ['cancel'] )){
            $logger->info('Update transaction -> retry or cancel');

            if($transaction->getType() != 'out') throw new HttpException(403, 'Forbidden action for this transaction ');

            $em = $this->getDoctrine()->getManager();
            $currency = $transaction->getCurrency();

            //Search wallet
            //$current_wallet = $group->getWallet($currency);
            //if($current_wallet == null) throw new HttpException(404,'Wallet not found');

            $amount = $transaction->getAmount();
            $payment_info = $transaction->getPayOutInfo();

            //RETRY
            if( isset( $data['retry'] ) && $data['retry'] == true ){
                $logger->info('Update transaction -> retry');
                if( $transaction->getStatus()== Transaction::$STATUS_FAILED ){
                    $logger->info('Update transaction -> status->failed');

                    $sender_account = $em->getRepository('FinancialApiBundle:Group')->findOneBy(array('id'=>$transaction->getGroup()));
                    $sender = $em->getRepository('FinancialApiBundle:User')->findOneBy(array('id'=>$transaction->getUser()));

                    $params = array(
                        'amount' => $amount,
                        'concept' => "Reenvio error",
                        'address' => $payment_info['address'],
                        'pin' => $sender->getPIN()
                    );
                    if(isset($data['internal_tx']) && $data['internal_tx']=='1') {
                        $params['internal_tx']='1';
                        $params['destionation_id']=$data['destionation_id'];
                    }
                    $logger->info('Update transaction -> create new transaction');
                    $message = $this->createTransaction($params, $version_number, 'out', $method_cname, $sender->getId(), $sender_account, '127.0.0.1');
                    $logger->info('New Transaction created');

                    $transaction->setDeleted(true);
                    $mongo->persist($transaction);
                    $mongo->flush();
                }
                elseif( $transaction->getStatus()== Transaction::$STATUS_CANCELLED ){
                    $logger->info('Update transaction -> status->cancelled');
                    //TODO hacer funcion que reactive envio
                    $logger->info('Si está cancelada por ahora no hace nada');

                }else{
                    throw new HttpException(409,"This transaction can't be retried. First has to be cancelled");
                }
            }

            if( isset( $data['cancel'] ) && $data['cancel'] == true ){
                $logger->info('Update transaction -> cancel');
                //el cash-out solo se puede cancelar si esta en created review o success
                //el cash-in de momento no se puede cancelar
                if($transaction->getStatus()== Transaction::$STATUS_CREATED || $transaction->getStatus() == Transaction::$STATUS_REVIEW || ( ($method_cname == "halcash_es" || $method_cname == "halcash_pl") && $transaction->getStatus() == Transaction::$STATUS_SUCCESS && $transaction->getPayOutInfo()['status'] == Transaction::$STATUS_SENT )){
                    /*
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
                        $balancer = $this->get('net.app.commons.balance_manipulator');
                        $balancer->addBalance($group, $amount, $transaction, "incoming2 contr 3");
                        $logger->info('Update transaction -> addBalance');

                        $em->persist($current_wallet);
                        $em->flush();

                        $transaction = $this->get('messenger')->notificate($transaction);

                        //return fees
                        $logger->info('Update transaction -> returnFees');
                        try{
                            $dealer->returnFees($transaction, $current_wallet);
                        }catch (HttpException $e){
                            throw $e;
                        }
                    }
                */
                }
                else{
                    throw new HttpException(403, "This transaction can't be cancelled.");
                }
            }
        }elseif( isset( $data['recheck'] ) && $data['recheck'] == true ){
            $logger->info('Update transaction -> recheck');
            /*
            $logger->info('Update transaction -> recheck');
            $transaction->setStatus(Transaction::$STATUS_CREATED);

            $payment_info = $transaction->getPayInInfo();
            $payment_info['status'] = 'created';
            $payment_info['final'] = false;

            $transaction->setPayInInfo($payment_info);
            $transaction->setUpdated(new \DateTime());
            */
        }else{
            $logger->info('Update transaction -> nothing');
//            $transaction = $service->update($transaction,$data);
        }

        //$mongo->persist($transaction);
        //$mongo->flush();

        $logger->info('Update transaction -> END');
        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'id' => $transaction->getId(),
                'result' => $message
            )
        );
    }

    /**
     * @Rest\View
     */
    public function check(Request $request, $version_number, $type, $method_cname, $id){
        $method = $this->get('net.app.'.$type.'.'.$method_cname.'.v'.$version_number);

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $group = $this->_getCurrentCompany($user);
        $this->_checkPermissions($user, $group);

//        $method_list = $group->getMethodsList();
//
//        if (!in_array($method_cname.'-'.$type, $method_list)) {
//            throw $this->createAccessDeniedException();
//        }



        $mongo = $this->get('doctrine_mongodb')->getManager();

        $transaction = $mongo->getRepository('FinancialApiBundle:Transaction')->findOneBy(array(
            'id'        => $id,
            'method'   =>  $method_cname,
            'group'      =>  $group->getId(),
            'type'      =>  $type
        ));

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        $this->get('net.app.commons.permissions_checker')->checkMethodPermissions($transaction);

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

                $transaction = $this->get('messenger')->notificate($transaction);

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
                        $balancer = $this->get('net.app.commons.balance_manipulator');
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

        $method = $this->get('net.app.'.$type.'.'.$method_cname.'.v'.$version_number);

        $dm = $this->get('doctrine_mongodb')->getManager();
        $user = $this->get('security.token_storage')
            ->getToken()->getUser();
        //TODO change this for active group
        $group = $user->getGroups()[0];

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction');

        if($request->query->get('query') != ''){
            $query = $request->query->get('query');
            $search = $query['search'];
            $order = $query['order'];
            $dir = $query['dir'];
            $start_time = new \MongoDate(strtotime(date($query['start_date'].' 00:00:00')));//date('Y-m-d 00:00:00')
            $finish_time = new \MongoDate(strtotime(date($query['finish_date'].' 23:59:59')));

            $transactions = $dm->getRepository('FinancialApiBundle:Transaction')->findTransactions($group, $start_time, $finish_time, $search, $order, $dir);
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
        $method = $this->get('net.app.'.$method_cname.'.'.$type.'.v'.$version_number);

        $mongo = $this->get('doctrine_mongodb')->getManager();
        $transaction =$mongo->getRepository('FinancialApiBundle:Transaction')->findOneBy(array(
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

            $user = $em->getRepository('FinancialApiBundle:User')->find($user_id);
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
                $balancer = $this->get('net.app.commons.balance_manipulator');
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
        $transaction = $this->get('messenger')->notificate($transaction);

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
     * @param EntityManagerInterface $em
     * @param $method_cname
     * @param $amount
     * @param $user_id
     * @param $group
     */
    public function checkCampaign(EntityManagerInterface $em, $method_cname, $amount, $user_id, $group): void
    {
        $campaign = $em->getRepository('FinancialApiBundle:Campaign')->findOneBy(array(
            'name' => Campaign::BONISSIM_CAMPAIGN_NAME
        ));

        $active_campaign = false;
        if(isset($campaign)){
            $init = $campaign->getInitDate();
            $end = $campaign->getEndDate();
            $now = new DateTime('NOW');
            $campaign_account = $em->getRepository(Group::class)->find($campaign->getCampaignAccount());
            $balance = $campaign_account->getWallet('REC')->getBalance() / 1e6;  //  1e6 = 1REC / 100
            if($init < $now && $now < $end && $amount < $balance){
                $active_campaign = true;
            }
        }

        if($group->getType() == Group::ACCOUNT_TYPE_PRIVATE && $method_cname == "lemonway" && $active_campaign) {
            $bonissim_private_account = $em->getRepository(Group::class)->findOneBy(array(
                'type' => Group::ACCOUNT_TYPE_PRIVATE, 'kyc_manager' => $user_id, 'name' => Campaign::BONISSIM_CAMPAIGN_NAME));
            if (isset($bonissim_private_account)){ // user has bonissim account
                $redeemable_amount = $bonissim_private_account->getRedeemableAmount();
                $allowed_amount = min($amount / 100, $campaign->getMax() -
                    ($bonissim_private_account->getRewardedAmount() + $redeemable_amount));
                $bonissim_private_account->setRedeemableAmount(min($allowed_amount + $redeemable_amount, $campaign->getMax()));
                $em->persist($bonissim_private_account);
                $em->flush();
            }
            elseif($amount >= $campaign->getMin() * 100) {
                $this->container->get('bonissim_service')->CreateBonissimAccount($user_id,
                    Campaign::BONISSIM_CAMPAIGN_NAME, min($amount / 100, $campaign->getMax()));
            }
        }
    }

    /**
     * @param $params
     * @param object|null $group
     * @param $type
     * @param $method_cname
     */
    private function checkCampaignConstraint($params, ?object $group, $type, $method_cname): void
    {
        $satoshi_decimals = 1e8;
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        $campaign = $em->getRepository(Campaign::class)->findOneBy(['name' => Campaign::BONISSIM_CAMPAIGN_NAME]);

        if(isset($campaign) && $campaign->getCampaignAccount() != $group->getId() && $type == "out" &&
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

            $sender_campaigns = $accountRepo->find($group->getId())->getCampaigns();
            $reciver_campaigns = $accountRepo->find($destination->getId())->getCampaigns();

            $user_id = $group->getKycManager()->getId();

            if (!$sender_campaigns->contains($campaign) && $group->getType() == Group::ACCOUNT_TYPE_PRIVATE
                && !$reciver_campaigns->contains($campaign)) { // sender and reciver accounts not in campaign

                $user_private_accounts = $accountRepo->findBy(['kyc_manager' => $user_id, 'type' => Group::ACCOUNT_TYPE_PRIVATE]);
                $user_balance = 0;
                foreach ($user_private_accounts as $account) {
                    if (!$account->getCampaigns()->contains($campaign)) {
                        $user_balance = $user_balance + $account->getWallets()[0]->getBalance();
                    } else {
                        $bonissim_account = $account;
                    }
                }
                if(isset($bonissim_account) && $bonissim_account->getRedeemableAmount() > $user_balance / $satoshi_decimals){ // user has bonissim account
                    $bonissim_account->setRedeemableAmount( $user_balance / $satoshi_decimals);
                    $em->persist($bonissim_account);
                    $em->flush();
                }

            }

            if ($sender_campaigns->contains($campaign)){ //sender is bonissim
                if (!$reciver_campaigns->contains($campaign)) { // reciver is not bonissim
                    if ($group->getKycManager()->getId() != $destination->getKycManager()->getId() ||
                        $destination->getType() != Group::ACCOUNT_TYPE_PRIVATE) { // accounts are from different user or reciver campany
                        throw new AppException(Response::HTTP_BAD_REQUEST, "Receiver account not in Campaign");
                    }
                }
                if ($destination->getType() == Group::ACCOUNT_TYPE_PRIVATE &&
                    $group->getKycManager()->getId() != $destination->getKycManager()->getId()) { // reciver is bonissim PRIVATE and different user
                    throw new AppException(Response::HTTP_BAD_REQUEST, "This account cannot receive payments");
                }

            }elseif ($reciver_campaigns->contains($campaign) && $destination->getType() == Group::ACCOUNT_TYPE_ORGANIZATION){ // sender is not bonissim and reciver is bonissim
                 $campaign_accounts = $campaign->getAccounts();

                 foreach($campaign_accounts as $account) {
                     if($account->getKycManager()->getId() == $user_id  && $account->getType() == Group::ACCOUNT_TYPE_PRIVATE){ // account is bonissim and private

                         $campaign_account = $accountRepo->findOneBy(['id' => $campaign->getCampaignAccount()]);
                         $user = $this->get('security.token_storage')->getToken()->getUser();

                         // send 15% from campaign account to commerce and from commerce to bonissim account
                         $request = array();
                         $request['concept'] = $params['concept'];
                         $request['amount'] = $params['amount'] / 100 * $campaign->getRedeemablePercentage();
                         $request['pin'] = $user->getPin();
                         $request['address'] = $destination->getRecAddress();
                         $request['internal_tx'] = '1';
                         $request['destionation_id'] = $account->getId();
                         $this->createTransaction($request, 1, 'out', $method_cname, $user->getId(), $campaign_account, '127.0.0.2');


                         $redeemable_amount = $account->getRedeemableAmount();
                         $new_rewarded = min($redeemable_amount, $params['amount'] / $satoshi_decimals);
                         $account->setRedeemableAmount($redeemable_amount - $new_rewarded);
                         $rewarded_amount = $account->getRewardedAmount();
                         $account->setRewardedAmount($rewarded_amount + $new_rewarded);
                         $em->persist($account);
                         $em->flush();
                     }
                }
            }
        }
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

}