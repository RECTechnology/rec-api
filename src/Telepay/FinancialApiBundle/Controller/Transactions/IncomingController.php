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
     * @Rest\View
     */
    public function make(Request $request, $service_cname, $service_function, $id = null){

        $service = $this->get('net.telepay.services.'.$service_cname);

        if (false === $this->get('security.authorization_checker')->isGranted($service->getRole())) {
            throw $this->createAccessDeniedException();
        }

        $dataIn = array();
        foreach($service->getFields() as $field){
            if(!$request->request->has($field))
                throw new HttpException(400, "Parameter '".$field."' not found");
            else $dataIn[$field] = $request->get($field);
        }

        $dm = $this->get('doctrine_mongodb')->getManager();

        $transaction = Transaction::createFromContext($this->get('transaction.context'));
        $transaction->setService($service_cname);
        $transaction->setStatus("CREATED");
        $transaction->setDataIn($dataIn);
        $this->get('doctrine_mongodb')->getManager()->persist($transaction);

        try {
            $transaction = $service->create($transaction);
        }catch (HttpException $e){
            $transaction->setStatus("FAILED");
            $dm->persist($transaction);
            $dm->flush();
            throw $e;
        }

        $transaction->setTimeOut(new \MongoDate());
        $dm->persist($transaction);
        $dm->flush();

        if($transaction == false) throw new HttpException(500, "oOps, some error has occurred within the call");

        return $this->rest(200, "Successful", $transaction->getData());
    }

    public function update(){

    }

    public function updateTest(){

    }

    /**
     * @Rest\View
     */
    public function makeTest(Request $request, $service_cname, $service_function, $id = null){
        return $this->make($request, $service_cname, $service_function, $id);
    }

}


