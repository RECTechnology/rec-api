<?php

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use App\FinancialApiBundle\Entity\AccountAward;
use App\FinancialApiBundle\Entity\AccountAwardItem;
use App\FinancialApiBundle\Entity\Award;
use App\FinancialApiBundle\Entity\AwardScoreRule;
use App\FinancialApiBundle\Entity\ConfigurationSetting;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\NFTTransaction;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class AwardHandler
{
    private const POST_CREATE_EVENT = 'post_created';
    private const TOPIC_CREATE_EVENT = 'topic_created';
    private const POST_LIKED_EVENT = 'post_liked';
    private const RECEIVED_LIKE_EVENT = 'received_like';
    private const ACCEPTED_SOLUTION_EVENT = 'accepted_solution';

    private const ALL_EVENTS = [
        self::POST_CREATE_EVENT => 'comment',
        self::TOPIC_CREATE_EVENT => 'create_topic',
        self::POST_LIKED_EVENT => 'like',
        self::RECEIVED_LIKE_EVENT => 'receive_like',
        self::ACCEPTED_SOLUTION_EVENT => 'accepted_solution',
    ];

    private $doctrine;

    /** @var Logger $logger */
    private $logger;

    private $container;

    public function __construct($doctrine, ContainerInterface $container,Logger $logger)
    {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->container = $container;
    }

    public function handleDiscourseNotification(Request $request): void
    {
        $data = $request->request->all();
        $headers = $request->headers->all();

        $event = $headers['x-discourse-event'][0];

        $this->logger->info('AWARD-HANDLER: Discourse notification event: '.$event);

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


            //check if service is enabled in configuration settings
            $nftSetting = $em->getRepository(ConfigurationSetting::class)->findOneBy(array(
                'name' => 'create_nft_wallet',
                'value' => 'enabled'
            ));

            if($nftSetting){
                $this->handleToken($data,$event);
            }
            //if its like check for received like event
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

        $this->logger->info('AWARD-HANDLER: '.$award->getName().' award item created for account '.$account->getName().' - '.$account->getId());
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


        $this->logger->info('AWARD-HANDLER: Looking for rule: event->'.$event.', category_id->'.$category_id.', scope->'.$scope);

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
        
        $this->logger->info('AWARD-HANDLER: Rule not found');
        return null;
    }

    private function handleToken($data, $event){
        switch ($event){
            case self::ACCEPTED_SOLUTION_EVENT:
                //topic owner shares his token with owner of accepted solution
                $this->logger->info('AWARD-HANDLER: share contribution token');
                $this->shareContributionToken($data);
                break;
            case self::TOPIC_CREATE_EVENT:
                //mint new contribution token
                $this->logger->info('AWARD-HANDLER: mint contribution token');
                $this->mintContributionToken($data);
                break;
            case self::POST_LIKED_EVENT:
                if($data['like']['post']['post_number'] === 1){
                    //es un like a topic
                    $this->logger->info('AWARD-HANDLER: like contribution token');
                    $this->mintLikeToken($data);
                }
                break;
            default:
                break;
        }
    }

    private function shareContributionToken($data): void
    {
        $ownerUsername = $data['solved']['owner']['username'];
        $ownerId = $data['solved']['owner']['id'];
        $colaboratorUsername = $data['solved']['post']['username'];
        $colaboratorId = $data['solved']['post']['user_id'];
        $topic_id = $data['solved']['post']['topic_id'];

        $em = $this->getEntityManager();
        $owner = $em->getRepository(Group::class)->findOneBy(array(
            'rezero_b2b_user_id' => $ownerId
        ));

        $colaborator = $em->getRepository(Group::class)->findOneBy(array(
            'rezero_b2b_user_id' => $colaboratorId
        ));

        if($owner && $colaborator){
            //share token from owner to collaborator
            //find original token
            $originalTx = $em->getRepository(NFTTransaction::class)->findOneBy(array(
                'topic_id' => $topic_id,
                'method' => NFTTransaction::NFT_MINT
            ));
            if($originalTx){
                $this->createNFTTransaction(NFTTransaction::NFT_SHARE, $owner, $colaborator, $topic_id, $originalTx);
            }


        }
    }

    private function mintContributionToken($data): void
    {
        $ownerUsername = $data['topic']['created_by']['username'];
        $ownerId = $data['topic']['created_by']['id'];

        $em = $this->getEntityManager();
        $owner = $em->getRepository(Group::class)->findOneBy(array(
            'rezero_b2b_user_id' => $ownerId
        ));

        if($owner){
            //mint new contribution token from admin
            $adminAccountId = $this->container->getParameter('id_group_root');
            $admin = $em->getRepository(Group::class)->find($adminAccountId);
            if($admin){
                $this->createNFTTransaction(NFTTransaction::NFT_MINT, $admin, $owner, $data['topic']['id'], null);
            }

        }
    }

    private function mintLikeToken($data): void
    {
        $em = $this->getEntityManager();
        $ownerId = $data['like']['post']['user_id'];
        $ownerUsername = $data['like']['post']['username'];
        $topicId = $data['like']['post']['topic_id'];
        $likerId = $data['like']['user']['id'];

        $liker = $em->getRepository(Group::class)->find($likerId);

        //find original tx
        $originalTx = $em->getRepository(NFTTransaction::class)->findOneBy(array(
            'topic_id' => $topicId,
            'method' => NFTTransaction::NFT_MINT
        ));

        if($originalTx){
            //create like transaction
            $this->createNFTTransaction(NFTTransaction::NFT_LIKE, $liker, $liker, $topicId, $originalTx);
        }

    }

    private function createNFTTransaction($method, Group $from, Group $to, $topic_id, ?NFTTransaction $originalTx): void
    {
        $em = $this->getEntityManager();
        $nftTransaction = new NFTTransaction();
        $nftTransaction->setStatus(NFTTransaction::STATUS_CREATED);
        $nftTransaction->setMethod($method);
        $nftTransaction->setFrom($from);
        $nftTransaction->setTo($to);
        $nftTransaction->setTopicId($topic_id);
        if($originalTx){
            $nftTransaction->setOriginalTokenId($originalTx->getOriginalTokenId());
        }

        $em->persist($nftTransaction);
        $em->flush();
    }
}