<?php

namespace App\FinancialApiBundle\Controller\Management\Manager;

use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\BaseApiController;
use App\FinancialApiBundle\Entity\LimitDefinition;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GroupsController
 * @package App\FinancialApiBundle\Controller\Manager
 */
class LimitsGroupController extends BaseApiController
{
    function getRepositoryName()
    {
        return "FinancialApiBundle:LimitDefinition";
    }

    function getNewEntity()
    {
        return new LimitDefinition();
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){

        //check if this user pertenece al group de la fee
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if(!$user->hasRole('ROLE_SUPER_ADMIN')){
            $group = $user->getActiveGroup();
            $em = $this->getDoctrine()->getManager();
            $limit = $em->getRepository($this->getRepositoryName())->find($id);
            $limitGroup = $limit->getGroup();

            //if($group->getId() != $limitGroup->Creator()->getId()) throw new HttpException(409, 'You don\'t have the necessary permissions');

            if(!$user->hasRole('ROLE_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

            //check limits from parent and if superior throw exception
            $creatorGroup = $group->getCreatorGroup();
            $creatorLimits = $creatorGroup->getLimits();
            if($request->request->has('day')){
                $day = $request->request->get('day');
                if(($day == -1 && $creatorLimits->getDay() != -1) || $day > $creatorLimits->getDay()) throw new HttpException(403, 'Day limit can\'t be greater than creator limit');
            }

            if($request->request->has('week')){
                $week = $request->request->get('week');
                if(($week == -1 && $creatorLimits->getWeek() != -1) ||$week > $creatorLimits->getWeek()) throw new HttpException(403, 'Week limit can\'t be greater than creator limit');
            }

            if($request->request->has('month')){
                $month = $request->request->get('month');
                if(($month == -1 && $creatorLimits->getMonth() != -1) || $month > $creatorLimits->getMonth()) throw new HttpException(403, 'Month limit can\'t be greater than creator limit');
            }

            if($request->request->has('total')){
                $total = $request->request->get('total');
                if(($total == -1 && $creatorLimits->getTotal() != -1) || $total > $creatorLimits->getTotal()) throw new HttpException(403, 'Total limit can\'t be greater than creator limit');
            }

            if($request->request->has('year')){
                $year = $request->request->get('year');
                if(($year == -1 && $creatorLimits->getYear() != -1) || $year > $creatorLimits->getYear()) throw new HttpException(403, 'Year limit can\'t be greater than creator limit');
            }

            if($request->request->has('single')){
                $single = $request->request->get('single');
                if(($single == -1 && $creatorLimits->getSingle() != -1) || $single > $creatorLimits->getSingle()) throw new HttpException(403, 'Single  limit can\'t be greater than creator limit');
            }
        }

        return parent::updateAction($request, $id);
    }

}
