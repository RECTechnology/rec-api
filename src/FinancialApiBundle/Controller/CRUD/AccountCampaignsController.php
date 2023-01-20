<?php

namespace App\FinancialApiBundle\Controller\CRUD;

use App\FinancialApiBundle\DataFixture\UserFixture;
use App\FinancialApiBundle\Entity\AccountChallenge;
use App\FinancialApiBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class AccountCampaignsController
 * @package App\FinancialApiBundle\Controller\CRUD
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
            /** @var User $user */
            $user = $this->getUser();
            $request->query->set('account_id', $user->getActiveGroup()->getId());
        }
        if($activeCampaignFilter){
            $response = parent::indexAction($request, $role);
            $content = json_decode($response->getContent(),true);
            $active_account_campaigns = [];
            $today = new \DateTime();
            foreach ($content['data']['elements'] as $element){
                $end_date = new \DateTime($element['campaign']['end_date']);
                if($end_date > $today){
                    $active_account_campaigns[] = $element;
                }

            }

            return $this->restV2(
                self::HTTP_STATUS_CODE_OK,
                "ok",
                "Request successful",
                array(
                    'total' => count($active_account_campaigns),
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
        return parent::searchAction($request, $role);
    }
}
