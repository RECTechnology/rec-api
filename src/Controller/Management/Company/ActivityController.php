<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace App\Controller\Management\Company;

use App\Entity\Activity;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\BaseApiController;
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

        return $this->rest(200, "ok", "Done", $activities);

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
            'p.id as parent_id, ' .
            'p.name as parent_name, ' .
            'p.name_es as parent_name_es, ' .
            'p.name_ca as parent_name_ca';

        $qb->select($select)->from(Activity::class, 'a')->leftJoin('a.parent', 'p');

        if(isset($parent_id) ){
            if(is_numeric($parent_id)){
                $activities = $qb
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
                    ->where('a.parent IS NULL')
                    ->getQuery()
                    ->getResult();
            }
        }else{
            $activities = $qb
                ->getQuery()
                ->getResult();
        }
            return $this->rest(200, "ok", "Done", $activities);

        }


    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     */
    public function searchAdminActivitiesV4(Request $request){

        $parent_id =$request->query->get('parent_id');
        $name = $request->query->get('search', '');
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $sort = $request->query->get('sort', 'id');
        $order = $request->query->getAlpha('order', 'DESC');

        $em = $this->getDoctrine()->getManager();

        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();

        $select = 'a.id, ' .
            'a.name, ' .
            'a.name_es, ' .
            'a.name_ca, ' .
            'identity(a.parent) as parent';

        $qb->select($select)->from(Activity::class, 'a');

        if($name !== '') {
            $qb->andWhere("(a.name LIKE '%$name%' OR a.name_es LIKE '%$name%' OR a.name_ca LIKE '%$name%')");
        }

        if(isset($parent_id)){
            if(is_numeric($parent_id)){

                $qb->andWhere("(a.id = '$parent_id' OR a.parent ='$parent_id')");

            }elseif($parent_id === 'null'){

                $qb->andWhere("a.parent IS NULL");
            }
        }

        $allActivities = $qb->getQuery()->getResult();
        $totalActivities = count($allActivities);

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->addOrderBy('a.'.$sort, $order);

        $activities = $qb->getQuery()->getResult();

        $response = array(
            'elements' => $activities,
            'total' => $totalActivities
        );


        return $this->rest(200, "ok", "Done", $response);

    }
}
