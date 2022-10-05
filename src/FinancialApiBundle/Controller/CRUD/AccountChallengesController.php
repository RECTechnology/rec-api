<?php

namespace App\FinancialApiBundle\Controller\CRUD;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\AccountChallenge;
use App\FinancialApiBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class AccountChallengesController
 * @package App\FinancialApiBundle\Controller\CRUD
 */
class AccountChallengesController extends CRUDController {


    public function indexAction(Request $request, $role)
    {
        //add filter if is user role
        if($role === 'user' || $role === 'manager' || $role === 'self'){
            if ($request->query->has('account_id')){
                $request->query->remove('account_id');
            }
            /** @var User $user */
            $user = $this->getUser();
            $request->query->set('account_id', $user->getActiveGroup()->getId());
        }

        return parent::indexAction($request, $role);
    }

    public function showAction($role, $id)
    {
        if($role === 'user' || $role === 'manager' || $role === 'self'){
            $em = $this->container->get('doctrine')->getManager();
            $accountChallenge = $em->getRepository(AccountChallenge::class)->find($id);
            if($accountChallenge->getAccount()->getId() !== $this->getUser()->getActiveGroup()->getid()){
                throw new HttpException(403, 'You do not have permissions to get this resource');
            }
        }
        return parent::showAction($role, $id);
    }
}
