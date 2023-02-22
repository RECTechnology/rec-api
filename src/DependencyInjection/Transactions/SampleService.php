<?php

namespace App\DependencyInjection\Transactions;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Controller\Services\SampleResponse;
use App\DependencyInjection\Transactions\Core\BaseService;
use App\DependencyInjection\Transactions\Core\ServiceLifeCycle;
use App\Document\Transaction;


/**
 * Class SampleService
 * @package App\DependencyInjection\Services
 */
class SampleService extends BaseService {
    public function getFields(){
        return array('param');
    }

    public function create(Transaction $baseTransaction = null){

        $baseTransaction->setData(array(
            'param' => $baseTransaction->getDataIn()['param'],
            'server_time' => new \MongoDate()
        ));

        return $baseTransaction;
    }

    public function cancel(Transaction $transaction,$data){

        throw new HttpException(400,'Method not implemented');

    }

}
