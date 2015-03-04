<?php

namespace Telepay\FinancialApiBundle\Controller\Services\Cryptos;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Document\Transaction;


/**
 * Class Faircoin
 * @package Telepay\FinancialApiBundle\Controller\Services
 */
class Faircoin extends RestApiController
{
    /**
     * Method for create a Faircoin transaction given the amount.
     *
     * @ApiDoc(
     *   section="Faircoin",
     *   description="Generate a Faircoin address for one payment",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successfully created",
     *   },
     *   output={
     *
     *      },
     *   parameters={
     *      {
     *          "name"="amount",
     *          "dataType"="Float",
     *          "required"="true",
     *          "description"="The amount to charge . E.g.:109.34"
     *      },
     *      {
     *          "name"="confirmations",
     *          "dataType"="Integer",
     *          "required"="true",
     *          "description"="Minimum of confirmations to validate the payment . E.g.:2"
     *      }
     *   }
     * )
     * @Rest\View
     */

    public function createCharge(Request $request, $mode = true) {

        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(new \MongoDate());
        $transaction->setService($this->get('telepay.services')->findByName('Sample')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode(new \stdClass()));
        $transaction->setMode($mode);

        $response = new SampleResponse(
            $mode?false:true,
            date('Y-m-d H:i:s')
        );

        $view = $this->buildRestView(
            200,
            "Request successful",
            $response
        );

        $transaction->setReceivedData($response);
        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction->setTimeOut(new \MongoDate());
        $transaction->setCompleted(true);
        $transaction->setSuccessful(true);
        $dm->persist($transaction);
        $dm->flush();

        return $this->handleView($view);
    }


    public function createChargeTest(Request $request) {
        return $this->sample($request, false);
    }

    /**
     * Method for check status of the transaction.
     *
     * @ApiDoc(
     *   section="Faircoin",
     *   description="Check the payment with the tx_id",
     *   https="true",
     *   statusCodes={
     *       200="Returned when the request was in progress or completed",
     *       404="Returned when the tx_id was not found"
     *   }
     * )
     * @Rest\View
     */
    public function checkCharge(Request $request, $mode = true) {

    }


}
