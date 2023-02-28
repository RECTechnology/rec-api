<?php

namespace App\Controller\CRUD;

use App\DataFixtures\UserFixtures;
use App\Entity\AccountChallenge;
use App\Entity\User;
use App\Entity\AccountCampaign;
use App\Entity\Campaign;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AccountCampaignsController
 * @package App\Controller\CRUD
 */
class AccountCampaignsController extends CRUDController {

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
            self::CRUD_UPDATE => self::ROLE_SUPER_ADMIN,
            self::CRUD_DELETE => self::ROLE_SUPER_ADMIN,
        ];
    }

    public function indexAction(Request $request, $role)
    {
        //filter by user if role==user
        $user = $this->getUser();
        $activeCampaignFilter = false;
        if($role === 'user'){
            if ($request->query->has('account_id')){
                $request->query->remove('account_id');
            }
            if($request->query->has('only_active_campaigns')){
                $only_active_campaigns = $request->query->get('only_active_campaigns');
                if($only_active_campaigns === '1' || $only_active_campaigns === 'true'){
                    $activeCampaignFilter = true;
                }
            }

            $request->query->set('account_id', $user->getActiveGroup()->getId());
        }
        if($activeCampaignFilter){

            $response = parent::indexAction($request, $role);
            $content = json_decode($response->getContent(),true);
            $active_account_campaigns = [];
            /** @var AccountCampaign $element */
            foreach ($content['data']['elements'] as $element){
                if($element['campaign']['status'] === Campaign::STATUS_ACTIVE){
                    $active_account_campaigns[] = $element;
                }
            }

            //calculate all spent and accumulated in all user accounts
            $accounts = $user->getGroups();
            $em = $this->get('doctrine.orm.entity_manager');
            $total_spent_bonus = 0;
            $total_accumulated_bonus = 0;
            foreach ($accounts as $account){
                if($account->getKycManager() === $user){
                    $account_campaigns = $em->getRepository(AccountCampaign::class)->findBy(array('account' => $account));
                    /** @var AccountCampaign $account_campaign */
                    foreach ($account_campaigns as $account_campaign){
                        if($account_campaign->getCampaign()->getStatus() === Campaign::STATUS_ACTIVE){
                            $total_accumulated_bonus += $account_campaign->getAcumulatedBonus();
                            $total_spent_bonus += $account_campaign->getSpentBonus();
                        }
                    }
                }
            }

            return $this->rest(
                self::HTTP_STATUS_CODE_OK,
                "ok",
                "Request successful",
                array(
                    'total' => count($active_account_campaigns),
                    'total_accumulated_bonus' => $total_accumulated_bonus,
                    'total_spent_bonus' => $total_spent_bonus,
                    'elements' => $active_account_campaigns
                )
            );

        }

        return parent::indexAction($request, $role);
    }

    public function showAction($role, $id)
    {
        //Check if the id is owned by user
        return parent::showAction($role, $id);
    }

    public function searchAction(Request $request, $role)
    {

        if($request->query->has('search')){
            $search = $request->query->get('search');
            $em = $this->get('doctrine.orm.entity_manager');
            $qb = $em->createQueryBuilder();
            $qb->from(User::class, 'u');

            $fields = array(
                'username',
                'phone',
                'name'
            );

            $searchFilter = $qb->expr()->orX();
            foreach ($fields as $field){
                $searchFilter->add(
                    $qb->expr()->like(
                        'u.' . $field,
                        $qb->expr()->literal('%' . $search . '%')
                    )
                );
            }
            $where = $qb->expr()->andX();
            $where->add($searchFilter);

            $users = $qb->select('u')
                ->where($where)
                ->getQuery()
                ->getResult();

            $all_account_campaigns = [];
            $total_accumulated_bonus = 0;
            $total_spent_bonus = 0;
            if(count($users) > 0){
                foreach ($users as $user){
                    $user_account_campaigns = $this->searchByUser($user);
                    foreach ($user_account_campaigns as $user_account_campaign){
                        $all_account_campaigns[] = $user_account_campaign;
                    }
                }
                return $this->rest(
                    self::HTTP_STATUS_CODE_OK,
                    "ok",
                    "Request successful",
                    array(
                        'total' => count($all_account_campaigns),
                        'total_accumulated_bonus' => $total_accumulated_bonus,
                        'total_spent_bonus' => $total_spent_bonus,
                        'elements' => $all_account_campaigns
                    )
                );
            }

            return parent::searchAction($request, $role);

        }

        return parent::searchAction($request, $role);

    }

    private function searchByUser($user){
        $em = $this->get('doctrine.orm.entity_manager');

        $accounts = $user->getGroups();
        $account_campaigns = [];
        /** @var Group $account */
        foreach ($accounts as $account){
            if($account->getKycManager() === $user){
                $account_campaign = $em->getRepository(AccountCampaign::class)->findOneBy(array('account' => $account));
                $account_campaigns[] = $account_campaign;
            }
        }

        return $this->secureOutput($account_campaigns);
    }
}
