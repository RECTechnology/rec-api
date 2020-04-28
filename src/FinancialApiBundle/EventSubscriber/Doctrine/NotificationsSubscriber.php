<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\DependencyInjection\App\Commons\Notificator;
use App\FinancialApiBundle\Entity\PaymentOrderNotification;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

/**
 * Class NotificationsSubscriber
 * @package App\FinancialApiBundle\EventSubscriber\Doctrine
 */
class NotificationsSubscriber implements EventSubscriber {

    /** @var Notificator $notificator */
    private $notificator;

    /**
     * NotificationsSubscriber constructor.
     * @param Notificator $notificator
     */
    public function __construct(Notificator $notificator)
    {
        $this->notificator = $notificator;
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
            $this->notificator->send(
                $notification,
                function ($ignored) use ($notification) {
                    $notification->setStatus(PaymentOrderNotification::STATUS_NOTIFIED);
                },
                function ($ignored) use ($notification) {
                    $notification->setStatue(PaymentOrderNotification::STATUS_RETRYING);
                    $notification->setTries($notification->getTries() + 1);
                }
            );
        }
    }

}