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
use Telepay\FinancialApiBundle\DependencyInjection\ServicesRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use Telepay\FinancialApiBundle\Document\Transaction;

class TransactionsController extends RestApiController{


    /**
     * @param Request $request
     * @param $serviceName
     * @param $funcName
     *
     * @Rest\View
     */
    public function make(Request $request, $service_cname, $service_function){
        $servicesRepo = new ServicesRepository();
        $service = $servicesRepo->findByCName($service_cname);

        die("hola");

        //TODO: instantiate $controller = $service->getController();
        //TODO: call $controller->funcName($request)

    }


    /**
     * @param Request $request
     * @param $serviceName
     * @param $funcName
     *
     * @Rest\View
     */
    public function makeTest(Request $request, $service_cname, $service_function, $id = null){

        //Paramore: Brick By Boring Brick [OFFICIAL VIDEO]
        $service = $this->get('net.telepay.services.'.$service_cname);

        $mm = new MethodManipulator();
        $method = $mm->underscoreToCamelcase($service_function);

        if(!method_exists($service, $method))
            throw new HttpException(404, "Method '".$service_function."' not found on service '".$service_cname."''");

        $transaction = Transaction::createFromContext($service);

        $result = call_user_func_array(
            array($service,'sample'),
            array(
                'request' => $request,
                'mode' => 'test',
                'id' => $id
            )
        );

        $transaction->setDebugOut($result);

        $service->getSentData($result);

        if($result == false) throw new HttpException(500, "oOps, some error has occurred within the call");

        $this->rest(200, "Successful", $result);

    }

}

class MethodManipulator{
    public function underscoreToCamelcase($str){
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback('/_([a-z])/', $func, $str);
    }
}

