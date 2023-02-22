<?php

namespace App\Command;

use App\DependencyInjection\Commons\DiscourseApiManager;
use App\DependencyInjection\Commons\MailerAwareTrait;
use App\Entity\AccountAward;
use App\Entity\AccountAwardItem;
use App\Entity\Group;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\Email;

class SendWeeklyB2BReportCommand extends SynchronizedContainerAwareCommand
{
    use MailerAwareTrait;

    protected function configure()
    {
        $this
            ->setName('rec:b2b:weekly:report')
            ->setDescription('Send weekly email with B2B report within two dates, with two options (start_date and finish_date).
            If you dont send any option, will be assigned automatically')
            ->addOption(
                'start_date',
                null,
                InputOption::VALUE_REQUIRED
            )
            ->addOption(
                'finish_date',
                null,
                InputOption::VALUE_REQUIRED
            );
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output)
    {
        $datesAreRight = true;

        if($input->getOption('start_date') && !$input->getOption('finish_date')){
            $datesAreRight = false;
            $output->writeln('Error: If the start_date is sent, finish_date is a required field.');
        }

        if ($datesAreRight)
        {
            $em = $this->container->get('doctrine.orm.entity_manager');

            /** @var DiscourseApiManager $discourseManager */
            $discourseManager = $this->container->get('net.app.commons.discourse.api_manager');
            $admin_username = $this->container->getParameter("discourse_admin_username");
            $admin_api_key = $this->container->getParameter("discourse_admin_api_key");
            $emails_list = $this->container->getParameter("resume_admin_emails_list");

            $today = new \DateTime();
            $oneWeekAgo = new \DateTime('-7 days');

            if ($input->getOption('start_date')) {
                $oneWeekAgo = new \DateTime("{$input->getOption('start_date')}");
            }

            if ($input->getOption('finish_date')) {
                $today = new \DateTime("{$input->getOption('finish_date')}");
            }

            $credentials = array(
                'Api-Key: ' . $admin_api_key,
                'Api-Username: ' . $admin_username
            );
            $response = $discourseManager->adminBridgeCall($credentials, '/admin/reports/bulk.json?reports[consolidated_page_views][cache]=true&reports[consolidated_page_views][facets][]=prev_period&reports[consolidated_page_views][start_date]=' . $oneWeekAgo->format('Y-m-d') . '&reports[consolidated_page_views][end_date]=' . $today->format('Y-m-d') . '&reports[signups][cache]=true&reports[signups][facets][]=prev_period&reports[signups][start_date]=' . $oneWeekAgo->format('Y-m-d') . '&reports[signups][end_date]=' . $today->format('Y-m-d') . '&reports[topics][cache]=true&reports[topics][facets][]=prev_period&reports[topics][start_date]=' . $oneWeekAgo->format('Y-m-d') . '&reports[topics][end_date]=' . $today->format('Y-m-d') . '&reports[posts][cache]=true&reports[posts][facets][]=prev_period&reports[posts][start_date]=' . $oneWeekAgo->format('Y-m-d') . '&reports[posts][end_date]=' . $today->format('Y-m-d') . '', 'GET');
            $output->writeln("Exporting data from {$oneWeekAgo->format('Y-m-d')} to {$today->format('Y-m-d')}");

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
            foreach ($consolidatedPageViews['data'] as $consolidatedPageView) {
                $title = array($consolidatedPageView['label']);
                $report[] = $title;
                $report[] = $header_views;

                foreach ($consolidatedPageView['data'] as $data) {
                    $row = array($data['x'], $data['y']);
                    $report[] = $row;
                }
            }

            //SignUps per day
            $titleSignUps = array($signUps['description']);
            $report[] = $titleSignUps;
            $report[] = $header_views;

            foreach ($signUps['data'] as $data) {
                $row = array($data['x'], $data['y']);
                $report[] = $row;
            }

            //Topics per day
            $titleTopics = array($topics['description']);
            $report[] = $titleTopics;
            $report[] = $header_views;

            foreach ($topics['data'] as $data) {
                $row = array($data['x'], $data['y']);
                $report[] = $row;
            }

            //Posts per day
            $titlePosts = array($posts['description']);
            $report[] = $titlePosts;
            $report[] = $header_views;

            foreach ($posts['data'] as $data) {
                $row = array($data['x'], $data['y']);
                $report[] = $row;
            }

            //topics per category per day
            $categories = $discourseManager->adminBridgeCall($credentials, '/categories.json', 'GET');
            foreach ($categories['category_list']['categories'] as $category) {
                $cat_id = $category['id'];
                $titleCat = array('Category: ' . $category['name'], 'Total topics: ' . $category['topic_count'], 'Total posts: ' . $category['post_count']);
                $report[] = $titleCat;
                $report[] = $header_views;
                $response = $discourseManager->adminBridgeCall($credentials, '/admin/reports/bulk.json?reports[topics][cache]=true&reports[topics][facets][]=prev_period&reports[topics][start_date]=' . $oneWeekAgo->format('Y-m-d') . '&reports[topics][end_date]=' . $today->format('Y-m-d') . '&reports[topics][filters][category]=' . $cat_id, 'GET');
                $catData = $response['reports'][0]['data'];

                foreach ($catData as $data) {
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
            $headerAwards = array('account_id', 'account_name', 'acumulated_score', 'period_score', 'award', 'level');
            $report[] = $titleAwards;
            $report[] = $headerAwards;
            $today->modify('+1 day');
            foreach ($accounts as $account) {
                //find awards by account
                $awards = $em->getRepository(AccountAward::class)->findBy(array(
                    'account' => $account
                ));
                /** @var AccountAward $award */
                foreach ($awards as $award) {
                    //get award_items from date to date
                    $qb_award_items = $em->createQueryBuilder();
                    $award_items = $qb_award_items->select('aai')
                        ->from(AccountAwardItem::class, 'aai')
                        ->where('aai.created < :today')
                        ->andWhere('aai.created > :oneWeekAgo')
                        ->andWhere('aai.account_award = :account_award_id')
                        ->setParameter('today', $today)
                        ->setParameter('oneWeekAgo', $oneWeekAgo)
                        ->setParameter('account_award_id', $award->getId())
                        ->getQuery()
                        ->getResult();

                    $period_score = 0;
                    foreach ($award_items as $award_item){
                        $period_score+= $award_item->getScore();
                    }

                    $report[] = array($account->getId(), $account->getName(), $award->getScore(), $period_score, $award->getAward()->getName(), $award->getLevel());
                }
            }

            foreach ($report as $row) {
                fputcsv($fp, $row, ';');
            }

            $this->sendEmail($emails_list, 'Statistics', 'statistics.csv');
        }
    }

    private function sendEmail($emails, $subject, $fileName){

        $no_replay = $this->container->getParameter('no_reply_email');

        $message = (new Email())
            ->subject($subject)
            ->from($no_replay)
            ->to(...$emails)
            ->html(
                $this->container->get('templating')
                    ->render('Email/empty_email.html.twig',
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
            );

        $message->attach(file_get_contents('/tmp/'.$fileName), $fileName);

        $this->mailer->send($message);
    }
}