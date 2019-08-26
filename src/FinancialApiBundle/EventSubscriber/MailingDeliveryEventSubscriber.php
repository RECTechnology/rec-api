<?php


namespace App\FinancialApiBundle\EventSubscriber;


use App\FinancialApiBundle\DependencyInjection\Drivers\MailgunWrapper;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Mailing;
use App\FinancialApiBundle\Entity\MailingDelivery;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

class MailingDeliveryEventSubscriber implements EventSubscriber {


    /** @var MailgunWrapper */
    private $mailgun;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param MailgunWrapper $mailgun
     */
    public function __construct(MailgunWrapper $mailgun)
    {
        $this->mailgun = $mailgun;
    }


    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents() {
        return [Events::preUpdate];
    }

    public function preUpdate(PreUpdateEventArgs $event){
        $entity = $event->getEntity();
        if($entity instanceof MailingDelivery)
            die("test");
            if ($event->hasChangedField('status') && $event->getNewValue('status') == MailingDelivery::STATUS_SCHEDULED) {
                /** @var Group $account */
                $account = $entity->getAccount();
                /** @var Mailing $mailing */
                $mailing = $entity->getMailing();

                $this->mailgun->send(
                    null,
                    $account->getEmail(),
                    $mailing->getSubject(),
                    $mailing->getContent(),
                    $mailing->getAttachments()
                );
                $entity->setStatus(MailingDelivery::STATUS_SENT);
                $event->getEntityManager()->persist($entity);
            }
    }

}