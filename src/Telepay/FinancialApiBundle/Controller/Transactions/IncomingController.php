<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/22/15
 * Time: 8:16 PM
 */



namespace Telepay\FinancialApiBundle\Controller\Transactions;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Telepay\FinancialApiBundle\Document\Transaction;

class IncomingController extends RestApiController{

    /**
     * @param Request $request
     * @param $serviceName
     * @param $funcName
     *
     * @Rest\View
     */
    public function make(Request $request, $service_cname, $service_function, $id = null){

        $service = $this->get('net.telepay.services.'.$service_cname);

        //TODO: check the fields
        //$service->getFields();

        //TODO: the call always should be
        // add service parameters to debugIn
        // implement $service->create($initialTrans) -> return transaction
        // implement $service->update($trId, array() data) -> return transaction
        // implement $service->check($trId)
        // add result to debugOut

        if (false === $this->get('security.authorization_checker')->isGranted($service->getRole())) {
            throw $this->createAccessDeniedException();
        }

        $mm = $this->get('net.telepay.commons.method_manipulator');
        $method = $mm->underscoreToCamelcase($service_function);

        if(!method_exists($service, 'create'))
            throw new HttpException(404,"Method '" . $service_function . "' not found on service '" . $service_cname . "''");

        $dm = $this->get('doctrine_mongodb')->getManager();

        $transaction = Transaction::createFromContext($this->get('transaction.context'));
        //$transaction->setDebugIn($request);
        $transaction->setService($service_cname);
        $transaction->setStatus("CREATED");
        $this->get('doctrine_mongodb')->getManager()->persist($transaction);
        try {
            $transaction = $service->create($transaction);
        }catch (HttpException $e){
            $transaction->setStatus("FAILED");
            $dm->persist($transaction);
            $dm->flush();
            throw $e;
        }


        //$transaction = $service->getTransaction($transaction);
        //$transaction->setTimeOut(new \MongoDate());
        //$transaction->setStatus("SUCCESS");
        //$transaction->setData(json_encode($result->jsonSerialize()));
        $dm->persist($transaction);
        $dm->flush();

        if($transaction == false) throw new HttpException(500, "oOps, some error has occurred within the call");

        return $this->rest(200, "Successful", json_decode($transaction->getData(), true));
    }

    public function update(){

    }

    public function updateTest(){

    }

    /**
     * @param Request $request
     * @param $serviceName
     * @param $funcName
     *
     * @Rest\View
     */
    public function makeTest(Request $request, $service_cname, $service_function, $id = null){
        return $this->make($request, $service_cname, $service_function, $id);
    }

}


