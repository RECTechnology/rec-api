<?php

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Telepay\FinancialApiBundle\Controller\Services\SampleResponse;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\ServiceLifeCycle;
use Telepay\FinancialApiBundle\Document\Transaction;


/**
 * Class SampleService
 * @package Telepay\FinancialApiBundle\DependencyInjection\Services
 */
class EchoService extends BaseService {
    public function getFields(){
        return array('param','currency','amount');
    }

    public function create(Transaction $baseTransaction = null){

        $baseTransaction->setCurrency($baseTransaction->getDataIn()['currency']);

        $baseTransaction->setData(array(
            'param' => $baseTransaction->getDataIn()['param'],
            'server_time' => new \MongoDate()
        ));

        $baseTransaction->setDataOut(array(
            'param' => $baseTransaction->getDataIn()['param'],
            'server_time' => new \MongoDate()
        ));

        return $baseTransaction;
    }

}
