<?php

namespace App\FinancialApiBundle\Command;

use App\FinancialApiBundle\DependencyInjection\App\Commons\DiscourseApiManager;
use App\FinancialApiBundle\Entity\AccountAward;
use App\FinancialApiBundle\Entity\Group;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class SendWeeklyB2BReportCommand extends SynchronizedContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:b2b:weekly:report')
            ->setDescription('Send weekly email with B2B report');
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        /** @var DiscourseApiManager $discourseManager */
        $discourseManager = $this->getContainer()->get('net.app.commons.discourse.api_manager');
        $admin_username = $this->getContainer()->getParameter("discourse_admin_username");
        $admin_api_key = $this->getContainer()->getParameter("discourse_admin_api_key");
        $emails_list = $this->getContainer()->getParameter("resume_admin_emails_list");

        $today = new \DateTime();
        $oneWeekAgo = new \DateTime('-7 days');

        $credentials = array(
            'Api-Key: '.$admin_api_key,
            'Api-Username: '. $admin_username
        );
        $response = $discourseManager->adminBridgeCall($credentials,'/admin/reports/bulk.json?reports[consolidated_page_views][cache]=true&reports[consolidated_page_views][facets][]=prev_period&reports[consolidated_page_views][start_date]='.$oneWeekAgo->format('Y-m-d').'&reports[consolidated_page_views][end_date]='.$today->format('Y-m-d').'&reports[signups][cache]=true&reports[signups][facets][]=prev_period&reports[signups][start_date]='.$oneWeekAgo->format('Y-m-d').'&reports[signups][end_date]='.$today->format('Y-m-d').'&reports[topics][cache]=true&reports[topics][facets][]=prev_period&reports[topics][start_date]='.$oneWeekAgo->format('Y-m-d').'&reports[topics][end_date]='.$today->format('Y-m-d').'&reports[posts][cache]=true&reports[posts][facets][]=prev_period&reports[posts][start_date]='.$oneWeekAgo->format('Y-m-d').'&reports[posts][end_date]='.$today->format('Y-m-d').'', 'GET');

        $consolidatedPageViews = $response['reports'][0];
        $signUps = $response['reports'][1];
        $topics = $response['reports'][2];
        $posts = $response['reports'][3];

        $fs = new Filesystem();
        $tmpFilename = "/tmp/statistics.csv";
        $fs->touch($tmpFilename);
        $fp = fopen($tmpFilename, 'w');

        $report = [];

        //Logged users/anon/crawlers by day
        $titleViews = array($consolidatedPageViews['description']);
        $report[] = $titleViews;
        $header_views = array('date', 'number');
        foreach ($consolidatedPageViews['data'] as $consolidatedPageView){
            $title = array($consolidatedPageView['label']);
            $report[] = $title;
            $report[] = $header_views;

            foreach ($consolidatedPageView['data'] as $data){
                $row = array($data['x'], $data['y']);
                $report[] = $row;
            }
        }

        //SignUps per day
        $titleSignUps = array($signUps['description']);
        $report[] = $titleSignUps;
        $report[] = $header_views;

        foreach ($signUps['data'] as $data){
            $row = array($data['x'], $data['y']);
            $report[] = $row;
        }

        //Topics per day
        $titleTopics = array($topics['description']);
        $report[] = $titleTopics;
        $report[] = $header_views;

        foreach ($topics['data'] as $data){
            $row = array($data['x'], $data['y']);
            $report[] = $row;
        }

        //Posts per day
        $titlePosts = array($posts['description']);
        $report[] = $titlePosts;
        $report[] = $header_views;

        foreach ($posts['data'] as $data){
            $row = array($data['x'], $data['y']);
            $report[] = $row;
        }

        //topics per category per day
        $categories = $discourseManager->adminBridgeCall($credentials,'/categories.json', 'GET');
        foreach ($categories['category_list']['categories'] as $category){
            $cat_id = $category['id'];
            $titleCat = array('Category: '.$category['name'], 'Total topics: '.$category['topic_count'], 'Total posts: '.$category['post_count']);
            $report[] = $titleCat;
            $report[] = $header_views;
            $response = $discourseManager->adminBridgeCall($credentials,'/admin/reports/bulk.json?reports[topics][cache]=true&reports[topics][facets][]=prev_period&reports[topics][start_date]='.$oneWeekAgo->format('Y-m-d').'&reports[topics][end_date]='.$today->format('Y-m-d').'&reports[topics][filters][category]='.$cat_id, 'GET');
            $catData = $response['reports'][0]['data'];

            foreach ($catData as $data){
                $row = [$data['x'], $data['y']];
                $report[] = $row;
            }
        }

        //get all b2b accounts
        $qb = $em->getRepository(Group::class)->createQueryBuilder('g');
        $accounts = $qb->select("g")
            ->where($qb->expr()->isNotNull("g.rezero_b2b_user_id"))
            ->getQuery()->getResult();

        $titleAwards = array('Awards by account');
        $headerAwards = array('account_id', 'account_name','score', 'award', 'level');
        $report[] = $titleAwards;
        $report[] = $headerAwards;
        foreach ($accounts as $account){
            //TODO find awards by account
            $awards = $em->getRepository(AccountAward::class)->findBy(array(
                'account' => $account
            ));
            /** @var AccountAward $award */
            foreach ($awards as $award){
                $report[] = array($account->getId(), $account->getName(), $award->getScore(), $award->getAward()->getName(), $award->getLevel());
            }
        }

        foreach ($report as $row){
            fputcsv($fp, $row, ';');
        }

        $this->sendEmail($emails_list, 'Statistics', 'statistics.csv');
    }

    private function sendEmail($emails, $subject, $fileName){

        $no_replay = $this->getContainer()->getParameter('no_reply_email');

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($no_replay)
            ->setTo($emails)
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('FinancialApiBundle:Email:empty_email.html.twig',
                        array(
                            'mail' => [
                                'subject' => $subject,
                                'body' => "Resumen semanal",
                                'lang' => "es"
                            ],
                            'app' => [
                                'landing' => 'rec.barcelona'
                            ]
                        )
                    )
            )
            ->setContentType('text/html');

        $message->attach(\Swift_Attachment::newInstance(file_get_contents('/tmp/'.$fileName), $fileName));

        $this->getContainer()->get('mailer')->send($message);
    }
}