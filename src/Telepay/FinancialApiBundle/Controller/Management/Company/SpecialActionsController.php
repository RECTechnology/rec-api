<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Swift_Attachment;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use WebSocket\Exception;

/**
 * Class SpecialActionsController
 * @package Telepay\FinancialApiBundle\Controller\Management\Company
 */
class SpecialActionsController extends RestApiController {

    /**
     * @Rest\View
     */
    public function easypayValidation(Request $request, $id){

        $logger = $this->get('manager.logger');
        $logger->info('EASYPAY validation');
        //only WORKER from EASYPAY company can validate this transactions
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_WORKER')) {
            throw $this->createAccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();

        $client_id = $this->container->getParameter('swift_client_id_default_easypay');
        $root_id = $this->container->getParameter('admin_user_id');
        $root_group_id = $this->container->getParameter('id_group_root');

        $root = $em->getRepository('TelepayFinancialApiBundle:User')->find($root_id);
        $rootGroup = $em->getRepository('TelepayFinancialApiBundle:Group')->find($root_group_id);
        $client = $em->getRepository('TelepayFinancialApiBundle:Client')->find($client_id);

        if(!$client) throw new HttpException(404, 'Client default not found');

        $user = $this->get('security.context')->getToken()->getUser();
        $activeCompany = $user->getActiveGroup();

        $company = $client->getGroup();

        if($activeCompany->getId() != $company->getId()) throw new HttpException(403, 'You don\'t have the necessary permissions');

        if(!$request->request->has('validate')) throw new HttpException(404, 'Parameter "validate" not found');
        else $validate = $request->request->get('validate');

        $dm = $this->get('doctrine_mongodb')->getManager();
        $transRepo = $dm->getRepository('TelepayFinancialApiBundle:Transaction');
        $transaction = $transRepo->find($id);

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        $logger->info('EASYPAY company=> '.$company->getName().' transaction'.$transaction->getId().' client=>'.$client->getId());

        //TODO if transaction we have to validate is the input this works fine
        //TODO but if we have to validate the output we have to do it better

        if($validate == true){
            if($transaction->getMethodOut() == 'btc' || $transaction->getMethodOut() == 'fac'){
                if($transaction->getStatus() != Transaction::$STATUS_CREATED) throw new HttpException(403, 'This transaction can not be validated');
                $logger->info('EASYPAY VALIDATING');
                //money received and the cron will do the rest
                $method_in = $transaction->getMethodIn();
                $method_out = $transaction->getMethodOut();
                //GET METHODS
                $cashInMethod = $this->get('net.telepay.in.'.$method_in.'.v1');
                $cashOutMethod = $this->get('net.telepay.out.'.$method_out.'.vG');

                $btcRootMethod = $this->get('net.telepay.out.btc.v1');

                $pay_in_info = $transaction->getPayInInfo();
                $pay_out_info = $transaction->getPayOutInfo();

                //get configuration(method)
                $swift_config = $this->get('net.telepay.config.'.$method_in.'.'.$method_out);
                $methodFees = $swift_config->getFees();

                //get client fees (fixed & variable)
                $clientFees = $em->getRepository('TelepayFinancialApiBundle:SwiftFee')->findOneBy(array(
                    'client'    =>  $client,
                    'cname' =>  $method_in.'-'.$method_out
                ));

                $amount = round($this->_exchange($pay_in_info['amount'], $cashInMethod->getCurrency(), $cashOutMethod->getCurrency()),0);

                $client_fee = round(($amount * ($clientFees->getVariable()/100) + $clientFees->getFixed()),0);
                $service_fee = round(($amount * ($methodFees->getVariable()/100) + $methodFees->getFixed()),0);
                $final_amount = $amount - $service_fee - $client_fee;
                $pay_out_info['amount'] = $final_amount;

                $logger->info('EASYPAY  eur=>'.$pay_in_info['amount'].' exchange=>'.$amount.' totalFees=>'.($service_fee + $client_fee).' finalAmount=>'.$final_amount);

                $pay_in_info['status'] = 'success';
                $transaction->setPayOutInfo($pay_out_info);
                $transaction->setPayInInfo($pay_in_info);
                $transaction->setDataOut($pay_in_info);
                $transaction->setAmount($final_amount);
                $transaction->setTotal($final_amount);
                $transaction->setUpdated(new \DateTime());

                //get root btc address for send fee
                $logger->info('EASYPAY GETTING ROOT ADDRESS');
                $pay_in_infoRoot = $btcRootMethod->getPayInInfo($service_fee);


                //Send btc to user
                try{
                    $pay_out_info = $cashOutMethod->send($pay_out_info);
                    $logger->info('EASYPAY SENDING BTC TO ENDUSER');
                }catch (Exception $e){
                    $logger->info('EASYPAY SENDING BTC FAILED');
                    $pay_out_info['status'] = Transaction::$STATUS_FAILED;
                    $pay_out_info['final'] = false;
                    $error = $e->getMessage();
                    $transaction->setPayOutInfo($pay_out_info);
                    $transaction->setStatus('failed');
                }
                $logger->info('EASYPAY SENDING BTC STATUS => '.$pay_out_info['status']);
                $transaction->setPayOutInfo($pay_out_info);

                $dm->persist($transaction);
                $dm->flush();

                if($pay_out_info['status'] == Transaction::$STATUS_SENT){
                    $transaction->setStatus(Transaction::$STATUS_SUCCESS);
                    $transaction->setDataIn($pay_out_info);
                    $dm->persist($transaction);
                    $dm->flush();
                    $logger->info('EASYPAY SENDING TICKET');
                    if( $transaction->getEmailNotification() != ""){
                        $currency = array(
                            'btc' => 'BITCOIN',
                            'fac' => 'FAIRCOIN'
                        );
                        $email = $transaction->getEmailNotification();
                        $ticket = $transaction->getPayInInfo()['reference'];
                        $ticket = str_replace('BUY BITCOIN ', '', $ticket);
                        $ticket = str_replace('BUY FAIRCOIN ', '', $ticket);
                        $body = array(
                            'reference' =>  $ticket,
                            'created'   =>  $transaction->getCreated()->format('Y-m-d H:i:s'),
                            'concept'   =>  'BUY ' . $currency[$method_out] . " " . $ticket,
                            'amount'    =>  $transaction->getPayInInfo()['amount']/100,
                            'crypto_amount' => $transaction->getPayOutInfo()['amount']/1e8,
                            'tx_id'        =>  $transaction->getPayOutInfo()['txid'],
                            'id'        =>  $ticket,
                            'address'   =>  $transaction->getPayOutInfo()['address']
                        );

                        $this->_sendTicket($body, $email, $ticket, $method_out);
                    }

                    //Generate fee transactions. One for the user and one for the root
                    if($client_fee != 0){
                        $logger->info('EASYPAY CLIENT FEE=>'.$client_fee.' fixed=>'.$clientFees->getFixed().' variable=>'.$amount * ($clientFees->getVariable()/100).' ( '.($clientFees->getVariable()/100).'% )');
                        //client fees goes to the user
                        $userFee = new Transaction();
                        if($transaction->getUser()) $transaction->setUser($transaction->getUser());
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
                        $userFeeInfo = array(
                            'previous_transaction'  =>  $transaction->getId(),
                            'previous_amount'   =>  $transaction->getAmount(),
                            'amount'                =>  $client_fee,
                            'currency'      =>  $transaction->getCurrency(),
                            'scale'     =>  $transaction->getScale(),
                            'concept'           =>  $method_in.'-'.$method_out.'->fee',
                            'status'    =>  Transaction::$STATUS_SUCCESS
                        );
                        $userFee->setFeeInfo($userFeeInfo);
                        $userFee->setClient($client);
                        $dm->persist($userFee);

                        $userWallet = $company->getWallet($transaction->getCurrency());

//                        $userWallet->setAvailable($userWallet->getAvailable() + $client_fee);
//                        $userWallet->setBalance($userWallet->getBalance() + $client_fee);

                        $em->persist($userWallet);
                        $em->flush();
                    }

                    if($service_fee != 0){

                        //service fees goes to root
                        $logger->info('EASYPAY ROOT FEE=>'.$service_fee.' fixed=>'.$methodFees->getFixed().' variable=>'.$amount * ($methodFees->getVariable()/100).' ( '.($methodFees->getVariable()/100).'% )');

                        $logger->info('EASYPAY SENDING ROOT BTC=>'.$service_fee.' satoshis');
                        $rootFeeStatus = Transaction::$STATUS_SUCCESS;
                        try{
                            //TODO revisar esto
                            $pay_out_infoRoot = array(
                                'amount'    =>  $pay_in_infoRoot['amount'],
                                'address'   =>  $pay_in_infoRoot['address']
                            );
                            $pay_out_infoRoot = $cashOutMethod->send($pay_out_infoRoot);
                        }catch (Exception $e){
                            $logger->info('EASYPAY SENDING ROOT BTC FAILED');
                            $rootFeeStatus = Transaction::$STATUS_FAILED;
                        }

                        $rootFee = new Transaction();
                        $rootFee->setUser($root->getId());
                        $rootFee->setGroup($rootGroup->getId());
                        $rootFee->setType(Transaction::$TYPE_FEE);
                        $rootFee->setCurrency($transaction->getCurrency());
                        $rootFee->setScale($transaction->getScale());
                        $rootFee->setAmount($service_fee);
                        $rootFee->setFixedFee($methodFees->getFixed());
                        $rootFee->setVariableFee($amount * ($methodFees->getVariable()/100));
                        $rootFee->setService($method_in.'-'.$method_out);
                        $rootFee->setMethod($method_in.'-'.$method_out);
                        $rootFee->setStatus($rootFeeStatus);
                        $rootFee->setTotal($service_fee);
                        $rootFee->setDataIn(array(
                            'previous_transaction'  =>  $transaction->getId(),
                            'transaction_amount'    =>  $transaction->getAmount(),
                            'total_fee' =>  $client_fee + $service_fee,
                            'previous_group_id'   =>  $company->getId(),
                            'previous_group_name'   =>  $company->getName()
                        ));

                        $serviceFeeInfo = array(
                            'previous_transaction'  =>  $transaction->getId(),
                            'previous_amount'   =>  $transaction->getAmount(),
                            'amount'                =>  $service_fee,
                            'currency'      =>  $transaction->getCurrency(),
                            'scale'     =>  $transaction->getScale(),
                            'concept'           =>  $method_in.'-'.$method_out.'->fee',
                            'status'    =>  Transaction::$STATUS_SUCCESS,
                            'previous_group_id'   =>  $company->getId(),
                            'previous_group_name'   =>  $company->getName(),
                            'pay_in_info'   =>  $pay_in_infoRoot
                        );
                        $rootFee->setFeeInfo($serviceFeeInfo);
                        $rootFee->setClient($client);

                        $dm->persist($rootFee);
                        //get wallets and add fees to both, user and wallet
                        $rootWallets = $rootGroup->getWallets();
                        $current_wallet = null;

                        foreach ( $rootWallets as $wallet){
                            if ($wallet->getCurrency() == $rootFee->getCurrency()){
                                $current_wallet = $wallet;
                            }
                        }

                        $current_wallet->setAvailable($current_wallet->getAvailable() + $service_fee);
                        $current_wallet->setBalance($current_wallet->getBalance() + $service_fee);

                        $em->persist($current_wallet);
                        $em->flush();
                    }

                    $dm->flush();

                }else{
                    $logger->info('EASYPAY AN ERROR OCURRED');
                    $transaction->setStatus(Transaction::$STATUS_FAILED);
                    $dm->persist($transaction);
                    $dm->flush();
                    //send mail informig the error
                    $error = array(
                        'transaction_id'    =>  $transaction->getId(),
                        'type'    =>    $transaction->getType(),
                        'method'    =>  $transaction->getMethodIn().' -> '.$transaction->getMethodOut(),
                        'status'    =>  $transaction->getStatus(),
                        'status_in' =>  $pay_in_info['status'],
                        'status_out'    =>  $pay_out_info['status'],
                        'amount'    =>  $transaction->getAmount(),
                        'error' =>  $error
                    );

                    $this->_sendErrorEmail('Swift error mail', $error);
                }
            }else{
                throw new HttpException(404, 'This transaction can\'t be validated');
            }

            $transaction = $this->get('notificator')->notificate($transaction);

            $dm->persist($transaction);
            $dm->flush();
        }

        return $this->restTransaction($transaction, "Done");

    }

    private function _sendTicket($body, $email, $ref, $method_out){
        $html = $this->get('templating')->render('TelepayFinancialApiBundle:Email:ticket' . $method_out . '.html.twig', $body);

        $marca = array(
            "btc" => "Chip-Chap",
            "fac" => "Fairtoearth"
        );
        $dompdf = $this->get('slik_dompdf');
        $dompdf->getpdf($html);
        $pdfoutput = $dompdf->output();

        $no_replay = $this->container->getParameter('no_reply_email');

        $message = \Swift_Message::newInstance()
            ->setSubject($marca[$method_out] . 'Ticket ref: '.$ref)
            ->setFrom($no_replay)
            ->setTo(array(
                $email
            ))
            ->setBody(
                $this->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:ticket' . $method_out . '.html.twig',
                        $body
                    )
            )
            ->setContentType('text/html')
            ->attach(Swift_Attachment::newInstance($pdfoutput, $ref.'-'.$body["id"].'.pdf'));

        $this->get('mailer')->send($message);
    }

    private function _sendErrorEmail($subject, $body){

        $no_replay = $this->container->getParameter('no_reply_email');

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($no_replay)
            ->setTo(array(
                'pere@chip-chap.com',
                'cto@chip-chap.com'
            ))
            ->setBody(
                $this->container->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:error.html.twig',
                        array(
                            'message'        =>  $body
                        )
                    )
            );

        $this->container->get('mailer')->send($message);
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

    private function _exchange($amount,$curr_in,$curr_out){

        $dm = $this->get('doctrine')->getManager();
        $exchangeRepo = $dm->getRepository('TelepayFinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$curr_in,'dst'=>$curr_out),
            array('id'=>'DESC')
        );

        if(!$exchange) throw new HttpException(404,'Exchange not found -> '.$curr_in.' TO '.$curr_out);

        $price = $exchange->getPrice();
        $total = round($amount * $price,0);

        return $total;

    }

    public function changeTier(Request $request, $company_id, $tier){
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_WORKER')) {
            throw $this->createAccessDeniedException();
        }
        //Function only available for botc
        $botc_id = $this->container->getParameter('default_company_creator_commerce_botc');
        $user = $this->get('security.context')->getToken()->getUser();
        $activeCompany = $user->getActiveGroup();
        if($activeCompany->getId() != $botc_id) throw new HttpException(403, 'You don\'t have the necessary permissions');
        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
            'id'  =>  $company_id,
            'group_creator' =>  $botc_id
        ));
        if(!$group) throw new HttpException(404, 'Group not allowed');
        if($group->getGroupCreator()->getId() != $botc_id) throw new HttpException(404, 'Group not allowed');
        $group->setTier($tier);
        $em->flush();
        return $this->rest(204, 'Company tier updated successfully');
    }

    public function updateBotc(Request $request, $company_id){
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_WORKER')) {
            throw $this->createAccessDeniedException();
        }
        //Function only available for botc
        $botc_id = $this->container->getParameter('default_company_creator_commerce_botc');
        $user = $this->get('security.context')->getToken()->getUser();
        $activeCompany = $user->getActiveGroup();
        if($activeCompany->getId() != $botc_id) throw new HttpException(403, 'You don\'t have the necessary permissions');
        $em = $this->getDoctrine()->getManager();
        $group = $em->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
            'id'  =>  $company_id
        ));
        if(!$group) throw new HttpException(404, 'Group not allowed');
        if($group->getGroupCreator()->getId() != $botc_id) throw new HttpException(404, 'Group not allowed');
        if($request->request->has('botc')){
            if($request->request->get('botc')==true){
                $group->setPremium(true);
                $group->setTier(10);
                $groupFees = $em->getRepository('TelepayFinancialApiBundle:ServiceFee')->findBy(array(
                    'group'    =>  $company_id
                ));
                foreach($groupFees as $fee){
                    $pos = strpos($fee->getServiceName(), "exchange");
                    if ($pos !== false) {
                        $pos2 = strpos($fee->getServiceName(), "FAC");
                        if ($pos2 !== false) {
                            $fee->setVariable(0);
                        }
                        else{
                            $fee->setVariable(1);
                        }
                    }
                    elseif($fee->getServiceName()=="btc-in" || $fee->getServiceName()=="fac-in" || $fee->getServiceName()=="fac-out"){
                        $fee->setVariable(0);
                    }
                    elseif($fee->getServiceName()=="btc-out"){
                        $fee->setVariable(0);
                        $fee->setFixed(100000);
                    }
                    elseif($fee->getServiceName()=="halcash_es-out"){
                        $fee->setVariable(1);
                        $fee->setFixed(200);
                    }
                    elseif($fee->getServiceName()=="halcash_pl-out"){
                        $fee->setVariable(1);
                        $fee->setFixed(2000);
                    }
                    elseif($fee->getServiceName()=="cryptocapital-out"){
                        $fee->setVariable(2);
                    }
                    elseif($fee->getServiceName()=="sepa-in" || $fee->getServiceName()=="sepa-out" || $fee->getServiceName()=="transfer-out"){
                        $fee->setVariable(0);
                    }
                    elseif($fee->getServiceName()=="easypay-in"){
                        $fee->setVariable(3);
                    }
                    elseif($fee->getServiceName()=="teleingreso-in"){
                        $fee->setVariable(6);
                    }
                    elseif($fee->getServiceName()=="teleingreso_usa-in"){
                        $fee->setVariable(3);
                    }
                    $em->persist($fee);
                    $em->flush();
                }
            }
            elseif($request->request->get('botc')==false){
                $group->setPremium(false);
                $group->setTier(1);
                $groupFees = $em->getRepository('TelepayFinancialApiBundle:ServiceFee')->findBy(array(
                    'group'    =>  $company_id
                ));
                foreach($groupFees as $fee){
                    $pos = strpos($fee->getServiceName(), "exchange");
                    if ($pos !== false) {
                        $fee->setVariable(1);
                    }
                    elseif($fee->getServiceName()=="btc-in" || $fee->getServiceName()=="fac-in" || $fee->getServiceName()=="fac-out"){
                        $fee->setVariable(0);
                    }
                    elseif($fee->getServiceName()=="btc-out"){
                        $fee->setVariable(0);
                        $fee->setFixed(100000);
                    }
                    elseif($fee->getServiceName()=="halcash_es-out"){
                        $fee->setVariable(1);
                        $fee->setFixed(200);
                    }
                    elseif($fee->getServiceName()=="halcash_pl-out"){
                        $fee->setVariable(1);
                        $fee->setFixed(2000);
                    }
                    elseif($fee->getServiceName()=="cryptocapital-out"){
                        $fee->setVariable(2);
                    }
                    elseif($fee->getServiceName()=="sepa-out" || $fee->getServiceName()=="transfer-out"){
                        $fee->setVariable(1);
                    }
                    elseif($fee->getServiceName()=="easypay-in"){
                        $fee->setVariable(3);
                    }
                    elseif($fee->getServiceName()=="teleingreso-in"){
                        $fee->setVariable(6);
                    }
                    elseif($fee->getServiceName()=="teleingreso_usa-in"){
                        $fee->setVariable(3);
                    }
                    $em->persist($fee);
                    $em->flush();
                }
            }
        }
        $em->flush();
        return $this->rest(204, 'Company tier updated successfully');
    }
}
