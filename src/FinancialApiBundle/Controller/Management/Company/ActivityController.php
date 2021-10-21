<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace App\FinancialApiBundle\Controller\Management\Company;

use App\FinancialApiBundle\Entity\Activity;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;
use App\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

class ActivityController extends BaseApiController{

    function getRepositoryName()
    {
        return "FinancialApiBundle:Activity";
    }

    function getNewEntity()
    {
        return new Activity();
    }


    /**
     * @Rest\View
     * @return Response
     */
    public function getActivitiesV4(){

        $em = $this->getDoctrine()->getManager();
        $name = 'culture';


        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();

        $select = 'a.id, ' .
            'a.name, ' .
            'a.name_es, ' .
            'a.name_ca, ' .
            'identity(a.parent) as parent';

        $activities = $qb
            ->select($select)
            ->from(Activity::class, 'a')
            ->where("lower(a.name) = '$name'")
            ->getQuery()
            ->getResult();

        if (count($activities) > 0){
            $children_activities = $qb
                ->where('a.parent =' . $activities[0]["id"])
                ->getQuery()
                ->getResult();

            $activities = array_merge($activities, $children_activities);
        }

        return $this->restV2(200, "ok", "Done", $activities);

    }
}