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
use Telepay\FinancialApiBundle\Entity\Service;



/**
 * Class UserServiceController
 * @package Telepay\FinancialApiBundle\Controller\Services
 */
abstract class UserServiceController extends RestApiController {

    protected $currentTransaction = null;

    public abstract function getService();
    public abstract function getInputData(Request $request);

    protected function getDM(){
        return $this->get('doctrine_mongodb')->getManager();
    }

    protected function saveTransaction(){
        $this->getDM()->persist($this->currentTransaction);
        $this->getDM()->flush();
    }

    protected function getTransaction($mode = true){
        if($this->currentTransaction === null){
            $this->currentTransaction = new Transaction();
            $this->currentTransaction->setIp($this->get('request')->getClientIp());
            $this->currentTransaction->setTimeIn(new \MongoDate());
            $this->currentTransaction->setMode($mode);
            $this->currentTransaction->setService($this->getService()->getId());
            $this->currentTransaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
            $this->currentTransaction->setCompleted(false);
            $this->currentTransaction->setSuccessful(false);
            $this->saveTransaction();
        }
        return $this->currentTransaction;
    }
}
