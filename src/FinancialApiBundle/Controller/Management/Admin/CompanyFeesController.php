<?php

namespace App\FinancialApiBundle\Controller\Management\Admin;

use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\BaseApiController;
use App\FinancialApiBundle\Entity\ServiceFee;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GroupsController
 * @package App\FinancialApiBundle\Controller\Admin
 */
class CompanyFeesController extends BaseApiController
{
    function getRepositoryName()
    {
        return "FinancialApiBundle:ServiceFee";
    }

    function getNewEntity()
    {
        return new ServiceFee();
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){

        //negative values not allowed in variable and fixed field
        if($request->request->has('fixed')){
            if($request->request->get('fixed') < 0) throw new HttpException(404, 'Parameter fixed must be positive.');
        }

        if($request->request->has('variable')){
            if($request->request->get('variable') < 0) throw new HttpException(404, 'Parameter variable must be positive.');
        }

        return parent::updateAction($request, $id);
    }


}
