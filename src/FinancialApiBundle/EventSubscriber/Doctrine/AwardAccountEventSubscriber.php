<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\Entity\AccountAward;
use App\FinancialApiBundle\Entity\Award;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AwardAccountEventSubscriber
 * @package App\FinancialApiBundle\EventSubscriber\Doctrine
 */
class AwardAccountEventSubscriber implements EventSubscriber {

    /** @var ContainerInterface $container */
    private $container;

    protected $logger;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $this->container->get('logger');
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

        $message = \Swift_Message::newInstance()
            ->setSubject("New Level Raised in Conecta")
            ->setFrom($no_replay)
            ->setTo($resume_admin_emails)
            ->setBody(
                $this->container->get('templating')
                    ->render('FinancialApiBundle:Email:empty_email.html.twig',
                        array(
                            'mail' => [
                                'subject' => "New level raised in Conecta",
                                'body' => $body,
                                'lang' => 'es'
                            ]
                        )
                    )
            )
            ->setContentType('text/html');

        $this->logger->info('AWARD_ACCOUNT_EVENT_SUBSCRIBER: Sending email');
        $this->container->get('mailer')->send($message);
    }


}