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

class TestStats extends RestApiController{

    public function transactions(Request $request, $mode = true) {

        if($request->query->has('limit')) $limit = $request->query->get('limit');
        else $limit = 10;

        if($request->query->has('offset')) $offset = $request->query->get('offset');
        else $offset = 0;

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

        $view = $this->buildRestView(
            200,
            "Request successful",
            $transactions
        );

        return $this->handleView($view);

    }

     public function transactionsTest(Request $request, $mode = false) {
        return $this->transactions($request, $mode);
     }

}