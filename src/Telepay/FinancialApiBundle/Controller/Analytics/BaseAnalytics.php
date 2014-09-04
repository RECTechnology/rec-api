<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/28/14
 * Time: 6:36 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Analytics;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;

abstract class BaseAnalytics extends RestApiController{

    public abstract function getServiceName();

    public function transactions(Request $request, $mode = true) {

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        if($request->query->has('start_time')) $start_time = $request->query->get('start_time');
        else $start_time = 10;

        if($request->query->has('end_time')) $end_time = $request->query->get('end_time');
        else $end_time = 0;

        $transactionsRepo = $this->get('doctrine_mongodb')
            ->getManager()
            ->getRepository('TelepayFinancialApiBundle:Transaction');

        $transactions = $transactionsRepo->findBy(
            array(
                'user' => $this->get('security.context')
                        ->getToken()->getUser()->getId(),
                'service' => $this->get('telepay.services')
                        ->findByName($this->getServiceName())->getId(),
                'mode'=> $mode
            ),
            array('timeIn'=>'DESC'),
            $limit,
            $offset
        );

        return $this->handleRestView(
            200,
            "Request successful",
            $transactions
        );
    }

     public function transactionsTest(Request $request, $mode = false) {
        return $this->transactions($request, $mode);
     }


    public function stats(Request $request) {

        if($request->query->has('start_time') && is_int($request->query->get('start_time')))
            $start_time = new \MongoDate($request->query->get('start_time'));
        else $start_time = new \MongoDate(time()-31*24*3600); // 1 month ago

        if($request->query->has('end_time') && is_int($request->query->get('end_time')))
            $end_time = new \MongoDate($request->query->get('end_time'));
        else $end_time = new \MongoDate(); // now

        if($request->query->has('interval')) $interval = $request->query->get('interval');
        else $interval = 'day';

        if($request->query->has('env')) $env = ($request->query->get('env') === 'real');
        else $env = true;

        $jsFuncAssocs = array(
            'month' => 'getMonth()',
            'day' => 'getDay()',
            'date' => 'getDate()',
            'hour' => 'getHours()',
        );
        if(!array_key_exists($interval, $jsFuncAssocs))
            throw new HttpException(400, "Bad interval");

        $dm = $this->get('doctrine_mongodb')->getManager();

        $userId = $this->get('security.context')
                    ->getToken()->getUser()->getId();
        $serviceId = $this->get('telepay.services')
                    ->findByName($this->getServiceName())->getId();

        $result = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->group(
                new \MongoCode('
                    function(doc){
                        return {'.$interval.': doc.timeIn.'.$jsFuncAssocs[$interval].'};
                    }
                '),
                array('count' => 0)
            )
            ->reduce('
                function(curr, result){
                    result.count++;
                }
            ')
            ->field('user')->equals($userId)
            ->field('service')->equals($serviceId)
            ->field('mode')->equals($env)
            ->field('timeIn')->gt($start_time)
            ->field('timeIn')->lt($end_time)
            ->getQuery()
            ->execute();

        return $this->handleRestView(
            200,
            "Request successful",
            array(
                'total'=>$result->getCommandResult()['count'],
                'elements'=>$result->toArray()
            )
        );
    }


}