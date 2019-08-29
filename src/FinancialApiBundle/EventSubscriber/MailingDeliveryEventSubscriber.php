<?php


namespace App\FinancialApiBundle\EventSubscriber;


use App\FinancialApiBundle\DependencyInjection\Drivers\MailgunWrapper;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Mailing;
use App\FinancialApiBundle\Entity\MailingDelivery;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Swift_Mailer;

class MailingDeliveryEventSubscriber implements EventSubscriber {

    /** @var Swift_Mailer */
    private $mailer;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param Swift_Mailer $mailer
     */
    public function __construct(Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents() {
        return [Events::preUpdate];
    }

    public function preUpdate(PreUpdateEventArgs $args){

        $entity = $args->getEntity();
        if($entity instanceof MailingDelivery)
            if ($args->hasChangedField('status') && $args->getNewValue('status') == MailingDelivery::STATUS_SCHEDULED) {
                /** @var Group $account */
                $account = $entity->getAccount();
                /** @var Mailing $mailing */
                $mailing = $entity->getMailing();

                $message = new \Swift_Message(
                    $mailing->getSubject(),
                    $mailing->getContent(),
                    'text/plain'
                );

                //$message->setTo($account->getEmail());
                $message->setTo('lluis@qbitartifacts.com');
                $message->setFrom('postmaster@sandbox45b8322153cb4d8898da8ad6e384be0b.mailgun.org');

                $this->mailer->send($message);

                $entity->setStatus(MailingDelivery::STATUS_SENT);
                $args->getEntityManager()->persist($entity);
            }
    }

}