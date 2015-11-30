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
use Telepay\FinancialApiBundle\Entity\SwiftFee;
use Telepay\FinancialApiBundle\Entity\SwiftLimit;
use Telepay\FinancialApiBundle\Entity\SwiftLimitCount;
use Telepay\FinancialApiBundle\Entity\UserWallet;

class SwiftController extends RestApiController{

    public function make(Request $request, $version_number, $type_in, $type_out){


        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();

        $admin = $em->getRepository('TelepayFinancialApiBundle:User')->findOneById(1);

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {

            //transaction authenticated with user/pass or credentials
            $user = $this->container->get('security.context')->getToken()->getUser();

            $tokenManager = $this->container->get('fos_oauth_server.access_token_manager.default');
            $accessToken = $tokenManager->findTokenByToken(
                $this->container->get('security.context')->getToken()->getToken()
            );
            $client = $accessToken->getClient();
            if(!$user){
                $user = $client->getUser();
            }

        }else{
            //TODO get user superadmin

            $user = $admin;
            $client = $em->getRepository('TelepayFinancialApiBundle:Client')->findOneById(49);

        }

        if(!$request->request->has('amount')) throw new HttpException(404, 'Param amount not found');

        $amount = $request->request->get('amount');

        //Create transaction
        $transaction = new Transaction();
        $transaction->createFromRequest($request);
        $transaction->setAmount($amount);
        $transaction->setTotal($amount);
        $transaction->setFixedFee(0);
        $transaction->setVersion($version_number);
        $transaction->setVariableFee(0);
        $transaction->setService($type_in.'_'.$type_out);
        $transaction->setUser($user->getId());
        $transaction->setType('swift');
        $transaction->setMethodIn($type_in);
        $transaction->setMethodOut($type_out);
        $transaction->setClient($client->getId());

        //GET METHODS
        $cashInMethod = $this->container->get('net.telepay.in.'.$type_in.'.v'.$version_number);

        $cashOutMethod = $this->container->get('net.telepay.out.'.$type_out.'.v'.$version_number);

        //GET PAYOUT INFO(parameters sent by user
        $pay_out_info = $cashOutMethod->getPayOutInfo($request);
        $transaction->setDataIn($pay_out_info);
        $transaction->setPayOutInfo($pay_out_info);

        //GET/ PAYOUT CURRENCY FOR THE TRANSATION
        $transaction->setCurrency($cashOutMethod->getCurrency());
        //TODO get scale in and out
        $transaction->setScale(2);

        //get configuration(method)
        $swift_config = $this->container->get('net.telepay.config.'.$type_out);
        $methodFees = $swift_config->getFees();

        //get client fees (fixed & variable)
        $clientFees = $em->getRepository('TelepayFinancialApiBundle:SwiftFee')->findOneBy(array(
            'client'    =>  $client->getId(),
            'cname' =>  $type_in.'_'.$type_out
        ));

        $clientLimits = $em->getRepository('TelepayFinancialApiBundle:SwiftLimit')->findOneBy(array(
            'client'    =>  $client->getId(),
            'cname' =>  $type_in.'_'.$type_out
        ));

        $clientLimitsCount = $em->getRepository('TelepayFinancialApiBundle:SwiftLimitCount')->findOneBy(array(
            'client'    =>  $client->getId(),
            'cname' =>  $type_in.'_'.$type_out
        ));

        if(!$clientFees){
            $clientFees = new SwiftFee();
            $clientFees->setFixed(0);
            $clientFees->setVariable(0);
            $clientFees->setCname($type_in.'_'.$type_out);
            $clientFees->setClient($client);
            $clientFees->setCurrency($transaction->getCurrency());
            $em->persist($clientFees);
            $em->flush();
        }
        if(!$clientLimits){
            $clientLimits = new SwiftLimit();
            $clientLimits->setCname($type_in.'_'.$type_out);
            $clientLimits->setSingle(0);
            $clientLimits->setDay(0);
            $clientLimits->setWeek(0);
            $clientLimits->setMonth(0);
            $clientLimits->setYear(0);
            $clientLimits->setTotal(0);
            $clientLimits->setClient($client);
            $clientLimits->setCurrency($transaction->getCurrency());
            $em->persist($clientLimits);
            $em->flush();
        }
        if(!$clientLimitsCount) {
            $clientLimitsCount = new SwiftLimitCount();
            $clientLimitsCount->setClient($client);
            $clientLimitsCount->setCname($type_in.'_'.$type_out);
            $clientLimitsCount->setSingle(0);
            $clientLimitsCount->setDay(0);
            $clientLimitsCount->setWeek(0);
            $clientLimitsCount->setMonth(0);
            $clientLimitsCount->setYear(0);
            $clientLimitsCount->setTotal(0);
            $em->persist($clientLimitsCount);
            $em->flush();
        }

        $service_fee = ($amount * ($methodFees->getVariable()/100) + $methodFees->getFixed());
        $client_fee = ($amount * ($clientFees->getVariable()/100) + $clientFees->getFixed());
        $total_fee = $client_fee + $service_fee;
        $total = round($amount + $total_fee, 0);

        //ADD AND CHECK LIMITS
        $clientLimitsCount = (new LimitAdder())->add($clientLimitsCount, $total);
        $checker = new LimitChecker();
        if(!$checker->leq($clientLimitsCount , $clientLimits))
            throw new HttpException(509,'Limit exceeded');

        $em->persist($clientLimitsCount);
        $em->flush();

        $exchange = $this->_exchange($total  , $cashOutMethod->getCurrency(), $cashInMethod->getCurrency());

        try{
            $pay_in_info = $cashInMethod->getPayInInfo($exchange);

        }catch (HttpException $e){
            $transaction->setStatus(Transaction::$STATUS_ERROR);
            $dm->persist($transaction);
            $dm->flush();
            throw new HttpException(400,'Service Temporally unavailable.');
        }

        $price = round($total/($pay_in_info['amount']/1e8),0);
        $transaction->setPrice($price);

        $transaction->setPayInInfo($pay_in_info);
        $transaction->setDataOut($pay_in_info);
        $transaction->setStatus(Transaction::$STATUS_CREATED);
        $dm->persist($transaction);
        $dm->flush();

        return $this->swiftTransaction($transaction, "Done");

    }

    public function _exchange($amount,$curr_in,$curr_out){

        $dm=$this->getDoctrine()->getManager();
        $exchangeRepo=$dm->getRepository('TelepayFinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$curr_in,'dst'=>$curr_out),
            array('id'=>'DESC')
        );

        if(!$exchange) throw new HttpException(404,'Exchange not found -> '.$curr_in.' TO '.$curr_out);

        $price = $exchange->getPrice();
        $total = round($amount * $price,0);

        return $total;

    }

}


