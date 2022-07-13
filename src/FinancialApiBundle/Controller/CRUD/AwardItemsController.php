<?php

namespace App\FinancialApiBundle\Controller\CRUD;

use App\FinancialApiBundle\Entity\AccountAward;
use App\FinancialApiBundle\Entity\AccountAwardItem;
use App\FinancialApiBundle\Entity\Award;
use App\FinancialApiBundle\Entity\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AwardItemsController extends CRUDController
{
    /**
     * @return array
     */
    function getCRUDGrants()
    {
        return [
            self::CRUD_SEARCH => self::ROLE_SUPER_ADMIN,
            self::CRUD_INDEX => self::ROLE_SUPER_ADMIN,
            self::CRUD_SHOW => self::ROLE_SUPER_ADMIN,
            self::CRUD_EXPORT => self::ROLE_SUPER_ADMIN,
            self::CRUD_CREATE => self::ROLE_SUPER_ADMIN,
            self::CRUD_UPDATE => self::ROLE_SUPER_ADMIN,
            self::CRUD_DELETE => self::ROLE_SUPER_ADMIN,
        ];
    }

    public function searchAction(Request $request, $role)
    {
        return parent::searchAction($request, $role);
    }

    public function indexAction(Request $request, $role)
    {
        return parent::indexAction($request, $role);
    }

    public function showAction($role, $id)
    {
        return parent::showAction($role, $id);
    }

    public function exportAction(Request $request, $role)
    {
        return parent::exportAction($request, $role);
    }

    public function indexByAccountAction(Request $request, $role, $id)
    {
        $em = $this->get('doctrine')->getEntityManager();
        $repo = $em->getRepository(AccountAwardItem::class);

        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $sort = $request->query->get('sort', "id");
        $order = strtoupper($request->query->get('order', "DESC"));

        if(!in_array($order, ["ASC", "DESC"]))
            throw new HttpException(400, "Invalid order: it must be ASC or DESC");

        $award_id = $request->query->get('award_id', false);

        $account = $em->getRepository(Group::class)->find($id);
        if(!$account) throw new HttpException(404, 'Account not found');

        $select = 'i.id, ' .
            'i.created, '.
            'i.score, '.
            'awd.id as award_id, '.
            'awd.name, '.
            'awd.name_es, '.
            'awd.name_ca, '.
            'i.action, '.
            'i.category, '.
            'i.scope, '.
            'i.topic_id, '.
            'i.post_id, '.
            'a.id as account_id, '.
            'a.name as account_name';

        //TODO get Award items by account
        $query = $repo->createQueryBuilder('i');
        $query
            ->select($select)
            ->leftJoin('i.account_award', 'aw') // The missing join
            ->leftJoin('aw.account', 'a') // The missing join
            ->leftJoin('aw.award', 'awd') // The missing join
            ->where('a.id = :id') // where p.name like %keyword%
            ->orderBy('i.'.$sort, $order)
            ->setParameter('id', $id)
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if($award_id){
            $award = $em->getRepository(Award::class)->find($award_id);
            if(!$award) throw new HttpException(404, 'Award not found');
            $query->andWhere('awd.id = :award_id')
                ->setParameter('award_id', $award_id);
        }

        $result = $query->getQuery()->getResult();

        return $this->restV2(
            self::HTTP_STATUS_CODE_OK,
            "ok",
            "Request successful",
            array(
                'total' => count($result),
                'elements' => $result,
                'limit' => $limit,
                'offset' => $offset
            )
        );
    }
}