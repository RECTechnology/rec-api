<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\User;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

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

        $this->trans_logger->info('EXCHANGE_MANIPULATOR (exchange)=> amount='.$amount.' cur_in='.$currency_in.' cur_out'.$currency_out);
        $em = $this->doctrine->getManager();

        $exchange = $em->getRepository('TelepayFinancialApiBundle:Exchange')->findOneBy(
            array(
                'src'   =>  strtoupper($currency_in),
                'dst'   =>  strtoupper($currency_out)
            ),
            array('id'  =>  'DESC')
        );

        $amount = round($amount * $exchange->getPrice(),0);

        return $amount;

    }

    public function getPrice($currency_in, $currency_out){
        $this->trans_logger->info('EXCHANGE_MANIPULATOR (getPrice)=> cur_in='.$currency_in.' cur_out'.$currency_out);
        $em = $this->doctrine->getManager();

        $exchange = $em->getRepository('TelepayFinancialApiBundle:Exchange')->findOneBy(
            array(
                'src'   =>  strtoupper($currency_in),
                'dst'   =>  strtoupper($currency_out)
            ),
            array('id'  =>  'DESC')
        );

        return $exchange->getPrice();
    }

    public function doExchange($amount, $from, $to, Group $company, User $user){
        $this->trans_logger->info('EXCHANGE_MANIPULATOR (doExchange)=> amount='.$amount.' from'.$from.' to='.$to);
        $dm = $this->container->get('doctrine_mongodb')->getManager();
        $em = $this->doctrine->getManager();

        $exchangeAmount = $this->exchange($amount, $from, $to);
        $params = array(
            'amount'    => 0,
            'from'  =>  $from,
            'to'    => $to
        );

        $service = 'exchange'.'_'.$from.'to'.$to;

        //checkWallet sender
        $senderWallet = $company->getWallet($from);
        $receiverWallet = $company->getWallet($to);

        //getFees
        $fees = $company->getCommissions();

        $exchange_fixed_fee = null;
        $exchange_variable_fee = null;

        foreach($fees as $fee){
            if($fee->getServiceName() == $service){
                $exchange_fixed_fee = $fee->getFixed();
                $exchange_variable_fee = round((($fee->getVariable()/100) * $exchangeAmount), 0);
            }
        }

        $price = $this->getPrice($from, $to);

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
        $cashOut->setType('out');
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
        $cashIn->setType('in');
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

        $receiverWallet->setAvailable($receiverWallet->getAvailable() + $exchangeAmount - $exchange_fixed_fee - $exchange_variable_fee);
        $receiverWallet->setBalance($receiverWallet->getBalance() + $exchangeAmount - $exchange_fixed_fee - $exchange_variable_fee);

        $em->persist($senderWallet);
        $em->persist($receiverWallet);
        $em->flush();

        //TODO dealer
        if( $totalExchangeFee != 0){
            //nueva transaccion restando la comision al user
            $dealer = $this->container->get('net.telepay.commons.fee_deal');
            try{
                $dealer->createFees($cashIn, $receiverWallet);
            }catch (HttpException $e){
                throw $e;
            }
        }

        //notification
        $this->container->get('notificator')->notificate($cashIn);

        return true;

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