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

        $transaction = Transaction::createFromContext($this->get('transaction.context'));
        $transaction->setService($service_cname);
        $transaction->setVersion($version_number);
        $transaction->setStatus("created");
        $transaction->setDataIn($dataIn);
        $this->get('doctrine_mongodb')->getManager()->persist($transaction);

        //TODO posible millora en un query molon
        //TODO comprobar limites
        //obtener limit usuario
        $user=$this->getUser();


        $limits=$user->getLimitCount();
        foreach ( $limits as $limit ){
            if($limit->getCname()==$service_cname){

                //TODO implementar un limitAdder
                $user_limit=$limit;
            }
        }

        $em = $this->getDoctrine()->getManager();

        if(!$user_limit){
            //TODO crear el limite
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

        //obtener limit group
        $group=$user->getGroups()[0];

        $group_limits=$group->getLimits();
        foreach ( $group_limits as $limit ){
            if($limit->getCname()==$service_cname){
                $group_limit=$limit;
            }
        }

        if(!$group_limit){
            //TODO crear el limite
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

        $amount=$dataIn['amount'];

        $new_user_limit = new LimitAdder();
        $new_user_limit->add($user_limit,$amount);


        $checker = new LimitChecker();
        $checker = $checker->leq($new_user_limit,$group_limit);

        if($checker==false) throw new HttpException(509,'Limit exceeded');

        try {
            $transaction = $service->create($transaction);
        }catch (HttpException $e){
            if($transaction->getStatus() === "created")
                $transaction->setStatus("failed");
            $dm->persist($transaction);
            $dm->flush();
            throw $e;
        }

        $transaction->setTimeOut(new \MongoDate());
        $dm->persist($transaction);
        $dm->flush();

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
        $this->get('doctrine_mongodb')->getManager()->persist($transaction);
        $this->get('doctrine_mongodb')->getManager()->flush();
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


