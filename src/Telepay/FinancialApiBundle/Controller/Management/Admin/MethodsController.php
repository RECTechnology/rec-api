<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Telepay\FinancialApiBundle\Entity\StatusMethod;
use Telepay\FinancialApiBundle\Financial\Currency;

/**
 * Class MethodsController
 * @package Telepay\FinancialApiBundle\Controller\Management\Admin
 */
class MethodsController extends BaseApiController
{

    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:StatusMethod";
    }

    function getNewEntity()
    {
        return new StatusMethod();
    }


    /**
     * @Rest\View()
     */
    public function index() {

        $services = $this->get('net.telepay.method_provider')->findAll();

        $allowed_services = [];
        if ($this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {

            $em = $this->getDoctrine()->getManager();

            foreach($services as $service){
                $statusMethod = $em->getRepository($this->getRepositoryName())->findOneBy(array(
                    'method'    =>  $service->getCname(),
                    'type'      =>  $service->getType()
                ));

                $status = 'not found';
                $balance = 'not found';
                $id = 'not found';
                if($statusMethod){
                    $status = $statusMethod->getStatus();
                    $balance = $statusMethod->getBalance();
                    $id = $statusMethod->getId();
                }

                if($service->getCname() == 'btc' || $service->getCname() == 'fac'){
                    if($service->getCname() == 'fac'){
                        $cryptoWallet = $this->container->get('net.telepay.wallet.fullnode.fair');
                    }else{
                        $cryptoWallet = $this->container->get('net.telepay.wallet.fullnode.'.$service->getCname());
                    }

                    $balance = $cryptoWallet->getBalance();
                }

                $resp = array(
                    'id'    =>  $id,
                    'name' =>  ucfirst($service->getCname()),
                    'cname' =>  $service->getCname(),
                    'type' =>  $service->getType(),
                    'currency'  =>  $service->getCurrency(),
                    'scale' =>  Currency::$SCALE[$service->getCurrency()],
                    'status'    =>  $status,
                    'balance'   =>  $balance,
                    'base64image'   =>  $service->getBase64Image()
                );

                $allowed_services[] = $resp;
            }
        }else{
            $userGroup = $this->get('security.token_storage')->getToken()->getUser()->getActiveGroup();
            $group_services = $userGroup->getMethodsList();

            foreach($services as $method){
                if(in_array($method->getCname().'-'.$method->getType(), $group_services)){

                    $methodsEntity = $this->get('net.telepay.method_provider')->findByCname($method->getCname().'-'.$method->getType());

                    $resp = array(
                        'name' =>  ucfirst($methodsEntity->getCname()),
                        'cname' =>  $methodsEntity->getCname(),
                        'type' =>  $methodsEntity->getType(),
                        'currency'  =>  $methodsEntity->getCurrency(),
                        'scale' =>  Currency::$SCALE[$methodsEntity->getCurrency()],
                        'base64image'   =>  $methodsEntity->getBase64Image()
                    );

                    $allowed_services[] = $resp;
                }

            }

        }

        if ($this->get('security.authorization_checker')->isGranted('ROLE_SUPER_COMMERCE')) {
            //todo: add pos service
        }

        //TODO: add exchange service

        return $this->restV2(
            200,
            "ok",
            "Methods got successfully",
            $allowed_services
        );
    }

    /**
     * @Rest\View()
     */
    public function indexSwift() {

        //search all input methods and output methods and combine like btc_halcash_es
        $services = $this->get('net.telepay.swift_provider')->findAll();

        if ($this->get('security.authorization_checker')->isGranted('ROLE_SUPER_COMMERCE')) {
            //todo: add pos service
        }

        $swift_methods = array();

        if ($this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {

            $em = $this->getDoctrine()->getManager();

            foreach($services as $service){
                $statusMethod = $em->getRepository($this->getRepositoryName())->findOneBy(array(
                    'method'    =>  $service,
                    'type'      =>  'swift'
                ));

                $status = 'not found';
                $id = 'not found';
                if($statusMethod){
                    $status = $statusMethod->getStatus();
                    $id = $statusMethod->getId();
                }

                $methods = explode('-',$service);

                $method_in = $this->get('net.telepay.in.'.$methods[0].'.v1');
                $method_out = $this->get('net.telepay.out.'.$methods[1].'.v1');

                $swift = array();
                $swift['id'] = $id;
                $swift['status'] = $status;
                $swift['name'] = $method_in->getName().' to '.$method_out->getName();
                $swift['cname'] = $method_in->getCname().'-'.$method_out->getCname();
                $swift['orig_coin'] = $method_in->getCurrency();
                $swift['dst_coin']  = $method_out->getCurrency();
                $swift_methods[] = $swift;
            }
        }else{
            foreach($services as $service){
                $methods = explode('-',$service);

                $method_in = $this->get('net.telepay.in.'.$methods[0].'.v1');
                $method_out = $this->get('net.telepay.out.'.$methods[1].'.v1');

                $swift = array();
                $swift['name'] = $method_in->getName().' to '.$method_out->getName();
                $swift['cname'] = $method_in->getCname().'-'.$method_out->getCname();
                $swift['orig_coin'] = $method_in->getCurrency();
                $swift['dst_coin']  = $method_out->getCurrency();
                $swift_methods[] = $swift;

            }
        }


        //TODO: add exchange service

        return $this->restV2(
            200,
            "ok",
            "Swift methods got successfully",
            $swift_methods
        );
    }

    /**
     * @Rest\View()
     */
    public function indexExchanges() {

        $exchange_methods = array();

        if ($this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {

            $em = $this->getDoctrine()->getManager();

            $exchanges = $em->getRepository('TelepayFinancialApiBundle:StatusMethod')->findBy(array(
                'type'  => 'exchange'
            ));

            foreach($exchanges as $exchange){

                $currencies = explode('to',$exchange->getMethod());
                $exch = array();
                $exch['id'] = $exchange->getId();
                $exch['status'] = $exchange->getStatus();
                $exch['name'] = $exchange->getMethod();
                $exch['cname'] = $exchange->getMethod();
                $exch['orig_coin'] = $currencies[0];
                $exch['dst_coin']  = $currencies[1];
                $exchange_methods[] = $exch;
            }
        }

        return $this->restV2(
            200,
            "ok",
            "Exchanges Status got successfully",
            $exchange_methods
        );
    }

    /**
     * @Rest\View()
     */

    public function createMethod(Request $request){
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN'))
            throw new HttpException(403, 'You don\'t have de necessary permissions');

        $paramsArray = array(
            'method',
            'type',
            'currency',
            'balance',
            'status'
        );

        foreach($paramsArray as $param){
            if(!$request->request->has($param)) throw new HttpException(404, 'Parameter '.$param.' not found');

        }

        return parent::createAction($request);

    }

    /**
     * @Rest\View()
     */
    public function updateMethod(Request $request, $id){

        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN'))
            throw new HttpException(403, 'You don\'t have de necessary permissions');

        $em = $this->getDoctrine()->getManager();

        $method = $em->getRepository($this->getRepositoryName())->find($id);

        if(!$method) throw new HttpException(404, 'Method not found');

        if($request->request->has('amount')){
            $balance = $method->getBalance() + $request->request->get('amount');
            $request->request->remove('amount');
            $request->request->add(array(
                'balance'    =>  $balance
            ));
        }

        return parent::updateAction($request, $id);
    }

    /**
     * @Rest\View()
     */
    public function deleteMethod($id){
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN'))
            throw new HttpException(403, 'You don\'t have de necessary permissions');

        return parent::deleteAction($id);
    }

}
