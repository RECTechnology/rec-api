<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 6/24/15
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
use Telepay\FinancialApiBundle\Entity\Balance;
use Telepay\FinancialApiBundle\Entity\LimitCount;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Entity\User;
use Telepay\FinancialApiBundle\Entity\UserWallet;

class POSIncomingController extends RestApiController{

    /**
     * @Rest\View
     */
    public function createTransaction(Request $request, $version_number,  $id){

        //TODO con el id obtenemos la informacion de la tpv
        $em = $this->getDoctrine()->getManager();
        $tpvRepo = $em->getRepository('TelepayFinancialApiBundle:POS')->find($id);

        $service_cname = $tpvRepo->getCname();

        $user = $tpvRepo->getUser();

        $service = $this->get('net.telepay.services.'.$service_cname.'.v'.$version_number);

        if (false === $user->hasRole($service->getRole())) {
            throw $this->createAccessDeniedException();
        }

        $dataIn = array();
        foreach($service->getFields() as $field){
            if(!$request->request->has($field))
                throw new HttpException(400, "Parameter '".$field."' not found");
            else $dataIn[$field] = $request->get($field);
        }

        //si en service->getFields no esta url_notification y si esta en el request lo añadimos al data in
        if(!isset($service->getFields()['url_notification']) && $request->request->has('url_notification')){
            $dataIn['url_notification'] = $request->request->get('url_notification');
        }

        if($request->request->has('sms_language')){
            $dataIn['sms_language']=$request->request->get('sms_language');
        }

        $concept = '';
        if($request->request->has('description')) $concept = $request->request->get('description');
        if($request->request->has('concept')) $concept = $request->request->get('concept');
        if($request->request->has('reference')) $concept = $request->request->get('reference');

        $dataIn['description'] = $concept;

        $dm = $this->get('doctrine_mongodb')->getManager();

        $transaction = Transaction::createFromRequest($request);
        $transaction->setService($service_cname);
        $transaction->setUser($user->getId());
        $transaction->setVersion($version_number);
        $transaction->setDataIn($dataIn);
        $dm->persist($transaction);

        //TODO posible millora en un query molon
        //obtain and check limits

        //obtener group
        $group = $user->getGroups()[0];

        //obtener comissiones del grupo
        $group_commissions=$group->getCommissions();
        $group_commission=false;
        foreach ( $group_commissions as $commission ){
            if ( $commission->getServiceName() == $service_cname ){
                $group_commission = $commission;
            }
        }

        //if group commission not exists we create it
        if(!$group_commission){
            $group_commission = ServiceFee::createFromController($service_cname, $group);
            $em->persist($group_commission);
            $em->flush();
        }

        $amount=$dataIn['amount'];
        $transaction->setAmount($amount);

        //add commissions to check
        $fixed_fee = $group_commission->getFixed();
        $variable_fee = $group_commission->getVariable()*$amount;
        $total_fee = $fixed_fee + $variable_fee;

        //add fee to transaction
        $transaction->setVariableFee($variable_fee);
        $transaction->setFixedFee($fixed_fee);
        $dm->persist($transaction);


        $total = $amount - $variable_fee - $fixed_fee;
        $transaction->setTotal($amount);


        //obtain user limits
        $limits = $user->getLimitCount();
        $user_limit = false;
        foreach ( $limits as $limit ){
            if($limit->getCname() == $service_cname){
                $user_limit=$limit;
            }
        }

        //if user hasn't limit create it
        if(!$user_limit){
            $user_limit = LimitCount::createFromController($service_cname, $user);
            $em->persist($user_limit);
            $em->flush();
        }

        //obtain group limit
        $group_limits=$group->getLimits();
        $group_limit = false;
        foreach ( $group_limits as $limit ){
            if( $limit->getCname() == $service_cname){
                $group_limit = $limit;
            }
        }

        //if limit doesn't exist create it
        if(!$group_limit){
            $group_limit = LimitDefinition::createFromController($service_cname, $group);
            $em->persist($group_limit);
            $em->flush();
        }

        $new_user_limit = (new LimitAdder())->add($user_limit, $total);

        $checker = new LimitChecker();

        if(!$checker->leq($new_user_limit, $group_limit))
            throw new HttpException(509,'Limit exceeded');

        //obtain wallet and check founds for cash_out services
        $wallets = $user->getWallets();

        //TODO check tpv currency
        //check if the service is halcash because we have various currencys
        if($service_cname == 'halcash_send'){
            if(isset($dataIn) && $dataIn['country'] == 'PL'){
                $service_currency = 'PLN';
            }else{
                $service_currency = $service->getCurrency();
            }

        }else{
            $service_currency = $service->getCurrency();
        }

        $current_wallet = null;

        $transaction->setCurrency($service_currency);

       //CASH - IN

        try {
            $transaction = $service->create($transaction);
        }catch (HttpException $e){
            if($transaction->getStatus() === Transaction::$STATUS_CREATED)
                $transaction->setStatus(Transaction::$STATUS_FAILED);
            $this->container->get('notificator')->notificate($transaction);
            $dm->persist($transaction);
            $dm->flush();
            throw $e;
        }

        $transaction = $this->get('notificator')->notificate($transaction);
        $em->flush();

        foreach ( $wallets as $wallet){
            if ($wallet->getCurrency() === $transaction->getCurrency()){
                $current_wallet=$wallet;
            }
        }

        $scale=$current_wallet->getScale();
        $transaction->setScale($scale);


        $transaction->setUpdated(new \DateTime());

        $dm->persist($transaction);
        $dm->flush();

        if($transaction == false) throw new HttpException(500, "oOps, some error has occurred within the call");

        return $this->restTransaction($transaction, "Done");
    }


}


