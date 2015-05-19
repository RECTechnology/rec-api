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
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Intl\Exception\NotImplementedException;
use Telepay\FinancialApiBundle\Controller\Services\SampleResponse;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\ServiceLifeCycle;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Financial\Currency;


/**
 * Class SampleService
 * @package Telepay\FinancialApiBundle\DependencyInjection\Services
 */
class EchoService extends BaseService {
    public function getFields(){
        return array('param','currency','amount');
    }

    public function create(Transaction $baseTransaction = null){

        if($this->getTransactionContext()->getEnvironment() === 'prod')
            throw new HttpException(503, "Method unavailable in production environment");
        $currency = $baseTransaction->getDataIn()['currency'];
        if(!array_key_exists($currency, Currency::$SCALE)){
            throw new HttpException(400, "Invalid currency");
        }
        $baseTransaction->setCurrency($currency);
        $baseTransaction->setScale(Currency::$SCALE[$currency]);

        $baseTransaction->setStatus('success');
        $baseTransaction->setData(array(
            'param' => $baseTransaction->getDataIn()['param'],
            'server_time' => new \MongoDate(),
            'currency' => $currency
        ));

        $baseTransaction->setDataOut(array(
            'param' => $baseTransaction->getDataIn()['param'],
            'server_time' => new \MongoDate(),
            'currency' => $currency
        ));

        return $baseTransaction;
    }

}
