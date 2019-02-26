<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use DateInterval;
use DateTime;


/**
 * Class TransactionsController
 * @package Telepay\FinancialApiBundle\Controller\Management\Admin
 */
class TransactionsController extends RestApiController {

    /**
     * @Rest\View
     */
    public function deleteAction($id){

        $dm = $this->get('doctrine_mongodb')->getManager();
        $trans = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->find($id);

        if(!$trans) throw new HttpException(404,'Not found');

        $dm->remove($trans);
        $dm->flush();

        return $this->restV2(204,"ok", "Deleted");
    }

    /**
     * @Rest\View
     */
    public function findAction($id){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $trans = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->find($id);

        if(!$trans) throw new HttpException(404,'Not found');

        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('TelepayFinancialApiBundle:Group')->find($trans->getGroup());

        //TODO get Method
        $methods = $this->get('net.telepay.method_provider')->findByCNames(array($trans->getMethod().'-'.$trans->getType()));

        $response = array(
            'transaction'   =>  $trans,
            'company'   =>  $company,
            'method'    =>  $methods[0]
        );

        return $this->restV2(200,"ok", "Transaction ffound successfully", $response);
    }

    /**
     * @Rest\View
     */
    public function listAction(Request $request){
        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();

        $limit = $request->query->getInt('limit', 10);
        $offset = $request->query->getInt('offset', 0);
        $search = $request->query->get("search", "");
        $sort = $request->query->getAlnum("sort", "id");
        $order = $request->query->getAlpha("order", "desc");

        if($search!=''){
            $qa = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('service')->equals('rec')
                ->field('id')->equals($search)
                ->getQuery();
            foreach ($qa->toArray() as $transaction) {
                $payment_info = $transaction->getPayInInfo();
                $txid = $payment_info['txid'];
                if($transaction->getType()=='in'){
                    $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                        ->field('service')->equals('rec')
                        ->field('pay_out_info.txid')->equals($txid)
                        ->getQuery();
                }
                else{
                    $qb = $qa;
                }
            }
        }
        else {
            $query = $request->query->get('query');
            if(isset($query['start_date'])){
                $start_time = new \MongoDate(strtotime(date($query['start_date'].' 00:00:00')));
            }else{
                $fecha = new DateTime();
                $fecha->sub(new DateInterval('P3M'));
                $start_time = new \MongoDate($fecha->getTimestamp());
            }

            if(isset($query['finish_date'])){
                $finish_time = new \MongoDate(strtotime(date($query['finish_date'].' 23:59:59')));
            }else{
                $finish_time = new \MongoDate();
            }

            $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('service')->equals('rec')
                ->field('created')->gte($start_time)
                ->field('created')->lte($finish_time)
                ->field('type')->equals('out')
                ->sort($sort, $order)
                ->limit($limit)
                ->skip($offset)
                ->getQuery();
        }

        $result = array();
        foreach ($qb->toArray() as $transaction) {
            $sender = $em->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
                'id' => $transaction->getGroup()
            ));

            $payment_info = $transaction->getPayOutInfo();
            $address = $payment_info['address'];
            $receiver = $em->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
                'rec_address' => $address
            ));
            if($receiver){
                $re_id = $receiver->getId();
                $re_type = $receiver->getType();
                $re_subtype = $receiver->getSubtype();
            }
            else{
                $re_id = '-';
                $re_type = '-';
                $re_subtype = '-';
            }

            $created = $transaction->getCreated();
            $result[]=array(
                $sender->getId(),
                $sender->getType(),
                $sender->getSubtype(),
                $re_id,
                $re_type,
                $re_subtype,
                $transaction->getMethod(),
                $transaction->getInternal(),
                $transaction->getStatus(),
                $transaction->getAmount(),
                $created->format('Y-m-d H:i:s')
            );
        }
        return $this->restV2(200,"ok", "List transactions generated", $result);
    }


}
