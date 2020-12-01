<?php

namespace App\FinancialApiBundle\Controller\Management\Admin;

use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use DateInterval;
use DateTime;
use App\FinancialApiBundle\Controller\SecurityTrait;


/**
 * Class TransactionsController
 * @package App\FinancialApiBundle\Controller\Management\Admin
 */
class TransactionsController extends RestApiController {

    use SecurityTrait;

    /**
     * @Rest\View
     */
    public function deleteAction($id){

        $dm = $this->get('doctrine_mongodb')->getManager();
        $trans = $dm->getRepository('FinancialApiBundle:Transaction')->find($id);

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
        $trans = $dm->getRepository('FinancialApiBundle:Transaction')->find($id);

        if(!$trans) throw new HttpException(404,'Not found');

        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('FinancialApiBundle:Group')->find($trans->getGroup());
        $company = $company->getAdminView();

        $response = array(
            'transaction'   =>  $trans,
            'company'   =>  $company
        );

        return $this->restV2(200,"ok", "Transaction found successfully", $this->secureOutput($response));
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
        $sort = $request->query->getAlnum("sort", "updated");
        $order = $request->query->getAlpha("order", "desc");
        $total = 0;

        if($search!=""){
            $qb = array();
            $trans = $dm->getRepository('FinancialApiBundle:Transaction')->find($search);
            if($trans){
                $total = 1;
                $payment_info = $trans->getPayInInfo();
                $txid = $payment_info['txid'];
                if($trans->getType()=='in'){
                    $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
                        ->field('service')->equals('rec')
                        ->field('pay_out_info.txid')->equals($txid)
                        ->getQuery();
                }
                else{
                    $qb[] = $trans;
                }
            }
        }
        else {
            $start = $request->query->get("start_date", "0");
            if($start!="0"){
                $start_date = new \MongoDate(strtotime($start .' 00:00:00'));
            }
            else{
                $start_date = new \MongoDate(strtotime('-1 month 00:00:00'));
            }

            $finish = $request->query->get("finish_date", "0");
            if($finish!="0"){
                $finish_date = new \MongoDate(strtotime($finish .' 23:59:59'));
            }
            else{
                $finish_date = new \MongoDate(strtotime('now'));
            }

            $total = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
                ->field('service')->equals('rec')
                ->field('updated')->gte($start_date)
                ->field('updated')->lte($finish_date)
                ->field('type')->equals('out')
                ->getQuery();

            $total = count($total);

            $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
                ->field('service')->equals('rec')
                ->field('updated')->gte($start_date)
                ->field('updated')->lte($finish_date)
                ->field('type')->equals('out')
                ->sort($sort, $order)
                ->limit($limit)
                ->skip($offset)
                ->getQuery();
        }

        $result = array();
        foreach ($qb as $transaction) {
            $sender = $em->getRepository('FinancialApiBundle:Group')->findOneBy(array(
                'id' => $transaction->getGroup()
            ));

            $payment_info = $transaction->getPayOutInfo();
            $address = $payment_info['address'];
            $receiver = $em->getRepository('FinancialApiBundle:Group')->findOneBy(array(
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

            $result[]=array(
                $transaction->getId(),
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
                $transaction->getUpdated()
            );
        }
        $data = array(
            'list' => $result,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        );
        return $this->restV2(200,"ok", "List transactions generated", $data);
    }


}
