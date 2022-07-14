<?php

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use App\FinancialApiBundle\Entity\AccountAward;
use App\FinancialApiBundle\Entity\AccountAwardItem;
use App\FinancialApiBundle\Entity\Award;
use App\FinancialApiBundle\Entity\AwardScoreRule;
use App\FinancialApiBundle\Entity\ConfigurationSetting;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Qualification;
use App\FinancialApiBundle\Exception\PreconditionFailedException;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;

class AwardHandler
{
    private const POST_CREATE_EVENT = 'post_created';
    private const TOPIC_CREATE_EVENT = 'topic_created';
    private const POST_LIKED_EVENT = 'post_liked';
    private const RECEIVED_LIKE_EVENT = 'received_like';

    private const ALL_EVENTS = [
        self::POST_CREATE_EVENT => 'comment',
        self::TOPIC_CREATE_EVENT => 'start_topic',
        self::POST_LIKED_EVENT => 'like',
        self::RECEIVED_LIKE_EVENT => 'receive_like',
    ];

    private $doctrine;

    /** @var Logger $logger */
    private $logger;

    public function __construct($doctrine, Logger $logger)
    {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    public function handleDiscourseNotification(Request $request): void
    {
        $data = $request->request->all();
        $headers = $request->headers->all();

        $event = $headers['x-discourse-event'][0];

        $this->logger->info('Discourse notification event: '.$event);

        if(isset(self::ALL_EVENTS[$event])){
            $em = $this->getEntityManager();

            [$category_id, $username, $scope] = $this->getInfoFromNotification($data, $event);

            if(!$username) return;
            if(!$category_id) return;

            /** @var Group $creatorAccount */
            $creatorAccount = $em->getRepository(Group::class)->findOneBy(array(
                'rezero_b2b_username' => $username
            ));

            if(!$creatorAccount) return;

            /** @var AwardScoreRule $awardRule */
            $awardRule = $this->getMatchedRule($event, $category_id, $scope);

            if($awardRule){
                $this->createAccountAwardItem($creatorAccount, $awardRule);
            }

            //if its like check for received like
            if($event === self::POST_LIKED_EVENT){
                $event = self::RECEIVED_LIKE_EVENT;
                $request->headers->set('x-discourse-event', $event);
                $this->handleDiscourseNotification($request);
            }
        }

    }

    private function getEntityManager(){
        return $this->doctrine->getManager();
    }

    private function getAccountAward(Group $account, Award $award): AccountAward
    {
        $em = $this->getEntityManager();
        $accountAward = $em->getRepository(AccountAward::class)->findOneBy(array(
            'account' => $account,
            'award' => $award
        ));

        if(!$accountAward){
            $accountAward = new AccountAward();
            $accountAward->setScore(0);
            $accountAward->setAward($award);
            $accountAward->setAccount($account);

            $em->persist($accountAward);
            $em->flush();
        }

        return $accountAward;
    }

    private function createAccountAwardItem(Group $account, AwardScoreRule $awardRule): void
    {
        $em = $this->getEntityManager();
        $award = $awardRule->getAward();
        $accountAward = $this->getAccountAward($account, $award);
        $awardItem = new AccountAwardItem();
        $awardItem->setScore($awardRule->getScore());
        $awardItem->setAccountAward($accountAward);
        $awardItem->setAction($awardRule->getAction());
        $awardItem->setCategory($awardRule->getCategory());
        $awardItem->setScope($awardRule->getScope());

        $em->persist($awardItem);

        $accountAward->setScore($accountAward->getScore() + $awardRule->getScore());
        $em->flush();
    }

    private function getInfoFromNotification($data, $event): array
    {

        switch ($event){
            case self::POST_CREATE_EVENT:
                if($data['post']['post_number'] === 1){
                    //is topic not post, ignore notification
                    $response = [null, null, null];
                }else{
                    $response = [$data['post']['category_id'], $data['post']['username'], 'post'];
                }
                break;
            case self::TOPIC_CREATE_EVENT:
                $response = [$data['topic']['category_id'], $data['topic']['created_by']['username'], 'topic'];
                break;
            case self::POST_LIKED_EVENT:
                $scope = 'post';
                if($data['like']['post']['post_number'] === 1){
                    $scope = 'topic';
                }
                $response = [$data['like']['post']['category_id'], $data['like']['user']['username'], $scope];
                break;
            case self::RECEIVED_LIKE_EVENT:
                $scope = 'post';
                if($data['like']['post']['post_number'] === 1){
                    $scope = 'topic';
                }
                $response = [$data['like']['post']['category_id'], $data['like']['post']['username'], $scope];
                break;
            default:
                 $response = [null, null, null];

        }
        return $response;
    }

    private function getMatchedRule($event, $category_id, $scope){
        $em = $this->getEntityManager();

        $awardRule = $em->getRepository(AwardScoreRule::class)->findOneBy(array(
            'action' => self::ALL_EVENTS[$event],
            'category' => $category_id,
            'scope' => $scope
        ));

        if($awardRule) return $awardRule;

        $awardRule = $em->getRepository(AwardScoreRule::class)->findOneBy(array(
            'action' => self::ALL_EVENTS[$event],
            'scope' => $scope,
            'category' => null
        ));

        if($awardRule) return $awardRule;

        $awardRule = $em->getRepository(AwardScoreRule::class)->findOneBy(array(
            'action' => self::ALL_EVENTS[$event],
            'scope' => null,
            'category' => $category_id
        ));

        if($awardRule) return $awardRule;

        return null;
    }
}