<?php

namespace App\FinancialApiBundle\Controller\Management\Manager;

use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\BaseApiController;
use App\FinancialApiBundle\Entity\ServiceFee;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GroupsController
 * @package App\FinancialApiBundle\Controller\Manager
 */
class CommissionsGroupController extends BaseApiController
{
    function getRepositoryName()
    {
        return "FinancialApiBundle:ServiceFee";
    }

    function getNewEntity()
    {
        return new ServiceFee();
    }
}
