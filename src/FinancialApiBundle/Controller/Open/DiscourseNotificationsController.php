<?php

namespace App\FinancialApiBundle\Controller\Open;

use App\FinancialApiBundle\Controller\RestApiController;
use App\FinancialApiBundle\Entity\AccountAward;
use App\FinancialApiBundle\Entity\Award;
use App\FinancialApiBundle\Entity\Group;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;

class DiscourseNotificationsController extends RestApiController
{
    private const STARTING_TOPIC_ACTION = 'starting_topic';
    private const ANSWERING_TOPIC_ACTION = 'answering_topic';
    private const ANSWERING_NEWS_ACTION = 'answering_news';
    private const ANSWERING_HELP_ACTION = 'answering_help';
    private const LIKING_POST_ACTION = 'liking_post';
    private const RECEIVE_LIKE_ACTION = 'receive_like';
    private const INIT_JOIN_PURCHASE_ACTION = 'init_join_purchase';
    private const ANSWERING_JOIN_PURCHASE_ACTION = 'answering_join_purchase';

    private const POST_CREATE_EVENT = 'post_created';
    private const POST_LIKED_EVENT = 'post_liked';
    private const USER_LOGGED_IN_EVENT = 'user_logged_in';

    private const LA_SAVIESA_AWARD = 'saviesa';
    private const LA_PARAULA_AWARD = 'paraula';


    public function notificate(Request $request, $version_number){



        if($request->server->has('x-discourse-event')){

            $this->detectAction($request);

        }
    }

    private function detectAction(Request $request){
        $em = $this->getDoctrine()->getManager();
        $data = $request->request->all();
        $headers = $request->server->all();

        $event = $headers['x-discourse-event'];

        $logger = $this->container->get('discourse.logger');
        $logger->info('Discourse notification event: '.$event);

        if($event === self::POST_CREATE_EVENT){
            $post = $data['post'];
            $post_number = $post['post_number'];
            /** @var Group $creatorAccount */
            $creatorAccount = $em->getRepository(Group::class)->findOneBy(array(
                'rezero_b2b_username' => $post['username']
            ));

            /** @var Award $awardParaula */
            $awardParaula = $em->getRepository(Award::class)->findOneBy(array(
                'canonical_name' => self::LA_PARAULA_AWARD
            ));

            if($post_number === 1){
                //start topic
                $paraulaPoints = 5;

            }else{
                //answering topic
                $category_id = $this->container->getParameter('discourse_news_category_id');
                if($category_id === $post['category_id']){
                    //answering news
                    $paraulaPoints = 2;
                }else{
                    $paraulaPoints = 2;
                }

            }

            $this->addScoreToAccount($creatorAccount, $awardParaula, $paraulaPoints);


        }elseif ($event === self::POST_LIKED_EVENT){
            $post = $data['like']['post'];
            $user = $data['user'];

            /** @var Group $giverAccount */
            $giverAccount = $em->getRepository(Group::class)->findOneBy(array(
                'rezero_b2b_username' => $post['username']
            ));

            /** @var Award $awardSaviesa */
            $awardSaviesa = $em->getRepository(Award::class)->findOneBy(array(
                'canonical_name' => self::LA_SAVIESA_AWARD
            ));

            $this->addScoreToAccount($giverAccount, $awardSaviesa, 1);

            /** @var Group $receiverAccount */
            $receiverAccount = $em->getRepository(Group::class)->findOneBy(array(
                'rezero_b2b_username' => $user['username']
            ));

            $this->addScoreToAccount($receiverAccount, $awardSaviesa, 2);
        }
    }

    private function addScoreToAccount(Group $account, Award $award, $score){
        $em = $this->getDoctrine()->getManager();

        $accountAward = $em->getRepository(AccountAward::class)->findOneBy(array(
            'account' => $account,
            'award' => $award
        ));

        if($accountAward){
            $accountAward->setScore($accountAward->getScore() + $score);
        }else{
            $accountAward = new AccountAward();
            $accountAward->setAccount($account);
            $accountAward->setAward($award);
            $accountAward->setScore($score);
            $em->persist($accountAward);
        }
        $em->flush();
    }

}