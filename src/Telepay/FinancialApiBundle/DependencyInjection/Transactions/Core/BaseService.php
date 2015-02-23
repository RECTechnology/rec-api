<?php

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Telepay\FinancialApiBundle\Controller\Services\SampleResponse;
use Telepay\FinancialApiBundle\Document\Transaction;

abstract class BaseService implements Service, TransactionContext, ServiceLifeCycle, BeforeRequestCallbacks, AfterRequestCallbacks {

    private $request;
    private $user;
    private $odm;
    private $orm;
    private $transaction;

    public function __construct(Request $request, $user, $odm, $orm){
        $this->request=$request;
        $this->user=$user;
        $this->odm=$odm;
        $this->orm=$orm;
        $this->transaction = null;
    }

    public function beforeCall()
    {
        $this->transaction = new Transaction();
        $this->transaction->setIp($this->getRequest()->getClientIp());
        $this->transaction->setTimeIn(new \MongoDate());
        $this->transaction->setService($this->getId());
        $this->transaction->setUser($this->getUser()->getId());
        $this->transaction->setSentData($this->getSentData());
        $this->transaction->setMode($this->getMode);
    }

    public function afterCall(){
        $this->getTransaction()->setReceivedData($this->getReceivedData());
        $this->getTransaction()->setTimeOut(new \MongoDate());
        $this->getTransaction()->setStatus($this->getStatus());

        $this->getOdm()->getManager()->persist($this->getTransaction());
        $this->getOdm()->getManager()->flush();
    }

    public function getStartTime()
    {
        return new \MongoDate();
    }

    public function getEndTime()
    {
        return new \MongoDate();
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return mixed
     */
    public function getOdm()
    {
        return $this->odm;
    }

    /**
     * @return mixed
     */
    public function getOrm()
    {
        return $this->orm;
    }

    /**
     * @return null
     */
    public function getTransaction()
    {
        return $this->transaction;
    }
}
