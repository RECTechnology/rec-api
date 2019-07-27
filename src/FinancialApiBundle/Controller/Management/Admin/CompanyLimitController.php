<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace App\FinancialApiBundle\Controller\Management\Admin;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\BaseApiController;
use App\FinancialApiBundle\Entity\LimitDefinition;
use App\FinancialApiBundle\Financial\Currency;

class CompanyLimitController extends BaseApiController{

    public function getRepositoryName(){
        return 'FinancialApiBundle:LimitDefinition';
    }

    public function getNewEntity(){
        return new LimitDefinition();
    }

    /**
     * @Rest\View
     */
    public function showAction($id){
        return parent::showAction($id);
    }

    /**
     * @Rest\View
     */
    public function createAction(Request $request){

        $paramNames = array(
            'cname',
            'company_id'
        );

        $params = array();

        foreach($paramNames as $paramName){
            if(!$request->request->has($paramName)) throw new HttpException(404, 'Param '.$paramName.' not found');
            $params[$paramName] = $request->request->get($paramName);
        }

        //check if is exchange
        if(strpos($params['cname'], 'exchange_') !== false){
            //es un exchange
            $explode_exchange = explode('_',$params['cname']);
            if($explode_exchange[0] != 'exchange' ||
                !in_array($explode_exchange[1], Currency::$ALL) ||
                !in_array($explode_exchange[2], Currency::$ALL) ||
                $explode_exchange[1] == $explode_exchange[2]) throw new HttpException(403, 'Invalid exchange');
        }else{
            //es un method
            $method = $this->get('net.app.method_provider')->findByCname($params['cname']);
            if(!$method) throw new HttpException(403, 'Invalid Method');

        }
        //check if exist this limit
        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('FinancialApiBundle:Group')->find($params['company_id']);
        if(!$company) throw new HttpException(404, 'Company not found');
        $limit = $em->getRepository($this->getRepositoryName())->findOneBy(array(
            'group' =>  $company->getId(),
            'cname' =>  $params['cname']
        ));

        if($limit) throw new HttpException(403, 'This limmit already exists, please update it');
        $request->request->remove('company_id');
        $request->request->add(array(
            'group' =>  $company
        ));

        return parent::createAction($request);
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){
        return parent::updateAction($request, $id);
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        return parent::deleteAction($id);
    }

}