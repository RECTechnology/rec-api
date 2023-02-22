<?php

namespace App\Controller\CRUD;

use App\Entity\Group;
use App\Entity\Qualification;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class QualificationsController extends CRUDController
{
    /**
     * @return array
     */
    function getCRUDGrants()
    {
        return [
            self::CRUD_SEARCH => self::ROLE_SUPER_ADMIN,
            self::CRUD_INDEX => self::ROLE_USER,
            self::CRUD_SHOW => self::ROLE_SUPER_ADMIN,
            self::CRUD_EXPORT => self::ROLE_SUPER_ADMIN,
            self::CRUD_CREATE => self::ROLE_SUPER_ADMIN,
            self::CRUD_UPDATE => self::ROLE_USER,
            self::CRUD_DELETE => self::ROLE_SUPER_ADMIN,
        ];
    }


    public function indexAction(Request $request, $role)
    {
        /** @var User $user */
        $user = $this->getUser();
        $reviewerAccount = $user->getActiveGroup();

        //TODO check if group_id has been sent and remove it to ensure only can see their own qualifications

        $request->query->add(array('reviewer_id' => $reviewerAccount->getId()));

        return parent::indexAction($request, $role);
    }

    public function updateAction(Request $request, $role, $id)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var Group $account */
        $account = $user->getActiveGroup();

        //TODO make some checks
        $qualificationRepo = $this->getRepository();
        $qualification = $qualificationRepo->findOneBy(array('id' => $id, 'reviewer' => $account, 'status' => Qualification::STATUS_PENDING));
        if(!$qualification) throw new HttpException(404, "Qualification not found");
        return parent::updateAction($request, $role, $id);
    }

}