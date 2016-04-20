<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Document\Transaction;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Entity\UserWallet;

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
            'reference'
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
        $token = $em->getRepository('TelepayFinancialApiBundle:CashInTokens')->findBy(array(
            'token' =>  $params['reference']
        ));

        if(!$token) throw new HttpException(404, 'Token not found');

        $token = $token[0];
        $user = $token->getUser();
        $service = $token->getService();

        //group needed to get and deal fees
        $group = $user->getGroups()[0];

        $group_commissions = $group->getCommissions();

        $group_commission = false;
        foreach ( $group_commissions as $commission ){
            if ( $commission->getServiceName() == $service ){
                $group_commission = $commission;
            }
        }

        //TODO obtain service provaider to get the currency

        $service_provider = $this->get('net.telepay.services.'.$service.'.v1');

        //if group commission not exists we create it
        if(!$group_commission){
            $group_commission = ServiceFee::createFromController($service, $group);
            $group_commission->setCurrency($service_provider->getCurrency());
            $em->persist($group_commission);
            $em->flush();
        }

        //Create cash in transaction
        $dm = $this->get('doctrine_mongodb')->getManager();

        $transaction = Transaction::createFromRequest($request);
        $transaction->setService($service);
        $transaction->setUser($user->getId());
        $transaction->setVersion('1');
        $params['description'] = 'cash_in ->'.$service;
        $transaction->setDataIn($params);

        $transaction->setAmount($params['amount']);

        //add commissions to check
        $fixed_fee = $group_commission->getFixed();
        $variable_fee = ($group_commission->getVariable()/100)*$params['amount'];

        //add fee to transaction
        $transaction->setVariableFee($variable_fee);
        $transaction->setFixedFee($fixed_fee);
        $transaction->setTotal($params['amount']);
        $total = $variable_fee + $fixed_fee + $params['amount'];
        $total_fee = $fixed_fee + $variable_fee;

        $transaction->setCurrency($service_provider->getCurrency());
        $transaction->setScale(2);
        $transaction->setStatus(Transaction::$STATUS_SUCCESS);
        $dm->persist($transaction);
        $dm->flush();
        //obtain wallet and check founds for cash_out services
        $wallets = $user->getWallets();

        $current_wallet = null;
        foreach ( $wallets as $wallet){
            if ($wallet->getCurrency() === $transaction->getCurrency()){
                $current_wallet = $wallet;
            }
        }

        $current_wallet->setAvailable($current_wallet->getAvailable()+$total);
        $current_wallet->setBalance($current_wallet->getBalance()+$total);

        $balancer = $this->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($user, $params['amount'], $transaction);

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

        $transaction = $this->get('notificator')->notificate($transaction);

        $dm->persist($transaction);
        $dm->flush();

        return $this->restTransaction($transaction, "Done");
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
        $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($transaction->getUser());

        //obtain wallet
        $wallets = $user->getWallets();

        $current_wallet = null;
        foreach ( $wallets as $wallet){
            if ($wallet->getCurrency() === $transaction->getCurrency()){
                $current_wallet = $wallet;
            }
        }

        if($validate == true){
            $transaction->setStatus('success');
            $total_fee = $transaction->getFixedFee() + $transaction->getVariableFee();
            $total = $transaction->getAmount() - $total_fee ;

            $current_wallet->setAvailable($current_wallet->getAvailable() + $total);
            $current_wallet->setBalance($current_wallet->getBalance()+$total);

            $balancer = $this->get('net.telepay.commons.balance_manipulator');
            $balancer->addBalance($user, $transaction->getAmount(), $transaction);

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
        $transactions = $dm->getRepository('TelepayFinancialApiBundle:Transaction')
                    ->findBy(array(
                'service'   =>  $service,
                'status'    =>  'created'
            ));


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

        //TODO hacer que no solo valga para swift, tiene que valer para los metodos tb con la misma llamada
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
                //money received and the cron will do the rest
                $transaction->setStatus(Transaction::$STATUS_RECEIVED);
                $paymentInfo = $transaction->getPayInInfo();
                $paymentInfo['status'] = Transaction::$STATUS_RECEIVED;
                $paymentInfo['final'] = false;
                $transaction->setPayInInfo($paymentInfo);
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

        //TODO hacer que no solo valga para swift, tiene que valer para los metodos tb con la misma llamada
        //only superadmin allowed
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if(!$request->request->has('validate')) throw new HttpException(404, 'Parameter "validate" not found');
        else $validate = $request->request->get('validate');

        $dm = $this->get('doctrine_mongodb')->getManager();
        $transRepo = $dm->getRepository('TelepayFinancialApiBundle:Transaction');
        $transaction = $transRepo->find($id);

        if($validate == true){
            $transaction->setStatus('success');
            $paymentInfo = $transaction->getPayOutInfo();
            $paymentInfo['status'] = 'sent';
            $paymentInfo['final'] = true;
            $transaction->setPayOutInfo($paymentInfo);

            $transaction = $this->get('notificator')->notificate($transaction);

            $dm->persist($transaction);
            $dm->flush();
        }

        return $this->restTransaction($transaction, "Done");

    }

    /**
     * @Rest\View
     */
    public function sepaOutValidation2(Request $request, $id){

        //only superadmin allowed
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $service = 'sepa_out';

        if(!$request->request->has('validate')) throw new HttpException(404, 'Parameter "validate" not found');
        else $validate = $request->request->get('validate');

        $dm = $this->get('doctrine_mongodb')->getManager();
        $transRepo = $dm->getRepository('TelepayFinancialApiBundle:Transaction');
        $transaction = $transRepo->find($id);

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($transaction->getUser());

        $wallets = $user->getWallets();

        $current_wallet = null;
        foreach ( $wallets as $wallet){
            if ($wallet->getCurrency() === $transaction->getCurrency()){
                $current_wallet = $wallet;
            }
        }

        if($validate == true){
            $transaction->setStatus('success');
            $total_fee = $transaction->getFixedFee() + $transaction->getVariableFee();
            $total = $transaction->getAmount() + $total_fee ;

            $current_wallet->setAvailable($current_wallet->getAvailable() - $total);
            $current_wallet->setBalance($current_wallet->getBalance() - $total);

            $balancer = $this->get('net.telepay.commons.balance_manipulator');
            $balancer->addBalance($user, $transaction->getAmount(), $transaction);

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
        $transactions = $dm->getRepository('TelepayFinancialApiBundle:Transaction')
            ->findBy(array(
                'method_out'  =>  'sepa',
                'type'  =>  'swift',
                'status'    =>  'sending'
            ));

        $transactions_out = $dm->getRepository('TelepayFinancialApiBundle:Transaction')
            ->findBy(array(
                'method'  =>  'sepa',
                'type'  =>  'out',
                'status'    =>  'sending'
            ));

        $transactions = array_merge($transactions, $transactions_out);

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
                $trans->setPayInInfo($pay_in_info);
            }else{
                throw new HttpException(404, 'Param status_in not found');
            }

            if($request->request->has('status_out')){
                $status_out = $request->request->get('status_out');
                $pay_out_info = $trans->getPayOutInfo();
                $pay_out_info['status'] = $status_out;
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
