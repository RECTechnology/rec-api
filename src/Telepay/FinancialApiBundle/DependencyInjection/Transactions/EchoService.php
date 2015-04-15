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

        //comprobar que la currency existe y sacar el scale correspondiente
        $currencies=Currency::$LISTA;
        $currency_found = 0;
        foreach($currencies as $currency ){
            if($currency == $baseTransaction->getDataIn()['currency'] ){
                $currency_found = 1;
                $scale=0;
                switch($currency){
                    case "EUR":
                        $scale=2;
                        break;
                    case "MXN":
                        $scale=2;
                        break;
                    case "USD":
                        $scale=2;
                        break;
                    case "BTC":
                        $scale=8;
                        break;
                    case "FAC":
                        $scale=8;
                        break;
                    case "PLN":
                        $scale=2;
                        break;
                    case "":
                        $scale=0;
                        break;
                }
                $baseTransaction->setScale($scale);
            }
        }

        if( $currency_found == 0 ){
            throw new HttpException(400,'Bad currency');
        }else{
            $baseTransaction->setCurrency($baseTransaction->getDataIn()['currency']);
        }



        $baseTransaction->setStatus('success');
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
