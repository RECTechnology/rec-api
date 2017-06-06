<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Swift_Attachment;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Document\Transaction;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\CashInDeposit;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;
use WebSocket\Exception;

/**
 * Class SpecialActionsController
 * @package Telepay\FinancialApiBundle\Controller\Management\Admin
 */
class SpecialActionsController extends RestApiController {

    /**
     * @Rest\View
     */
    public function rechargeValidation(Request $request){

        //only superadmin allowed
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        //we need amount in cents and reference
        $paramNames = array(
            'amount',
            'reference',
            'hash'
        );

        $params = array();

        foreach ( $paramNames as $paramName){
            if($request->request->has($paramName)){
                $params[$paramName] = $request->request->get($paramName);
            }else{
                throw new HttpException(400,'Missing parameter '.$paramName);
            }
        }

        //search reference to get the user
        $em = $this->getDoctrine()->getManager();
        $hash = $em->getRepository('TelepayFinancialApiBundle:CashInDeposit')->findOneBy(array(
            'hash' =>  $params['hash']
        ));

        if($hash) throw new HttpException(403, 'This hash has been used in other transaction. Please check it and ensure that this is the correct hash');

        $token = $em->getRepository('TelepayFinancialApiBundle:CashInTokens')->findOneBy(array(
            'token' =>  $params['reference']
        ));

        if(!$token) throw new HttpException(404, 'Token not found');

        $tokenmethod = explode('-', $token->getMethod());
        $method = $tokenmethod[0];
        $type = $tokenmethod[1];

        $methodDriver = $this->get('net.telepay.in.'.$method.'.v1');

        //chek tier limits
        $limitManipulator = $this->get('net.telepay.commons.limit_manipulator');

        try{

            $limitManipulator->checkLimits($token->getCompany(), $methodDriver, $params['amount']);
        }catch (HttpException $e){
            throw new HttpException(403, $e->getMessage().'. This company ( '.$token->getCompany()->getName().' ) has reached his maximun limit. This company is Tier '.$token->getCompany()->getTier().'. Please update to the next Tier.');
        }

        $paymentInfo = $methodDriver->getPayInInfo($params['amount']);
        $paymentInfo['status'] = Transaction::$STATUS_SUCCESS;
        $paymentInfo['final'] = true;
        $paymentInfo['reference'] = $params['reference'];
        $paymentInfo['concept'] = $method.' deposit '.$params['reference'].' -> '.$params['hash'];

        //generate deposit hystory
        $deposit = new CashInDeposit();
        $deposit->setAmount($params['amount']);
        $deposit->setConfirmations(1);
        $deposit->setHash($params['hash']);
        $deposit->setStatus(CashInDeposit::$STATUS_DEPOSITED);
        $deposit->setToken($token);
        $em->persist($deposit);
        $em->flush();

        //Create cash in transaction
        $dm = $this->get('doctrine_mongodb')->getManager();
        $fee_manipulator = $this->get('net.telepay.commons.fee_manipulator');

        $company_fees = $fee_manipulator->getMethodFees($token->getCompany(), $methodDriver);

        $fixed = $company_fees->getFixed();
        $variable = $params['amount'] * $company_fees->getVariable()/100;
        $total_fee = $fixed + $variable;

        $transaction = Transaction::createFromRequest($request);
        $transaction->setMethod($method);
        $transaction->setGroup($token->getCompany()->getId());
        $transaction->setVersion('1');
        $transaction->setAmount($params['amount']);
        //TODO en type yo pondria deposit
        $transaction->setType($type);

        //add fee to transaction
        $transaction->setVariableFee($variable);
        $transaction->setFixedFee($fixed);
        $transaction->setTotal($params['amount']);
        $transaction->setCurrency($token->getCurrency());
        $transaction->setScale(Currency::$SCALE[$token->getCurrency()]);
        $transaction->setStatus(Transaction::$STATUS_SUCCESS);
        $transaction->setPayInInfo($paymentInfo);
        $dm->persist($transaction);
        $dm->flush();

        //obtain wallet and check founds for cash_out services
        $current_wallet = $token->getCompany()->getWallet($transaction->getCurrency());

        $current_wallet->addBalance($params['amount']);

        $balancer = $this->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($token->getCompany(), $params['amount'], $transaction);

        $em->flush();

        if($total_fee != 0){
            // nueva transaccion restando la comision al user
            $dealer = $this->container->get('net.telepay.commons.fee_deal');
            try{
                $dealer->createFees2($transaction, $current_wallet);
            }catch (HttpException $e){
                throw $e;
            }
        }

        $transaction = $this->get('notificator')->notificate($transaction);

        $dm->persist($transaction);
        $dm->flush();

        return $this->methodTransaction(201, $transaction, "Done. ".$params['amount'].' '.$transaction->getCurrency().' added to company '.$token->getCompany()->getName());
    }

    /**
     * @Rest\View
     */
    public function cashInValidation(Request $request, $id){

        //only superadmin allowed
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if(!$request->request->has('validate')) throw new HttpException(404, 'Parameter "validate" not found');
        else $validate = $request->request->get('validate');

        $dm = $this->get('doctrine_mongodb')->getManager();
        $transRepo = $dm->getRepository('TelepayFinancialApiBundle:Transaction');
        $transaction = $transRepo->find($id);

        //search reference to get the user
        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('TelepayFinancialApiBundle:Group')->find($transaction->getGroup());

        //obtain wallet
        $current_wallet = $group->getWallet($transaction->getCurrency());



        if($validate == true){
            $transaction->setStatus('success');
            $total_fee = $transaction->getFixedFee() + $transaction->getVariableFee();
            $total = $transaction->getAmount() - $total_fee ;

            $current_wallet->setAvailable($current_wallet->getAvailable() + $total);
            $current_wallet->setBalance($current_wallet->getBalance()+$total);

            $balancer = $this->get('net.telepay.commons.balance_manipulator');
            $balancer->addBalance($group, $transaction->getAmount(), $transaction);

            $em->persist($current_wallet);
            $em->flush();

//            if(!$user->hasRole('ROLE_SUPERADMIN')){
                if($total_fee != 0){
                    // nueva transaccion restando la comision al user
                    try{
                        $this->_dealer($transaction,$current_wallet);
                    }catch (HttpException $e){
                        throw $e;
                    }
                }
//            }


            $transaction = $this->get('notificator')->notificate($transaction);

            $dm->persist($transaction);
            $dm->flush();
        }


        return $this->restTransaction($transaction, "Done");

    }

    /**
     * @Rest\View
     */
    public function cashInList(Request $request, $service){

        //only superadmin allowed
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();
        $transactions = $dm->getRepository('TelepayFinancialApiBundle:Transaction')
                    ->findBy(array(
                'method'   =>  $service,
                'status'    =>  'created',
                'type'  =>  'in'
            ));


        $total = count($transactions);
        $response = array();
        foreach($transactions as $transaction){
            $company_id = $transaction->getGroup();
            if($company_id){
                $group = $em->getRepository('TelepayFinancialApiBundle:Group')->find($company_id);
                $group_data = array(
                    'name'  =>  $group->getName()
                );
            }else{
                $group_data = array(
                    'name'  =>  'not found'
                );
            }

            $transaction->setGroupData($group_data);
            $response[] = $transaction;

        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'start' => 0,
                'end' => $total,
                'elements' => $response,
                'scale' =>  2
            )
        );

    }

    /**
     * @Rest\View
     */
    public function cashOutList(Request $request, $method){

        //only superadmin allowed
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();
        if($method == 'sepa'){
            $transactions_out = $dm->getRepository('TelepayFinancialApiBundle:Transaction')
                ->findBy(array(
                    'method'   =>  $method,
                    'status'    =>  'sending',
                    'type'  =>  'out',
                    'pay_out_info.gestioned'    =>  false
                ));
        }else{
            $transactions_out = $dm->getRepository('TelepayFinancialApiBundle:Transaction')
                ->findBy(array(
                    'method'   =>  $method,
                    'status'    =>  'sending',
                    'type'  =>  'out'
                ));
        }

        $response = array();
        foreach($transactions_out as $transaction){
            $company_id = $transaction->getGroup();
            $group = $em->getRepository('TelepayFinancialApiBundle:Group')->find($company_id);
            $group_data = array(
                'name'  =>  $group->getName()
            );
            $transaction->setGroupData($group_data);
            $response[] = $transaction;

        }

        $transactions_out_qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');
        $transactions = $transactions_out_qb
            ->field('method_out')->equals('sepa')
            ->field('type')->equals('swift')
            ->field('status')->equals('sending')
            ->field('pay_out_info.gestioned')->equals(false)
            ->getQuery()
            ->execute();

        $resArray = [];
        foreach($transactions->toArray() as $res){
            $resArray []= $res;

        }

        $transactions_out_qb_transfer = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');
        $transactions_out_transfer = $transactions_out_qb_transfer
            ->field('method')->equals('transfer')
            ->field('type')->equals('out')
            ->field('status')->equals('sending')
            ->field('pay_out_info.gestioned')->equals(false)
            ->getQuery()
            ->execute();

        $resArray_out_transfer = [];
        foreach($transactions_out_transfer->toArray() as $res){
            $resArray_out_transfer []= $res;

        }

        $transactions = array_merge($resArray, $response);
        $transactions = array_merge($transactions, $resArray_out_transfer);

        $total = count($transactions);
        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'start' => 0,
                'end' => $total,
                'elements' => $transactions,
                'scale' =>  2
            )
        );

    }

    /**
     * @Rest\View
     */
    public function swiftList(Request $request){

        //only superadmin allowed
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $dm = $this->get('doctrine_mongodb')->getManager();

        $transactions = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('type')->equals('swift')
            ->field('status')->in(array('created','received'))
            ->field('method_in')->in(array('sepa', 'easypay'))
            ->getQuery();

        $all_transactions = array();
        foreach($transactions as $transaction){
            $all_transactions[] = $transaction;
        }

        $total = count($all_transactions);

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'start' => 0,
                'end' => $total,
                'elements' => $all_transactions,
                'scale' =>  2
            )
        );

    }

    /**
     * @Rest\View
     */
    public function swiftValidation(Request $request, $id){

        //only superadmin allowed
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if(!$request->request->has('validate')) throw new HttpException(404, 'Parameter "validate" not found');
        else $validate = $request->request->get('validate');

        $dm = $this->get('doctrine_mongodb')->getManager();
        $transRepo = $dm->getRepository('TelepayFinancialApiBundle:Transaction');
        $transaction = $transRepo->find($id);

        //TODO if transaction we have to validate is the input this works fine
        //TODO but if we have to validate the output we have to do it better

        if($validate == true){
            if($transaction->getMethodOut() == 'btc' || $transaction->getMethodOut() == 'fac'){
                if($transaction->getStatus() != Transaction::$STATUS_CREATED) throw new HttpException(403, 'This transaction can not be validated');
                //money received and the cron will do the rest
                $transaction->setStatus(Transaction::$STATUS_RECEIVED);
                $paymentInfo = $transaction->getPayInInfo();
                $paymentInfo['status'] = Transaction::$STATUS_RECEIVED;
                $paymentInfo['final'] = false;
                $transaction->setPayInInfo($paymentInfo);
                if( $transaction->getEmailNotification() != ""){
                    $email = $transaction->getEmailNotification();
                    $ticket = $transaction->getPayInInfo()['reference'];
                    $ticket = str_replace('BUY BITCOIN ', '', $ticket);
                    if($transaction->getMethodOut() == 'btc') $currency = 'BITCOIN' ;
                    else $currency = 'FAIRCOIN';
                    $body = array(
                        'reference' =>  $ticket,
                        'created'   =>  $transaction->getCreated()->format('Y-m-d H:i:s'),
                        'concept'   =>  'BUY '.$currency.' '.$ticket,
                        'amount'    =>  $transaction->getPayInInfo()['amount']/100,
                        'crypto_amount' => $transaction->getPayOutInfo()['amount']/1e8,
                        'tx_id'        =>  '',
                        'id'        =>  $ticket,
                        'address'   =>  $transaction->getPayOutInfo()['address']
                    );

                    //TODO no se pot enviar el ticket desde açi
                    //$this->_sendTicket($body, $email, $ticket, $currency);
                }
            }else{
                $transaction->setStatus(Transaction::$STATUS_SUCCESS);
                $paymentInfo = $transaction->getPayOutInfo();
                $paymentInfo['status'] = Transaction::$STATUS_SENT;
                $paymentInfo['final'] = true;
                $transaction->setPayOutInfo($paymentInfo);

                $dm->persist($transaction);
                $dm->flush();

                $command = $this->container->get('command.validate.swiftSepa');
                $input = new ArgvInput(
                    array(
                        '--env=' . $this->container->getParameter('kernel.environment'),
                        '--transaction-id=' . $id
                    )
                );
                $output = new BufferedOutput();
                $command->run($input, $output);

                if($id == $output) {
                    $transaction = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->find($id);
                }

            }

            $transaction = $this->get('notificator')->notificate($transaction);

            $dm->persist($transaction);
            $dm->flush();
        }

        return $this->restTransaction($transaction, "Done");

    }

    /**
     * @Rest\View
     */
    public function sepaOutValidation(Request $request, $id){

        //only superadmin allowed
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if(!$request->request->has('validate')) throw new HttpException(404, 'Parameter "validate" not found');
        else $validate = $request->request->get('validate');

        $dm = $this->get('doctrine_mongodb')->getManager();
        $transRepo = $dm->getRepository('TelepayFinancialApiBundle:Transaction');
        $transaction = $transRepo->find($id);

        if($transaction->getMethod() != 'sepa' && $transaction->getMethodOut() != 'sepa') throw new HttpException(403, 'This transaction can\'t be validated with this method');

        if($validate == true){
            $paymentInfo = $transaction->getPayOutInfo();
            if($paymentInfo['gestioned'] == true) throw new HttpException(403, 'This transactions is gestioned yet');
            $paymentInfo['gestioned'] = true;
            $transaction->setPayOutInfo($paymentInfo);
            $transaction->setUpdated(new \DateTime());

            $transaction = $this->get('notificator')->notificate($transaction);

            $dm->persist($transaction);
            $dm->flush();
        }

        return $this->restTransaction($transaction, "Done");

    }

    /**
     * @Rest\View
     */
    public function sepaOutList(Request $request){
        //only superadmin allowed
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $dm = $this->get('doctrine_mongodb')->getManager();
        $transactions_out_qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');

//        $transactions = $dm->getRepository('TelepayFinancialApiBundle:Transaction')
//            ->findBy(array(
//                'method_out'  =>  'sepa',
//                'type'  =>  'swift',
//                'status'    =>  'sending'
//            ));

        $transactions = $transactions_out_qb
            ->field('method_out')->equals('sepa')
            ->field('type')->equals('swift')
            ->field('status')->equals('sending')
            ->field('pay_out_info.gestioned')->equals(false)
            ->getQuery()
            ->execute();

        $resArray = [];
        foreach($transactions->toArray() as $res){
            $resArray []= $res;

        }

//        $transactions_out = $dm->getRepository('TelepayFinancialApiBundle:Transaction')
//            ->findBy(array(
//                'method'  =>  'sepa',
//                'type'  =>  'out',
//                'status'    =>  'sending',
//                'pay_out_info.gestioned'  =>  true
//            ));


        $transactions_out_qb_sepa = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');

        $transactions_out = $transactions_out_qb_sepa
            ->field('method')->equals('sepa')
            ->field('type')->equals('out')
            ->field('status')->equals('sending')
            ->field('pay_out_info.gestioned')->equals(false)
            ->getQuery()
            ->execute();

        $resArray_out = [];
        foreach($transactions_out->toArray() as $res){
            $resArray_out []= $res;

        }

//        $transactions_out_transfer = $dm->getRepository('TelepayFinancialApiBundle:Transaction')
//            ->findBy(array(
//                'method'  =>  'transfer',
//                'type'  =>  'out',
//                'status'    =>  'sending'
//            ));

        $transactions_out_qb_transfer = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');
        $transactions_out_transfer = $transactions_out_qb_transfer
            ->field('method')->equals('transfer')
            ->field('type')->equals('out')
            ->field('status')->equals('sending')
            ->field('pay_out_info.gestioned')->equals(false)
            ->getQuery()
            ->execute();

        $resArray_out_transfer = [];
        foreach($transactions_out_transfer->toArray() as $res){
            $resArray_out_transfer []= $res;

        }

        $transactions = array_merge($resArray, $resArray_out);
        $transactions = array_merge($transactions, $resArray_out_transfer);

        $total = count($transactions);

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'start' => 0,
                'end' => $total,
                'elements' => $transactions,
                'scale' =>  2
            )
        );

    }

    /**
     * @Rest\View
     */
    public function updateTransactionStatus(Request $request, $id){

        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        //Get transaction and change status
        $dm = $this->get('doctrine_mongodb')->getManager();
        $trans = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->find($id);

        if(!$trans) throw new HttpException(404,'Not found');

        if($request->request->has('status')){
            $status = $request->request->get('status');
        }else{
            throw new HttpException(404, 'Param status not found');
        }

        if($trans->getType() == 'swift'){
            if($request->request->has('status_in')){
                $status_in = $request->request->get('status_in');
                $pay_in_info = $trans->getPayInInfo();
                $pay_in_info['status'] = $status_in;
                if($status_in == 'success') $pay_in_info['final'] = true;
                $trans->setPayInInfo($pay_in_info);
            }else{
                throw new HttpException(404, 'Param status_in not found');
            }

            if($request->request->has('status_out')){
                $status_out = $request->request->get('status_out');
                $pay_out_info = $trans->getPayOutInfo();
                $pay_out_info['status'] = $status_out;
                if($status_out == 'sent') $pay_out_info['final'] = true;
                $trans->setPayOutInfo($pay_out_info);
            }else{
                throw new HttpException(404, 'Param status_out not found');
            }
        }

        $trans->setStatus($status);
        $dm->persist($trans);
        $dm->flush();

        if($trans->getType() == 'swift'){
            return $this->swiftTransaction($trans, "Done");
        }else{
            return $this->methodTransaction(200, $trans, "Done");
        }

    }

    /**
     * @Rest\View
     */
    public function changeTransactionAmount(Request $request, $id){

        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        //Get transaction and change amount
        $dm = $this->get('doctrine_mongodb')->getManager();
        $trans = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->find($id);

        if(!$trans) throw new HttpException(404,'Not found');

        if($request->request->has('amount')){
            $amount = $request->request->get('amount');
        }else{
            throw new HttpException(404, 'Param amount not found');
        }

        if($trans->getMethod() != 'btc' || $trans->getType() != 'in' || $trans->getStatus() != Transaction::$STATUS_EXPIRED) throw new HttpException(403, 'Transaction can\'t be updated');

        $payInInfo = $trans->getPayInInfo();
        $payInInfo['amount'] = $amount;
        $trans->setAmount($amount);
        $trans->setTotal($amount);
        $dataIn = $trans->getDataIn();
        $dataIn['amount'] = $amount;

        $trans->setDataIn($dataIn);
        $trans->setPayInInfo($payInInfo);

        $dm->persist($trans);
        $dm->flush();

        return $this->restV2(204, 'success', 'Amount changed successfully');

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

    private function _dealer2(Transaction $transaction, UserWallet $current_wallet){

        $logger = $this->get('logger');
        $logger->info('make transaction -> dealer');
        $amount = $transaction->getAmount();
        $currency = $transaction->getCurrency();
        $method_cname = $transaction->getMethod() . "-" . $transaction->getType();

        $em = $this->getDoctrine()->getManager();

        $total_fee = $transaction->getFixedFee() + $transaction->getVariableFee();
        $group = $em->getRepository('TelepayFinancialApiBundle:Group')->find($transaction->getGroup());
        $creator = $group->getGroupCreator();

        $feeTransaction = Transaction::createFromTransaction($transaction);
        $feeTransaction->setMethod($method_cname);
        $feeTransaction->setAmount($total_fee);
        $feeTransaction->setDataIn(array(
            'previous_transaction'  =>  $transaction->getId(),
            'amount'                =>  -$total_fee,
            'concept'           =>  $method_cname.'->fee',
            'admin'                 =>  $creator->getName()
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
            'concept'           =>  $method_cname.'->fee',
            'admin'                 =>  $creator->getName()
        ));
        $feeTransaction->setFeeInfo(array(
            'previous_transaction'  =>  $transaction->getId(),
            'previous_amount'   =>  $transaction->getAmount(),
            'amount'                =>  -$total_fee,
            'currency'      =>  $currency,
            'scale'     =>  $transaction->getScale(),
            'concept'           =>  $method_cname.'->fee',
            'status'    =>  Transaction::$STATUS_SUCCESS
        ));

        $feeTransaction->setType(Transaction::$TYPE_FEE);

        $feeTransaction->setTotal(-$total_fee);

        $mongo = $this->get('doctrine_mongodb')->getManager();
        $mongo->persist($feeTransaction);
        $mongo->flush();

        $logger->info('make transaction -> feeTransaction id => '.$feeTransaction->getId());

        $logger->info('make transaction -> addBalance');
        $balancer = $this->get('net.telepay.commons.balance_manipulator');

        $balancer->addBalance($group, -$total_fee, $feeTransaction );
        //empezamos el reparto

        if(!$creator) throw new HttpException(404,'Creator not found');

        $transaction_id = $transaction->getId();
        $dealer = $this->get('net.telepay.commons.fee_deal');
        $dealer->deal(
            $creator,
            $amount,
            $transaction->getMethod(),
            $transaction->getType(),
            $currency,
            $total_fee,
            $transaction_id,
            $transaction->getVersion()
        );

    }

    private function _sendTicket($body, $email, $ref, $currency){
        $html = $this->renderView('TelepayFinancialApiBundle:Email:ticket' . $currency . '.html.twig', $body);

        $dompdf = $this->get('slik_dompdf');
        $dompdf->getpdf($html);
        $pdfoutput = $dompdf->output();

        $message = \Swift_Message::newInstance()
            ->setSubject('Chip-Chap Ticket ref: '.$ref)
            ->setFrom('no-reply@chip-chap.com')
            ->setTo(array(
                $email
            ))
            ->setBody(
                $this->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:ticket' . $currency . '.html.twig',
                        $body
                    )
            )
            ->setContentType('text/html')
            ->attach(Swift_Attachment::newInstance($pdfoutput, $ref.'-'.$body["id"].'.pdf'));

        $this->get('mailer')->send($message);
    }

    public function getNewAddress(Request $request, $currency){

        if($currency != 'btc' && $currency != 'fac' && $currency != 'fair') throw new HttpException(404, 'Bad request, currency not allowed');

        if($currency == 'fac'){
            $currency = 'fair';
        }

        $driver = $this->container->get('net.telepay.wallet.fullnode.'.$currency);

        try{
            $newAddress = $driver->getAddress();
        }catch (Exception $e){
            throw new HttpException(404, 'Something went wrong');
        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'currency' => $currency,
                'address' => $newAddress
            )
        );


    }
}
