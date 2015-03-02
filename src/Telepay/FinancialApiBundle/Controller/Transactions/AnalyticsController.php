<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 9:20 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Transactions;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;

class AnalyticsController extends RestApiController {

    static $OLD_CNAME_ID_MAPPINGS = array(
        "sample" => 1,
        "aaa" => 3,
        "aaaa" => 4
    );


    /**
     * @param Request $request
     * @param $service_cname
     * @param null $id
     * @Rest\View
     */
    public function process(Request $request, $service_cname, $id = null){
        return $this->findTransactions($request, $service_cname, $id, 'real');

    }

    /**
     * @param Request $request
     * @param $service_cname
     * @param null $id
     * @return RestView
     * @Rest\View
     */
    public function processTest(Request $request, $service_cname, $id = null){
        return $this->findTransactions($request, $service_cname, $id, 'test');
    }

    private function findTransactions(Request $request, $service_cname, $id = null, $mode = 'real'){

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
        $service = $this->get('net.telepay.services.'.$service_cname);

        $dm = $this->get('doctrine_mongodb')->getManager();

        $transactions = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('user')->equals($userId)
            ->field('service')->equals($service->getCname())
            ->field('mode')->equals($mode)
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
                ->field('mode')->equals($mode)
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

        return $this->rest(
            200,
            "Request successful",
            $transArray
        );
    }

}