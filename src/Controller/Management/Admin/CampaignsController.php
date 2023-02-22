<?php

namespace App\Controller\Management\Admin;

use App\Entity\AccountCampaign;
use App\Entity\Campaign;
use DateTimeZone;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Controller\BaseApiController;
use App\Entity\User;

/**
 * Class CampaignsController
 * @package App\Controller\Admin
 */
class CampaignsController extends BaseApiController{
    function getRepositoryName(){
        return "FinancialApiBundle:Campaign";
    }

    function getNewEntity(){
        return new Campaign();
    }

    public function listUsersByCampaign($id){
        $admin_user = $this->get('security.token_storage')->getToken()->getUser();
        if(!$admin_user->hasRole('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        /** @var Campaign $campaign */
        $campaign = $this->getRepository()->find($id);

        if(!$campaign) throw new HttpException(404, 'Campaign not found');

        [$response_users, $total_accumulated_bonus, $total_spent_bonus] = $this->getListUsersByCampaign($campaign);

        return $this->rest(
            200,
            "ok",
            "Request successful",
            array(
                'total' => count($response_users),
                'elements' => $response_users,
                'total_accumulated_bonus' => $total_accumulated_bonus,
                'total_spent_bonus' => $total_spent_bonus
            )
        );
    }

    public function exportUsersByCampaign($id){
        $admin_user = $this->get('security.token_storage')->getToken()->getUser();
        if(!$admin_user->hasRole('ROLE_SUPER_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        /** @var Campaign $campaign */
        $campaign = $this->getRepository()->find($id);
        [$response_users, $total_accumulated_bonus, $total_spent_bonus] = $this->getListUsersByCampaign($campaign);
        return $this->prepareDocument($response_users);
    }

    private function prepareDocument($elements){
        $now = new \DateTime("now", new DateTimeZone('Europe/Madrid'));
        $dwFilename = "export-users_by_campaign-" . $now->format('Y-m-d\TH-i-sO') . ".csv";

        $fs = new Filesystem();
        $tmpFilename = "/tmp/$dwFilename";
        $fs->touch($tmpFilename);
        $fp = fopen($tmpFilename, 'w');

        foreach ($elements as $element){
            fputcsv($fp, $element, ";");
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $dwFilename . '"');
        $response->headers->set('Content-Length', filesize($tmpFilename));

        $response->setContent(file_get_contents($tmpFilename));
        $fs->remove($tmpFilename);
        return $response;
    }

    private function getListUsersByCampaign(Campaign $campaign){
        $em = $this->getDoctrine()->getManager();
        $account_campaigns = $em->getRepository(AccountCampaign::class)->findBy(array('campaign' => $campaign));
        $list_of_users = [];

        /** @var AccountCampaign $account_campaign */
        foreach ($account_campaigns as $account_campaign){
            /** @var User $kyc_manager */
            $kyc_manager = $account_campaign->getAccount()->getKycManager();
            if(in_array($kyc_manager, $list_of_users)){
                $existent_kyc_manager_index = array_search($kyc_manager, $list_of_users);
                /** @var User $existent_kyc_manager */
                $existent_kyc_manager = $list_of_users[$existent_kyc_manager_index];
                $existent_kyc_manager->setAccumulatedBonus($existent_kyc_manager->getAccumulatedBonus() + $account_campaign->getAcumulatedBonus());
                $existent_kyc_manager->setSpentBonus($existent_kyc_manager->getSpentBonus() + $account_campaign->getSpentBonus());
                $list_of_users[$existent_kyc_manager_index] = $existent_kyc_manager;

            }else{
                $kyc_manager->setAccumulatedBonus($account_campaign->getAcumulatedBonus());
                $kyc_manager->setSpentBonus($account_campaign->getSpentBonus());
                $list_of_users[] = $kyc_manager;
            }
        }
        $response_users = [];
        $total_accumulated_bonus = 0;
        $total_spent_bonus = 0;
        /** @var User $user_on_list */
        foreach($list_of_users as $user_on_list){
            $userData = array(
                'id' => $user_on_list->getId(),
                'name' => $user_on_list->getKycValidations()->getName() .' '. $user_on_list->getKycValidations()->getLastName(),
                'username' => $user_on_list->getUsername(),
                'phone' => $user_on_list->getPhone(),
                'accumulated_bonus' => $user_on_list->getAccumulatedBonus() ?? 0,
                'spent_bonus' => $user_on_list->getSpentBonus() ?? 0
            );

            $response_users[] = $userData;
            $total_accumulated_bonus += $userData['accumulated_bonus'];
            $total_spent_bonus += $userData['spent_bonus'];
        }

        return [$response_users, $total_accumulated_bonus, $total_spent_bonus];
    }

}
