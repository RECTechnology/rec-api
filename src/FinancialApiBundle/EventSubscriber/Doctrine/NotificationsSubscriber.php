<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\DependencyInjection\App\Commons\Notifier;
use App\FinancialApiBundle\Entity\PaymentOrderNotification;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NotificationsSubscriber
 * @package App\FinancialApiBundle\EventSubscriber\Doctrine
 */
class NotificationsSubscriber implements EventSubscriber {

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Notifier
     */
    private $notifier;

    /**
     * NotificationsSubscriber constructor.
     * @param EntityManagerInterface $em
     * @param Notifier $notifier
     */
    public function __construct(EntityManagerInterface $em, Notifier $notifier)
    {
        $this->em = $em;
        $this->notifier = $notifier;
    }


    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents() {
        return [
            Events::postPersist,
        ];
    }

    public function postPersist(LifecycleEventArgs $args){
        $notification = $args->getEntity();
        if($notification instanceof PaymentOrderNotification){
            $em = $this->em;
            $repo = $em->getRepository(PaymentOrderNotification::class);
            if($repo->findBy(['status' => PaymentOrderNotification::STATUS_RETRYING])){
                $notification->setStatus(PaymentOrderNotification::STATUS_RETRYING);
                $notification->setTries(0);
                $em->flush();
            }
            else {
                $this->notifier->send(
                    $notification,
                    function ($ignored) use ($notification) {
                        $notification->setStatus(PaymentOrderNotification::STATUS_NOTIFIED);
                    },
                    function ($ignored) use ($notification) {
                        $notification->setStatus(PaymentOrderNotification::STATUS_RETRYING);
                        $notification->setTries($notification->getTries() + 1);
                    },
                    function () use ($notification, $em) {
                        $em->flush();
                    }
                );
            }
        }
    }

}