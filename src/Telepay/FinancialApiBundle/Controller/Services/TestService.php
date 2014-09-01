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
use Telepay\FinancialApiBundle\Controller\ServiceManager;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Service;


/**
 * Class TestService
 * @package Telepay\FinancialApiBundle\Controller\Services
 */
class TestService extends RestApiController
{
    /**
     * This method returns a test response for improving the connection with the API.
     *
     * @ApiDoc(
     *   section="0 - Testing Service",
     *   description="Service for test the right connection to the api.",
     *   output="Telepay\FinancialApiBundle\Controller\Services\TestResponse",
     *   statusCodes={
     *       200="Returned when successful",
     *       404="Returned when the resource does not exists"
     *   }
     * )
     *
     * @Rest\View
     */
    public function test(Request $request, $mode = true) {

        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('Test')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode(array()));
        $transaction->setMode($mode);

        $response = new TestResponse(
            !$mode,
            date('Y-m-d H:i:s')
        );

        $view = $this->buildRestView(
            200,
            "Request successful",
            $response
        );

        $transaction->setReceivedData($response);
        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction->setTimeOut(time());
        $dm->persist($transaction);
        $dm->flush();

        return $this->handleView($view);
    }


    public function testTest(Request $request) {
        return $this->test($request, false);
    }

}
