<?php

namespace App\DependencyInjection\Transactions;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Intl\Exception\NotImplementedException;
use App\Controller\Services\SampleResponse;
use App\DependencyInjection\Transactions\Core\BaseService;
use App\DependencyInjection\Transactions\Core\ServiceLifeCycle;
use App\Document\Transaction;
use App\Financial\Currency;


/**
 * Class SampleService
 * @package App\DependencyInjection\Services
 */
class EchoService extends BaseService {

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getFields(){
        return array('param','currency','amount');
    }

    public function create(Transaction $baseTransaction = null){
        $this->logger->info('I just got the logger');
        $this->logger->error('An error occurred');

        $currency = $baseTransaction->getDataIn()['currency'];
        if(!array_key_exists($currency, Currency::$SCALE)){
            throw new HttpException(400, "Invalid currency");
        }
        if(!is_numeric($baseTransaction->getDataIn()['amount'])){
            throw new HttpException(400, "Amount must be numeric");
        }
        $baseTransaction->setCurrency($currency);
        $baseTransaction->setScale(Currency::$SCALE[$currency]);

        if($this->getContainer()->get('kernel')->getEnvironment() === 'prod')
            throw new HttpException(503, "Method unavailable for production environment");

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
