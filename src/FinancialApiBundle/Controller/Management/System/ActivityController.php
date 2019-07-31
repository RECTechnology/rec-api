<?php

namespace App\FinancialApiBundle\Controller\Management\System;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\CashInDeposit;
use App\FinancialApiBundle\Entity\InternalBalance;
use App\FinancialApiBundle\Financial\Currency;

/**
 * Class ActivityController
 * @package App\FinancialApiBundle\Controller\Management\System
 */
class ActivityController extends RestApiController
{

    /**
     * @Rest\View
     */
    public function last50Transactions() {

        $dm = $this->get('doctrine_mongodb')->getManager();
        $last50Trans = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
            ->limit(50)
            ->sort('updated','desc')
            ->sort('id','desc')
            ->getQuery()
            ->execute();

        $resArray = [];

        $em = $this->getDoctrine()->getManager();
        $groupRepo = $em->getRepository('FinancialApiBundle:Group');
        foreach($last50Trans->toArray() as $res){
            if($res->getGroup()){
                $group = $groupRepo->find($res->getGroup());
                if($group){
                    $res->setGroupData($group->getName());
                }
            }


            $resArray [] = $res;

        }

        return $this->restV2(200, "ok", "Last 10 transactions got successfully", $resArray);
    }

    /**
     * @Rest\View
     */

    public function totalWallets(){
        $dm = $this->getDoctrine()->getManager();
        $groupRepo = $dm->getRepository('FinancialApiBundle:Group');
        $groups = $groupRepo->findBy(
            array('own'=>true)
        );
        $chipchap_groups = array();
        foreach($groups as $group){
            $chipchap_groups[] = $group->getId();
        }

        $qb = $this->getDoctrine()->getRepository('FinancialApiBundle:UserWallet')->createQueryBuilder('w');
        $qb->Select('SUM(w.available) as available, SUM(w.balance) as balance, w.currency')
            ->where('w.group NOT IN (:groups)')
            ->setParameter('groups', $chipchap_groups)
            ->groupBy('w.currency');

        $query = $qb->getQuery()->getResult();

        //montamos el wallet
        $multidivisa = [];
        $multidivisa['id'] = 'multidivisa';
        $multidivisa['currency'] = 'EUR';
        $multidivisa['available'] = 0;
        $multidivisa['balance'] = 0;
        $multidivisa['scale'] = 2;

        $filtered = [];

        foreach($query as $wallet){
            $wallet['id'] = $wallet['currency'];
            $wallet['available'] = round($wallet['available'],0);
            $wallet['balance'] = round($wallet['balance'],0);
            $wallet['scale'] = Currency::$SCALE[$wallet['currency']];
            $filtered[] = $wallet;
            if($wallet['currency'] != 'EUR'){
                $multidivisa['available'] = $multidivisa['available'] + $this->exchange($wallet['available'], $wallet['currency'], 'EUR');
                $multidivisa['balance'] = $multidivisa['balance'] + $this->exchange($wallet['balance'], $wallet['currency'], 'EUR');
            }else{
                $multidivisa['available'] = $multidivisa['available'] + $wallet['available'];
                $multidivisa['balance'] = $multidivisa['balance'] + $wallet['balance'];
            }

        }

        $filtered[] = $multidivisa;

        return $this->restV2(200, "ok", "Total wallet info got successfully", $filtered);

    }

    /**
     * makes an exchange between currencies in the wallet
     */
    private function exchange($amount, $src, $dst){

        $dm = $this->getDoctrine()->getManager();
        $exchangeRepo = $dm->getRepository('FinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$src,'dst'=>$dst),
            array('id'=>'DESC')
        );

        if(!$exchange) throw new HttpException(404,'Exchange not found');

        $price = $exchange->getPrice();

        $response = $amount * $price;

//        die(print_r($amount.' - '.$src.' - '.$dst.' - '.$price.' - '.$response,true));

        return round($response,0);

    }

    /**
     * @Rest\View
     */
    public function searchBalanceByCompany(Request $request, $id){

        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('FinancialApiBundle:Group')->find($id);
        $balances = $em->getRepository('FinancialApiBundle:Balance')->findBy(array(
            'group'   =>  $company
        ));

        foreach ($balances as $balance){
            $balance->setScale(Currency::$SCALE[$balance->getCurrency()]);
        }

        return $this->restV2(200,"ok", "Request successful", $balances);

    }

    /**
     * @Rest\View
     */
    public function walletByCompany(Request $request, $id){

        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('FinancialApiBundle:Group')->find($id);

        //obtener los wallets
        $wallets = $company->getWallets();

        //obtenemos la default currency
        $currency = $company->getDefaultCurrency();

        $filtered = [];
        $available = 0;
        $balance = 0;
        $scale = 0;
        $exchanger = $this->container->get('net.app.commons.exchange_manipulator');

        foreach($wallets as $wallet){
            $filtered[] = $wallet->getWalletView();
            if($company->getPremium()){
                if($wallet->getCurrency() == Currency::$FAC){
                    $wallet->setCurrency('FAIRP');
                }elseif($currency == Currency::$FAC){
                    $currency = 'FAIRP';
                }
            }
            $new_wallet = $exchanger->exchangeWallet($wallet, $currency);
            $available = round($available + $new_wallet['available'],0);
            $balance = round($balance + $new_wallet['balance'],0);
            if($new_wallet['scale'] != null) $scale = $new_wallet['scale'];
        }

        //montamos el wallet
        $multidivisa = [];
        $multidivisa['id'] = 'multidivisa';
        $multidivisa['currency'] = $currency;
        $multidivisa['available'] = $available;
        $multidivisa['balance'] = $balance;
        $multidivisa['scale'] = $scale;
        $filtered[] = $multidivisa;

        //return $this->rest(201, "Account info got successfully", $filtered);
        return $this->restV2(200, "ok", "Wallet info got successfully", $filtered);
    }

    /**
     * @Rest\View
     */
    public function setBalance(Request $request, $service){

        $logger = $this->get('manager.logger');
        if(!$request->request->has('available')) throw new HttpException('Available param not found');
        if(!$request->request->has('currency')) throw new HttpException('currency param not found');
        if(!$request->request->has('scale')) throw new HttpException('scale param not found');

        $available = $request->request->get('available');
        $currency = strtoupper($request->request->get('currency'));
        $scale = $request->request->get('scale');

        $logger->info('InternalBalance => Service '.$service.' Available '.$available);

        //search last balance, if not the same create and send telegram
        $em = $this->getDoctrine()->getManager();
        $internalBalance = $em->getRepository('FinancialApiBundle:InternalBalance')->findOneBy(
            array(
                'node'  =>  strtoupper($service),
                'currency'  =>  strtoupper($currency)
            ),
            array(
                'date'  =>  'DESC'
            )
        );

        if(!$internalBalance){
            $logger->info('InternalBalance => Creating first balance');
            $internalBalance = new InternalBalance();
            $internalBalance->setBalance(0);
            $internalBalance->setCurrency(strtoupper($currency));
            $internalBalance->setNode(strtoupper($service));
            $internalBalance->setScale($scale);
            $em->persist($internalBalance);
            $em->flush();

        }

        $balance = $internalBalance->getBalance();

        if($balance != $available){
            $logger->info('InternalBalance => New Balance detected');
            $newBalance = new InternalBalance();
            $newBalance->setScale($scale);
            $newBalance->setNode(strtoupper($service));
            $newBalance->setCurrency(strtoupper($currency));
            $newBalance->setBalance($available);

            $em->persist($newBalance);
            $em->flush();

            if($scale == 2){
                $availableAmount = $available/100;
            }else{
                $availableAmount = $available/100000000;
            }
            $logger->info('InternalBalance => Send telegram');

            //exec('curl -X POST -d "chat_id=-145386290&text=#balance_'.$service.' '.$availableAmount.' '.$currency.'" "https://api.telegram.org/bot348257911:AAG9z3cJnDi31-7MBsznurN-KZx6Ho_X4ao/sendMessage"');

        }

        return $this->restV2(200,'Success',$service.' Request successfull', array());
    }
}
