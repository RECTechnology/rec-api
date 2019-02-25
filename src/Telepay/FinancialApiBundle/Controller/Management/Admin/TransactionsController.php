<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;


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

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('service')->equals('rec')
            ->field('type')->equals('out')
            ->sort($sort, $order)
            ->limit($limit)
            ->skip($offset)
            ->getQuery();

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

            $created = $transaction->getCreated();
            $result[]=array(
                $transaction->getId(),
                $sender->getId(),
                $sender->getType(),
                $sender->getSubtype(),
                $receiver->getId(),
                $receiver->getType(),
                $receiver->getSubtype(),
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
