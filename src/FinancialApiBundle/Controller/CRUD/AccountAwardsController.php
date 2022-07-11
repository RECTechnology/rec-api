<?php

namespace App\FinancialApiBundle\Controller\CRUD;

use App\FinancialApiBundle\Entity\AccountAward;
use App\FinancialApiBundle\Entity\AccountAwardItem;
use App\FinancialApiBundle\Entity\Award;
use App\FinancialApiBundle\Entity\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AccountAwardsController extends CRUDController
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

        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);

        $award_id = $request->query->get('award_id', false);

        $account = $em->getRepository(Group::class)->find($id);
        if(!$account) throw new HttpException(404, 'Account not found');

        $awards = $em->getRepository(AccountAward::class)->findBy(array(
            'account' => $account
        ));

        $result = $this->secureOutput($awards);

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