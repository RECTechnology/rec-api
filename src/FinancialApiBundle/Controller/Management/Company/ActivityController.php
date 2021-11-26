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
use Symfony\Component\HttpKernel\Exception\HttpException;

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

    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     */
    public function searchActivitiesV4(Request $request){

        $parent_id =$request->query->get('parent_id');

        $em = $this->getDoctrine()->getManager();


        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();

        $select = 'a.id, ' .
            'a.name, ' .
            'a.name_es, ' .
            'a.name_ca, ' .
            'identity(a.parent) as parent';

        if(isset($parent_id) ){
            if(is_numeric($parent_id)){
                $activities = $qb
                    ->select($select)
                    ->from(Activity::class, 'a')
                    ->where("a.id = '$parent_id'")
                    ->getQuery()
                    ->getResult();

                if (count($activities) > 0){
                    $children_activities = $qb
                        ->where('a.parent =' . $activities[0]["id"])
                        ->getQuery()
                        ->getResult();

                    $activities = array_merge($activities, $children_activities);
                }
            }elseif($parent_id == 'null'){
                $activities = $qb
                    ->select($select)
                    ->from(Activity::class, 'a')
                    ->where('a.parent IS NULL')
                    ->getQuery()
                    ->getResult();
            }
        }else{
            $activities = $qb
                ->select($select)
                ->from(Activity::class, 'a')
                ->getQuery()
                ->getResult();
        }
            return $this->restV2(200, "ok", "Done", $activities);

        }


    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     */
    public function searchAdminActivitiesV4(Request $request){
        $parent_id =$request->query->get('parent_id');
        $em = $this->getDoctrine()->getManager();
        $activities = [];
        if(isset($parent_id) ){
            if(is_numeric($parent_id)){
                $parent = $em->getRepository(Activity::class)->find($parent_id);

                if ($parent){
                    $activities = $em->getRepository(Activity::class)->findBy(array(
                        "parent"=>$parent_id
                    ));

                    array_push($activities, $parent);
                }
            }elseif($parent_id == 'null'){
                $activities = $em->getRepository(Activity::class)->findBy(array(
                    "parent"=> null
                ));
            }
        }else{
            $activities = $em->getRepository(Activity::class)->findAll();
        }
        return $this->restV2(200, "ok", "Done", $this->secureOutput($activities));

    }
}