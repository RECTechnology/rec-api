<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Manager;

use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\DependencyInjection\ServicesRepository;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GroupsController
 * @package Telepay\FinancialApiBundle\Controller\Manager
 */
class LimitsGroupController extends BaseApiController
{
    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:LimitDefinition";
    }

    function getNewEntity()
    {
        return new LimitDefinition();
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){
        return parent::updateAction($request, $id);
    }


}
