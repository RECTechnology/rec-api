<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Financial\Currency;

class ExchangeManipulator{
    private $doctrine;
    private $container;
    private $trans_logger;

    public function __construct($doctrine, $container){
        $this->doctrine = $doctrine;
        $this->container = $container;
        $this->trans_logger = $this->container->get('transaction.logger');
    }

    /**
     * User
     * Transaction amount (+/-)
     * Transaction
     */

    public function exchange($amount, $currency_in, $currency_out){

        $this->trans_logger->info('EXCHANGE_MANIPULATOR (exchange)=> amount='.$amount.' cur_in='.$currency_in.' cur_out='.$currency_out);
        $em = $this->doctrine->getManager();

        $exchange = $em->getRepository('FinancialApiBundle:Exchange')->findOneBy(
            array(
                'src'   =>  strtoupper($currency_in),
                'dst'   =>  strtoupper($currency_out)
            ),
            array('id'  =>  'DESC')
        );

        $amount = round($amount * $exchange->getPrice(),0);

        $this->trans_logger->info('EXCHANGE_MANIPULATOR (exchange)=>  exchange_amount='.$amount. ' '.$currency_out.' price='.$exchange->getPrice());

        return $amount;

    }

    public function exchangeInverse($amount, $currency_in, $currency_out){

        $this->trans_logger->info('EXCHANGE_MANIPULATOR (exchange)=> amount='.$amount.' cur_in='.$currency_in.' cur_out'.$currency_out);
        $em = $this->doctrine->getManager();

        $exchange = $em->getRepository('FinancialApiBundle:Exchange')->findOneBy(
            array(
                'src'   =>  strtoupper($currency_out),
                'dst'   =>  strtoupper($currency_in)
            ),
            array('id'  =>  'DESC')
        );

        $price = 1.0/($exchange->getPrice());
        $total = round($amount * $price, 0);

        $this->trans_logger->info('EXCHANGE_MANIPULATOR (exchange)=>  exchange_amount='.$total. ' '.$currency_out.' price='.$price);

        return $total;

    }

    public function getPrice($currency_in, $currency_out){
        $this->trans_logger->info('EXCHANGE_MANIPULATOR (getPrice)=> cur_in='.$currency_in.' cur_out'.$currency_out);
        $em = $this->doctrine->getManager();

        $exchange = $em->getRepository('FinancialApiBundle:Exchange')->findOneBy(
            array(
                'src'   =>  strtoupper($currency_in),
                'dst'   =>  strtoupper($currency_out)
            ),
            array('id'  =>  'DESC')
        );

        return $exchange->getPrice();
    }

    //TODO check if is used
    public function doExchange($amount, $from, $to, Group $company, User $user, $internal = false){
        $this->trans_logger->info('EXCHANGE_MANIPULATOR (doExchange)=> amount='.$amount.' from'.$from.' to='.$to);

        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $em = $this->doctrine->getManager();

        if($from == Currency::$FAC && $company->getPremium() == true){
            $price = $this->getPrice('FAIRP', $to);
            $exchangeAmount = $this->exchange($amount, 'FAIRP', $to);
        }
        else if($to == Currency::$FAC && $company->getPremium() == true){
            $price = $this->getPrice($from, 'FAIRP');
            $exchangeAmount = $this->exchange($amount, $from, 'FAIRP');
        }
        else{
            $price = $this->getPrice($from, $to);
            $exchangeAmount = $this->exchange($amount, $from, $to);
        }

        //check exchange limits
        $this->container->get('net.app.commons.limit_manipulator')->checkExchangeLimits($company, $amount, $exchangeAmount, $from, $to);

        $params = array(
            'amount'    => 0,
            'from'  =>  $from,
            'to'    => $to
        );

        $service = 'exchange'.'_'.$from.'to'.$to;

        //checkWallet sender
        $senderWallet = $company->getWallet($from);
        $receiverWallet = $company->getWallet($to);

        if($senderWallet->getAvailable() < $amount) throw new HttpException(403, 'Insuficient funds');
        //getFees
        $fee = $company->getCommission($service);
        $exchange_fixed_fee = $fee->getFixed();
        $exchange_variable_fee = round((($fee->getVariable()/100) * $exchangeAmount), 0);

        $botc_exchange = $this->container->getParameter('default_company_exchange_botc');
        if(!$internal && ($company->getFairtoearthAdmin() || $company->getId() == $botc_exchange) && ($from == Currency::$FAC || $to == Currency::$FAC)){
            throw new HttpException(403, 'Exchange not allowed for this company');
        }

        $notificator = $this->getContainer()->get('com.qbitartofacts.rec.commons.notificator');
        $response=$notificator->msg('#EXCHANGE (doExchange' .$company->getName().')=> amount='. $amount . 'from'  . $from .' to=' . $to);

        $this->trans_logger->info('NOTIFICATOR  RESPONSE '.$response);

        $totalExchangeFee = $exchange_fixed_fee + $exchange_variable_fee;

        //cashOut transaction BTC
        $cashOut = new Transaction();
        $cashOut->setIp('');
        $cashOut->setNotificationTries(0);
        $cashOut->setMaxNotificationTries(3);
        $cashOut->setNotified(false);
        $cashOut->setAmount($amount);
        $cashOut->setCurrency($from);
        $cashOut->setDataIn($params);
        $cashOut->setFixedFee(0);
        $cashOut->setVariableFee(0);
        $cashOut->setTotal(-$amount);
        $cashOut->setType(Transaction::$TYPE_EXCHANGE);
        $cashOut->setMethod($service);
        $cashOut->setService($service);
        $cashOut->setUser($user->getId());
        $cashOut->setGroup($company->getId());
        $cashOut->setVersion(1);
        $cashOut->setScale($senderWallet->getScale());
        $cashOut->setStatus(Transaction::$STATUS_SUCCESS);
        $cashOut->setPayOutInfo(array(
            'amount'    =>  $amount,
            'currency'  =>  $from,
            'scale'     =>  $senderWallet->getScale(),
            'concept'   =>  'Exchange '.$from.' to '.$to,
            'price'     =>  $price,
        ));

        $dm->persist($cashOut);
        $dm->flush();

        $paramsOut = $params;
        $paramsOut['amount'] = $exchangeAmount;
        //cashIn transaction EUR
        $cashIn = new Transaction();
        $cashIn->setIp('');
        $cashIn->setNotificationTries(0);
        $cashIn->setMaxNotificationTries(3);
        $cashIn->setNotified(false);
        $cashIn->setAmount($exchangeAmount);
        $cashIn->setCurrency($to);
        $cashIn->setFixedFee($exchange_fixed_fee);
        $cashIn->setVariableFee($exchange_variable_fee);
        $cashIn->setTotal($exchangeAmount);
        $cashIn->setService($service);
        $cashIn->setType(Transaction::$TYPE_EXCHANGE);
        $cashIn->setMethod($service);
        $cashIn->setUser($user->getId());
        $cashIn->setGroup($company->getId());
        $cashIn->setVersion(1);
        $cashIn->setScale($receiverWallet->getScale());
        $cashIn->setStatus(Transaction::$STATUS_SUCCESS);
        $cashIn->setDataIn($paramsOut);
        $cashIn->setPayInInfo(array(
            'amount'    =>  $exchangeAmount,
            'currency'  =>  $to,
            'scale'     =>  $receiverWallet->getScale(),
            'concept'   =>  'Exchange '.$from.' to '.$to,
            'price'     =>  $price,
        ));

        $dm->persist($cashIn);
        $dm->flush();

        //update wallets
        $senderWallet->setAvailable($senderWallet->getAvailable() - $amount);
        $senderWallet->setBalance($senderWallet->getBalance() - $amount);

        $receiverWallet->setAvailable($receiverWallet->getAvailable() + $exchangeAmount);
        $receiverWallet->setBalance($receiverWallet->getBalance() + $exchangeAmount);

        //inject balancer
        $balancer = $this->container->get('net.app.commons.balance_manipulator');
        //rest balance
        $balancer->addBalance($company,-$amount, $cashOut, "exch manipulator 1");
        //add balance
        $balancer->addBalance($company, $exchangeAmount, $cashIn, "exch manipulator 2");

        $em->persist($senderWallet);
        $em->persist($receiverWallet);
        $em->flush();

        //TODO dealer
        if( $totalExchangeFee != 0){
            //nueva transaccion restando la comision al user
            $dealer = $this->container->get('net.app.commons.fee_deal');
            try{
                $dealer->createFees2($cashIn, $receiverWallet);
            }catch (HttpException $e){
                throw $e;
            }
        }

        //notification
        $this->container->get('notificator')->notificate($cashIn);

        $paymentInfo = array(
            'from'  =>  $from,
            'to'    =>  $to,
            'sent'    =>  $amount,
            'received'  =>  $exchangeAmount,
            'price' =>  round($price,$receiverWallet->getScale())

        );

        return $paymentInfo;

    }





    public function exchangeWallet(UserWallet $wallet, $currency){
        $this->trans_logger->info('EXCHANGE_MANIPULATOR (exchangeWallet)=> currency='.$currency);
        $currency_actual = $wallet->getCurrency();
        if($currency_actual == $currency){
            $response['available'] = $wallet->getAvailable();
            $response['balance'] = $wallet->getBalance();
            $response['scale'] = $wallet->getScale();
            return $response;
        }

        $price = $this->getPrice($wallet->getCurrency(), $currency);

        $response['available'] = round($wallet->getAvailable() * $price, 0);
        $response['balance'] = round($wallet->getBalance() * $price,0);
        $response['scale'] = Currency::$SCALE[$currency];

        return $response;

    }

}