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

        if($request->query->has('limit')) $limit = intval($request->query->get('limit'));
        else $limit = 10;

        if($request->query->has('offset')) $offset = intval($request->query->get('offset'));
        else $offset = 0;

        $userId = $this->get('security.context')->getToken()->getUser()->getId();
        $serviceId = $this->get('telepay.services')
            ->findByName($this->getServiceName())->getId();

        $dm = $this->get('doctrine_mongodb')->getManager();

        $total = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('user')->equals($userId)
            ->field('service')->equals($serviceId)
            ->field('mode')->equals($mode)
            ->count()->getQuery()->execute();

        $transactions = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('user')->equals($userId)
            ->field('service')->equals($serviceId)
            ->field('mode')->equals($mode)
            ->sort('timeIn', 'desc')
            ->skip($offset)->limit($limit)->getQuery()->execute();

        $tansArray = [];
        foreach($transactions->toArray() as $transaction){
            $tansArray []= $transaction;
        }

        $start = $offset;
        $end = $offset+$limit;

        return $this->handleRestView(
            200,
            "Request successful",
            array(
                'total' => $total,
                'start' => $start,
                'end' => $end,
                'transactions' => $tansArray
            )
        );
    }

     public function transactionsTest(Request $request, $mode = false) {
        return $this->transactions($request, $mode);
     }


    public function stats(Request $request) {

        if($request->query->has('start_time') && is_numeric($request->query->get('start_time')))
            $start_time = new \MongoDate($request->query->get('start_time'));
        else $start_time = new \MongoDate(time()-31*24*3600); // 1 month ago

        if($request->query->has('end_time') && is_numeric($request->query->get('end_time')))
            $end_time = new \MongoDate($request->query->get('end_time'));
        else $end_time = new \MongoDate(); // now

        if($request->query->has('interval')) $interval = $request->query->get('interval');
        else $interval = 'day';

        if($request->query->has('env')) $env = ($request->query->get('env') === 'real');
        else $env = true;

        $jsAssocs = array(
            'month' => 'getMonth()',
            'day' => 'getDate()',
            'date' => 'getDate()',
            'hour' => 'getHours()',
        );

        if(!array_key_exists($interval, $jsAssocs))
            throw new HttpException(400, "Bad interval");

        $dm = $this->get('doctrine_mongodb')->getManager();

        $userId = $this->get('security.context')
                    ->getToken()->getUser()->getId();
        $serviceId = $this->get('telepay.services')
                    ->findByName($this->getServiceName())->getId();

        $result = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('user')->equals($userId)
            ->field('service')->equals($serviceId)
            ->field('mode')->equals($env)
            ->field('timeIn')->gt($start_time)
            ->field('timeIn')->lt($end_time)
            ->group(
                new \MongoCode('
                    function(trans){
                        return {
                            '.$interval.': trans.timeIn.'.$jsAssocs[$interval].'
                        };
                    }
                '),
                array('success' => 0, 'fail' => 0)
            )
            ->reduce('
                function(curr, result){
                    if(curr.successful)
                        result.success++;
                    else
                        result.fail++;
                }
            ')
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