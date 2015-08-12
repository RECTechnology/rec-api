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

        $em = $this->getDoctrine()->getManager();
        $tpvRepo = $em->getRepository('TelepayFinancialApiBundle:POS')->findOneBy(array(
            'pos_id'    =>  $id
        ));

        $service_cname = $tpvRepo->getCname();

        $user = $tpvRepo->getUser();

        $service_currency = strtoupper($tpvRepo->getCurrency());

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

        if($dataIn['currency'] != $service_currency) throw new HttpException(403, 'Currency not allowed');

        $dm = $this->get('doctrine_mongodb')->getManager();

        //Check unique order_id by user and tpv
        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('posId')->equals($id)
            ->field('user')->equals($user->getId())
            ->field('dataIn.order_id')->equals($dataIn['order_id'])
            ->getQuery();

        if( count($qb) > 0 ) throw new HttpException(409,'Duplicated resource');

        //create transaction
        $transaction = Transaction::createFromRequest($request);
        $transaction->setService($service_cname);
        $transaction->setUser($user->getId());
        $transaction->setVersion($version_number);
        $transaction->setDataIn($dataIn);
        $transaction->setPosId($id);
        $dm->persist($transaction);
        $amount = $dataIn['amount'];
        $transaction->setAmount($amount);

        //add commissions to check
        $fixed_fee = 0;
        $variable_fee = 0;
        $total_fee = $fixed_fee + $variable_fee;

        //add fee to transaction
        $transaction->setVariableFee($variable_fee);
        $transaction->setFixedFee($fixed_fee);
        $dm->persist($transaction);

        $total = $amount - $variable_fee - $fixed_fee;
        $transaction->setTotal($amount);

        //obtain wallet and check founds for cash_out services
        $wallets = $user->getWallets();

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
                $current_wallet = $wallet;
            }
        }

        $scale = $current_wallet->getScale();
        $transaction->setScale($scale);

        $transaction->setUpdated(new \DateTime());

        $dm->persist($transaction);
        $dm->flush();

        if($transaction == false) throw new HttpException(500, "oOps, some error has occurred within the call");

        return $this->restTransaction($transaction, "Done");
    }

    /**
     * @Rest\View
     */
    public function generateAddress(){

        $address = $this->container->get('net.telepay.provider.btc')->getnewaddress();

        return $address;

    }

    /**
     * @Rest\View
     */
    public function checkTransaction(Request $request, $id){

        $em = $this->get('doctrine_mongodb')->getManager();
        $transaction = $em->getRepository('TelepayFinancialApiBundle:Transaction')->find($id);

        return $this->restTransaction($transaction, "Got ok");

    }

    /**
     * @Rest\View
     */
    public function find(Request $request, $version_number, $pos_id){

        $service = $this->get('net.telepay.services.pos.v'.$version_number);

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
            ->field('posId')->equals($pos_id)
            ->field('created')->gt($start_time)
            ->field('created')->lt($end_time)
            ->sort('created', 'desc')
            ->skip($offset)
            ->limit($limit)
            ->getQuery()->execute();

        $transArray = [];
        foreach($transactions->toArray() as $transaction){
            $transArray []= $transaction;
        }

        //esto es asi porque hemos cambiado la respuesta en restV2 ( ahora tiene algunos campos más ).
        return $this->restV2(
            200,
            "ok",
            "Request successful",
            $transArray
        );
    }

    public function notificate(Request $request, $id){

        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->find($id);

        if(!$transaction) throw new HttpException(400,'Transaction not found');

        if($transaction->getNotified() == true) throw new HttpException(409,'Duplicate notification');
        
        $status = $request->request->get('status');

        if ($status == 1){
            //set transaction cancelled
            $transaction->setStatus('success');
        }else{
            //set transaction success
            $transaction->setStatus('cancelled');
        }

        $transaction->setUpdated(new \MongoDate());

        $dm->persist($transaction);
        $dm->flush();

        $transaction = $this->get('notificator')->notificate($transaction);

        return $this->restV2(200, "ok", "Notification successful");


    }


}


