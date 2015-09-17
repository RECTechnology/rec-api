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

        //POS is not a service, omly needs a role commerce
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_COMMERCE')) {
            throw $this->createAccessDeniedException();
        }

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        $dm = $this->get('doctrine_mongodb')->getManager();
        $userId = $this->get('security.context')
            ->getToken()->getUser()->getId();

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');

        if($request->query->get('query') != ''){
            $query = $request->query->get('query');
            $search = $query['search'];
            $order = $query['order'];
            $dir = $query['dir'];
            $start_time = new \MongoDate(strtotime(date($query['start_date'].' 00:00:00')));//date('Y-m-d 00:00:00')
            $finish_time = new \MongoDate(strtotime(date($query['finish_date'].' 23:59:59')));

            $transactions = $qb
                ->field('user')->equals($userId)
                ->field('service')->equals($service->getCname())
                ->field('posId')->equals($pos_id)
                ->field('created')->gte($start_time)
                ->field('created')->lte($finish_time)
                ->where("function() {
            if (typeof this.dataIn !== 'undefined') {
                if (typeof this.dataIn.order_id !== 'undefined') {
                    if(String(this.dataIn.order_id).indexOf('$search') > -1){
                        return true;
                    }
                }
                if (typeof this.dataIn.description !== 'undefined') {
                    if(String(this.dataIn.description).indexOf('$search') > -1){
                        return true;
                    }
                }

            }
            if (typeof this.dataOut !== 'undefined') {
                if (typeof this.dataOut.transaction_pos_id !== 'undefined') {
                    if(String(this.dataOut.transaction_pos_id).indexOf('$search') > -1){
                        return true;
                    }
                }

            }
            if(typeof this.status !== 'undefined' && String(this.status).indexOf('$search') > -1){ return true;}
            if(typeof this.amount !== 'undefined' && String(this.amount).indexOf('$search') > -1){ return true;}
            if(String(this._id).indexOf('$search') > -1){ return true;}

            return false;
            }")
                ->sort($order,$dir)
                ->getQuery()
                ->execute();

        }else{
            $order = "id";
            $dir = "desc";

            $transactions = $qb
                ->field('user')->equals($userId)
                ->field('service')->equals($service->getCname())
                ->field('posId')->equals($pos_id)
                ->sort($order,$dir)
                ->getQuery()
                ->execute();
        }
        $resArray = [];
        foreach($transactions->toArray() as $res){
            $resArray []= $res;

        }

        $total = count($resArray);

        $page_amount = 0;
        $total_amount = 0;

        foreach ($resArray as $array){
            if($array->getStatus() == 'success'){
                $total_amount = $total_amount + $array->getAmount();
            }
        }

        $entities = array_slice($resArray, $offset, $limit);

        foreach ($entities as $ent){
            if($ent->getStatus() == 'success'){
                $page_amount = $page_amount + $ent->getAmount();
            }
        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'start' => intval($offset),
                'end' => count($entities)+$offset,
                'elements' => $entities,
                'page_amount' => $page_amount,
                'total_amount' => $total_amount
            )
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


