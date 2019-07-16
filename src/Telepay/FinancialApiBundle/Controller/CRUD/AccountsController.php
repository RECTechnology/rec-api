<?php

namespace Telepay\FinancialApiBundle\Controller\CRUD;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use JMS\Serializer\Serializer;
use ReflectionException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiControllerV2;
use Telepay\FinancialApiBundle\Entity\Group;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\Offer;

/**
 * Class AccountsController
 * @package Telepay\FinancialApiBundle\Controller\CRUD
 */
class AccountsController extends BaseApiControllerV2 {


    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:Group";
    }

    function getNewEntity()
    {
        return new Group();
    }

    /**
     * @return array
     */
    function getCRUDGrants()
    {
        return [
            self::CRUD_METHOD_SEARCH => self::ROLE_PUBLIC,
            self::CRUD_METHOD_INDEX => self::ROLE_USER,
            self::CRUD_METHOD_SHOW => self::ROLE_USER,
            self::CRUD_METHOD_UPDATE => self::ROLE_SUPER_ADMIN,
            self::CRUD_METHOD_DELETE => self::ROLE_SUPER_ADMIN,
        ];
    }





    /**
     * @param Request $request
     * @param $role
     * @return Response
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function search(Request $request){
        $limit = $request->query->getInt('limit', 10);
        $offset = $request->query->getInt('offset', 0);
        $query = json_decode($request->query->get('query', '{}'));
        $sort = $request->query->get('sort', 'id');
        $order = $request->query->getAlpha('order', 'DESC');

        $rect_box = isset($query->rect_box)?$query->rect_box: [-90.0, -90.0, 90.0, 90.0];
        $search = isset($query->search)?$query->search: '';

        $account_subtype = isset($query->subtype)? strtoupper($query->subtype): '';

        if(!in_array($account_subtype, ["RETAILER", "WHOLESALE", ""])){
            throw new HttpException(400, "Invalid subtype '$account_subtype', valid options: 'retailer', 'wholesale'");
        }

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        /** @var QueryBuilder $qb */
        $qb = $em->createQueryBuilder();
        $and = $qb->expr()->andX();
        $searchFields = [
            'a.id',
            'a.name',
            'a.phone',
            'a.cif',
            'a.city',
            'a.street',
            'a.description',
            'o.discount',
            'o.description',
            'c.cat',
            'c.esp',
            'c.eng'
        ];
        $like = $qb->expr()->orX();
        foreach ($searchFields as $field) {
            $like->add($qb->expr()->like($field, $qb->expr()->literal('%' . $search . '%')));
        }
        $and->add($like);
        $and->add($qb->expr()->eq('a.on_map', 1));
        //geo query
        $and->add($qb->expr()->gt('a.latitude', $rect_box[0]));
        $and->add($qb->expr()->lt('a.latitude', $rect_box[2]));
        $and->add($qb->expr()->gt('a.longitude', $rect_box[1]));
        $and->add($qb->expr()->lt('a.longitude', $rect_box[3]));

        $and->add($qb->expr()->eq('a.type', $qb->expr()->literal('COMPANY')));

        if($account_subtype != '')
            $and->add($qb->expr()->like('a.subtype', $qb->expr()->literal($account_subtype)));


        $only_with_offers = isset($query->only_with_offers)? $query->only_with_offers: 0;

        if($only_with_offers == 1) {

            $qbAux = $em->createQueryBuilder()
                ->select('count(o2)')
                ->from(Offer::class, 'o2')
                ->where($qb->expr()->eq('o2.company', 'a.id'));
            $and->add($qb->expr()->gt("(" . $qbAux->getDQL() . ")", $qb->expr()->literal(0)));
        }

        $qb = $qb
            ->distinct()
            ->from(Group::class, 'a')
            ->leftJoin('a.offers', 'o')
            ->leftJoin('a.category', 'c')
            ->where($and);

        $total = $qb
            ->select('count(distinct(a))')
            ->getQuery()
            ->getSingleScalarResult();

        $qbAux2 = $em->createQueryBuilder()
            ->select('count(o3)')
            ->from(Offer::class, 'o3')
            ->where($qb->expr()->eq('o3.company', 'a.id'));

        $elements = $qb
            ->select('a as account')
            ->addSelect('(' . $qbAux2->getDQL() . ') as offer_count')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy($sort == 'offer_count'? $sort: 'a.' . $sort, $order)
            ->getQuery()
            ->getResult();

        return [$total, $elements];
    }

}
