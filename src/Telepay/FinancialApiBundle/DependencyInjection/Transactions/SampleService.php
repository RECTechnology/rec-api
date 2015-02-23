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
use Telepay\FinancialApiBundle\DependencyInjection\Transacttions\Services\Sample;
use Telepay\FinancialApiBundle\Document\Transaction;


/**
 * Class SampleService
 * @package Telepay\FinancialApiBundle\DependencyInjection\Services
 */
class SampleService extends BaseService implements Sample {

    public function sample() {
        return new SampleResponse(
            $this->getMode()==='test'?false:true,
            date('Y-m-d H:i:s')
        );
    }

    public function getId()
    {
        return 1;
    }

    public function getName()
    {
        // TODO: Implement getName() method.
    }

    public function getRole()
    {
        // TODO: Implement getRole() method.
    }

    public function getCname()
    {
        // TODO: Implement getCname() method.
    }

    public function getBase64Image()
    {
        // TODO: Implement getBase64Image() method.
    }

    /**
     * @return mixed
     */
    public function getReceivedData()
    {
        return $this->receivedData;
    }


    public function getStatus()
    {
        return 'OK';
    }

    public function getSentData()
    {
        return json_encode(new \stdClass());
    }

}
