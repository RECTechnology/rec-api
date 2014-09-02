<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/28/14
 * Time: 6:36 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Stats;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\RestApiController;

abstract class BaseStats extends RestApiController{


    public abstract function getServiceName();

    public function transactions(Request $request, $mode = true) {

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        if($request->query->has('start_time')) $limit = $request->query->get('start_time');
        else $start_time = 10;

        if($request->query->has('end_time')) $offset = $request->query->get('end_time');
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



    public function stats(Request $request, $mode = true) {

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

        if($request->query->has('start_time')) $limit = $request->query->get('start_time');
        else $start_time = 10;

        if($request->query->has('end_time')) $offset = $request->query->get('end_time');
        else $end_time = 0;

        $transactionsRepo = $this->get('doctrine_mongodb')
            ->getManager()
            ->getRepository('TelepayFinancialApiBundle:Transaction');

        $transactions = $transactionsRepo->findBy(
            array(
                'user' => $this->get('security.context')->getToken()->getUser()->getId(),
                'service' => $this->get('telepay.services')->findByName('Test')->getId(),
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


}