<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/22/15
 * Time: 8:16 PM
 */



namespace Telepay\FinancialApiBundle\Controller\Transactions;


use Symfony\Component\EventDispatcher\Tests\Service;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;

use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\FeeDeal;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\LimitAdder;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\LimitChecker;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\LimitCount;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Entity\User;

class IncomingController extends RestApiController{

    static $OLD_CNAME_ID_MAPPINGS = array(
        "sample" => 1,
        "aaa" => 3,
        "aaaa" => 4
    );

    /**
     * @Rest\View
     */
    public function make(Request $request, $version_number, $service_cname, $id = null){

        $service = $this->get('net.telepay.services.'.$service_cname.'.v'.$version_number);

        if (false === $this->get('security.authorization_checker')->isGranted($service->getRole())) {
            throw $this->createAccessDeniedException();
        }

        $dataIn = array();
        foreach($service->getFields() as $field){
            if(!$request->request->has($field))
                throw new HttpException(400, "Parameter '".$field."' not found");
            else $dataIn[$field] = $request->get($field);
        }

        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();

        $transaction = Transaction::createFromContext($this->get('transaction.context'));
        $transaction->setService($service_cname);
        $transaction->setVersion($version_number);
        $transaction->setDataIn($dataIn);
        $dm->persist($transaction);

        //TODO posible millora en un query molon
        //obtain and check limits
        $user=$this->getUser();

        //obtener group
        $group=$user->getGroups()[0];

        //obtener comissiones del grupo
        $group_commissions=$group->getCommissions();
        $group_commission=false;
        foreach ( $group_commissions as $commission ){
            if ( $commission->getServiceName() == $service_cname ){
                $group_commission = $commission;
            }
        }

        //si no existe la comission para el grupo la creamos
        if(!$group_commission){
            $group_commission = new ServiceFee();
            $group_commission->setFixed(0);
            $group_commission->setVariable(0);
            $group_commission->setServiceName($service_cname);
            $group_commission->setGroup($group);
            $em->persist($group_commission);
            $em->flush();
        }

        $amount=$dataIn['amount'];
        $transaction->setAmount($amount);

        //añadimos las comisiones para chekear
        $fixed_fee=$group_commission->getFixed();
        $variable_fee=$group_commission->getVariable()*$amount;
        $total_fee=$fixed_fee+$variable_fee;

        //incloure les fees en la transacció
        $transaction->setVariableFee($group_commission->getVariable());
        $transaction->setFixedFee($group_commission->getFixed());
        $dm->persist($transaction);

        //comprobamos si es cash out
        if($service->getcashDirection()=='out'){
            $total=$amount+$variable_fee+$fixed_fee;
            //le cambiamos el signo para guardarla i marcarla como salida en el wallet
            $transaction->setTotal($amount*-1);
        }else{
            $total=$amount-$variable_fee-$fixed_fee;
            $transaction->setTotal($amount);
        }

        $limits=$user->getLimitCount();
        $user_limit = false;
        foreach ( $limits as $limit ){
            if($limit->getCname()==$service_cname){
                $user_limit=$limit;
            }
        }

        //si el usuario no tiene limitCount se lo creamos
        if(!$user_limit){
            $user_limit = new LimitCount();
            $user_limit->setUser($user);
            $user_limit->setCname($service_cname);
            $user_limit->setSingle(0);
            $user_limit->setDay(0);
            $user_limit->setWeek(0);
            $user_limit->setMonth(0);
            $user_limit->setYear(0);
            $user_limit->setTotal(0);
            $em->persist($user_limit);
            $em->flush();
        }

        //obtener limites del grupo
        $group_limits=$group->getLimits();
        $group_limit = false;
        foreach ( $group_limits as $limit ){
            if( $limit->getCname() == $service_cname){
                $group_limit = $limit;
            }
        }

        //si no existe el limite del grupo lo creamos
        if(!$group_limit){
            $group_limit = new LimitDefinition();
            $group_limit->setCname($service_cname);
            $group_limit->setSingle(0);
            $group_limit->setDay(0);
            $group_limit->setWeek(0);
            $group_limit->setMonth(0);
            $group_limit->setYear(0);
            $group_limit->setTotal(0);
            $group_limit->setGroup($group);
            $em->persist($group_limit);
            $em->flush();
        }

        $new_user_limit=(new LimitAdder())->add($user_limit,$total);

        $checker = new LimitChecker();

        if(!$checker->leq($new_user_limit,$group_limit))
            throw new HttpException(509,'Limit exceeded');

        //obtain wallet and check founds for cash_out services
        $wallets=$user->getWallets();
        $service_currency = $service->getCurrency();
        $current_wallet=null;

        $transaction->setCurrency($service_currency);

        //comprobamos si es cash out
        if($service->getcashDirection()=='out'){

            foreach ( $wallets as $wallet){
                if ($wallet->getCurrency()==$service_currency){
                    if($wallet->getAvailable()<=$total) throw new HttpException(509,'Not founds enough');
                    //Bloqueamos la pasta en el wallet
                    $actual_available=$wallet->getAvailable();
                    $new_available=$actual_available-$total;
                    $wallet->setAvailable($new_available);
                    $em->persist($wallet);
                    $em->flush();
                    $current_wallet=$wallet;
                }
            }

            $scale=$current_wallet->getScale();
            $transaction->setScale($scale);


            try {
                $transaction = $service->create($transaction);
            }catch (HttpException $e){
                if($transaction->getStatus() === Transaction::$STATUS_CREATED)
                    $transaction->setStatus(Transaction::$STATUS_FAILED);
                $dm->persist($transaction);
                $dm->flush();
                $current_wallet->setAvailable($current_wallet->getAvailable()+$total);
                $em->persist($current_wallet);
                $em->flush();
                throw $e;
            }

            $transaction->setTimeOut(new \MongoDate());
            $dm->persist($transaction);
            $dm->flush();

            //si la transaccion se finaliza se suma al wallet i se reparten las comisiones
            if($transaction->getStatus() === Transaction::$STATUS_SUCCESS){

                //sumar al usuario el amount
                $current_wallet->setBalance($current_wallet->getBalance()-$total);
                $em->persist($current_wallet);
                $em->flush();

                //empezamos el reparto
                $creator=$group->getCreator();

                if(!$creator) throw new HttpException(404,'Creator not found');

                //hacemos el reparto
                //$this->cashInDealer($creator,$amount,$service_cname,$service_currency);
                $transaction_id=$transaction->getId();
                $dealer=$this->get('net.telepay.commons.fee_deal');
                $dealer->deal($creator,$amount,$service_cname,$service_currency,$total_fee,$transaction_id);


            }

        }else{     //cashIn

            foreach ( $wallets as $wallet){
                if ($wallet->getCurrency() === $service_currency){
                    $current_wallet=$wallet;
                }
            }

            $scale=$current_wallet->getScale();
            $transaction->setScale($scale);


            try {
                $transaction = $service->create($transaction);
            }catch (HttpException $e){
                if($transaction->getStatus() === Transaction::$STATUS_CREATED)
                    $transaction->setStatus(Transaction::$STATUS_FAILED);
                $dm->persist($transaction);
                $dm->flush();
                throw $e;
            }

            $em->flush();

            $transaction->setTimeOut(new \MongoDate());
            $dm->persist($transaction);
            $dm->flush();

            //si la transaccion se finaliza se suma al wallet i se reparten las comisiones
            if($transaction->getStatus() === Transaction::$STATUS_SUCCESS){

                //sumar al usuario el amount
                $current_wallet->setAvailable($current_wallet->getAvailable()+$total);
                $current_wallet->setBalance($current_wallet->getBalance()+$total);
                $em->persist($current_wallet);
                $em->flush();

                //empezamos el reparto
                $creator=$group->getCreator();

                if(!$creator) throw new HttpException(404,'Creator not found');

                $transaction_id=$transaction->getId();
                $dealer=$this->get('net.telepay.commons.fee_deal');
                $dealer->deal($creator,$amount,$service_cname,$service_currency,$total_fee,$transaction_id);

                //$this->cashInDealer($creator,$amount,$service_cname,$service_currency);
            }



        }

        if($transaction == false) throw new HttpException(500, "oOps, some error has occurred within the call");

        return $this->restTransaction($transaction, "Done");
    }


    /**
     * @Rest\View
     */
    public function update(Request $request, $version_number, $service_cname, $id){

        $service = $this->get('net.telepay.services.'.$service_cname.'.v'.$version_number);

        if (false === $this->get('security.authorization_checker')->isGranted($service->getRole())) {
            throw $this->createAccessDeniedException();
        }
    }

    /**
     * @Rest\View
     */
    public function check(Request $request, $version_number, $service_cname, $id){

        $service = $this->get('net.telepay.services.'.$service_cname.'.v'.$version_number);

        if (false === $this->get('security.authorization_checker')->isGranted($service->getRole())) {
            throw $this->createAccessDeniedException();
        }

        $transaction =$service
            ->getTransactionContext()
            ->getODM()
            ->getRepository('TelepayFinancialApiBundle:Transaction')
            ->find($id);

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        if($transaction->getService() != $service->getCname()) throw new HttpException(404, 'Transaction not found');
        $transaction = $service->check($transaction);
        $mongo = $this->get('doctrine_mongodb')->getManager();
        $mongo->persist($transaction);
        $mongo->flush();
        return $this->restTransaction($transaction, "Got ok");
    }


    /**
     * @Rest\View
     */
    public function find(Request $request, $version_number, $service_cname){
        $service = $this->get('net.telepay.services.'.$service_cname.'.v'.$version_number);

        if (false === $this->get('security.authorization_checker')->isGranted($service->getRole())) {
            throw $this->createAccessDeniedException();
        }

        if($request->query->has('start_time') && is_numeric($request->query->get('start_time')))
            $start_time = new \MongoDate($request->query->get('start_time'));
        else $start_time = new \MongoDate(time()-3*31*24*3600); // 3 month ago

        if($request->query->has('end_time') && is_numeric($request->query->get('end_time')))
            $end_time = new \MongoDate($request->query->get('end_time'));
        else $end_time = new \MongoDate(); // now

        if($request->query->has('limit')) $limit = intval($request->query->get('limit'));
        else $limit = 10;

        if($request->query->has('offset')) $offset = intval($request->query->get('offset'));
        else $offset = 0;

        $userId = $this->get('security.context')->getToken()->getUser()->getId();

        $dm = $this->get('doctrine_mongodb')->getManager();

        $transactions = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('user')->equals($userId)
            ->field('service')->equals($service->getCname())
            ->field('timeIn')->gt($start_time)
            ->field('timeIn')->lt($end_time)
            ->sort('timeIn', 'desc')
            ->skip($offset)
            ->limit($limit)
            ->getQuery()->execute();

        $transArray = [];
        foreach($transactions->toArray() as $transaction){
            $transArray []= $transaction;
        }

        if(array_key_exists($service->getCname(),static::$OLD_CNAME_ID_MAPPINGS)) {
            $transactionsOld = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('user')->equals($userId)
                ->field('service')->equals(static::$OLD_CNAME_ID_MAPPINGS[$service->getCname()])
                ->field('timeIn')->gt($start_time)
                ->field('timeIn')->lt($end_time)
                ->sort('timeIn', 'desc')
                ->skip($offset)
                ->limit($limit)
                ->getQuery()->execute();
            foreach($transactionsOld->toArray() as $transaction){
                $transArray []= $transaction;
            }
        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            $transArray
        );
    }

    /**
     * @Rest\View
     */
    public function notificate(Request $request, $version_number, $service_cname, $id) {

        $service = $this->get('net.telepay.services.'.$service_cname.'.v'.$version_number);

        $transaction =$service
            ->getTransactionContext()
            ->getODM()
            ->getRepository('TelepayFinancialApiBundle:Transaction')
            ->find($id);

        if(!$transaction) throw new HttpException(404, 'Transaction not found');

        if($transaction->getService() != $service->getCname()) throw new HttpException(404, 'Transaction not found');

        $transaction = $service->notificate($transaction, $request->request->all());

        if(!$transaction) throw new HttpException(500, "oOps, the notification failed");

        return $this->restV2(200, "ok", "Notification successful");
    }
}


