<?php

namespace Telepay\FinancialApiBundle\Controller\Services;

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
 * Class TestService
 * @package Telepay\FinancialApiBundle\Controller\Services
 */
class SampleService extends RestApiController
{
    /**
     * This method returns a test response for improving the connection with the API.
     *
     * @ApiDoc(
     *   section="0 - Sample Service",
     *   description="Sample service for test the right connection to the api.",
     *   output="Telepay\FinancialApiBundle\Controller\Services\SampleResponse"
     * )
     *
     * @Rest\View
     */
    public function sample(Request $request, $mode = true) {

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
        $transaction->setCompleted(true);
        $transaction->setSuccessful(true);
        $dm->persist($transaction);
        $dm->flush();

        return $this->handleView($view);
    }


    public function sampleTest(Request $request) {
        return $this->sample($request, false);
    }

}
