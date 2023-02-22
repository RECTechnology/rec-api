<?php

namespace App\EventSubscriber\Doctrine;

use App\Entity\AccountAward;
use App\Entity\Award;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Class AwardAccountEventSubscriber
 * @package App\EventSubscriber\Doctrine
 */
class AwardAccountEventSubscriber implements EventSubscriber {

    /** @var ContainerInterface $container */
    private $container;

    protected $logger;
    private MailerInterface $mailer;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, LoggerInterface $logger, MailerInterface $mailer)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->mailer = $mailer;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents() {
        return [
            Events::preUpdate,
        ];
    }

    public function preUpdate(PreUpdateEventArgs $args){
        $accountAward = $args->getEntity();
        $em = $args->getEntityManager();
        if($accountAward instanceof AccountAward){
            if ($args->hasChangedField("score")){
                $this->logger->info('AWARD_ACCOUNT_EVENT_SUBSCRIBER: Detected score changes');
                $score = $accountAward->getScore();

                /** @var Award $award */
                $award = $accountAward->getAward();
                $ranges = $award->getThresholds();

                $currentLevel = $accountAward->getLevel();

                if(isset($ranges[$currentLevel + 1])){
                    if($score >= $ranges[$currentLevel + 1]){
                        //increase level
                        $accountAward->setLevel($currentLevel +1);
                        $this->logger->info('AWARD_ACCOUNT_EVENT_SUBSCRIBER: promote account '
                            .$accountAward->getAccount()->getName().' to level '.($currentLevel+1).' in '
                            .$accountAward->getAward()->getName());

                        //send an email
                        $this->sendEmail($accountAward);
                    }
                }
            }
        }
    }

    private function sendEmail(AccountAward $accountAward){

        $no_replay = $this->container->getParameter('no_reply_email');
        $body = 'La cuenta '.$accountAward->getAccount()->getId().' - '.$accountAward->getAccount()->getName().
            ' ha subido de nivel '.($accountAward->getLevel() - 1).' a nivel '.$accountAward->getLevel().
            ' en la categoria '.$accountAward->getAward()->getNameEs();

        $this->logger->info('AWARD_ACCOUNT_EVENT_SUBSCRIBER: '.$body);

        $resume_admin_emails = $this->container->getParameter("resume_admin_emails_list");

        $message = new Email();
            $message->subject("New Level Raised in Conecta")
            ->from($no_replay)
            ->to(...$resume_admin_emails)
            ->html(
                $this->container->get('templating')
                    ->render('Email/empty_email.html.twig',
                        array(
                            'mail' => [
                                'subject' => "New level raised in Conecta",
                                'body' => $body,
                                'lang' => 'es'
                            ]
                        )
                    )
            );

        $this->logger->info('AWARD_ACCOUNT_EVENT_SUBSCRIBER: Sending email');
        $this->mailer->send($message);
    }


}