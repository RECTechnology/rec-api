<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\DependencyInjection\App\Commons\Notifier;
use App\FinancialApiBundle\Entity\PaymentOrderNotification;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NotificationsSubscriber
 * @package App\FinancialApiBundle\EventSubscriber\Doctrine
 */
class NotificationsSubscriber implements EventSubscriber {

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * NotificationsSubscriber constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
            $notifier = $this->container->get(Notifier::class);
            $em = $this->container->get('doctrine.orm.entity_manager');
            $notifier->send(
                $notification,
                function ($ignored) use ($notification) {
                    $notification->setStatus(PaymentOrderNotification::STATUS_NOTIFIED);
                },
                function ($ignored) use ($notification) {
                    $notification->setStatus(PaymentOrderNotification::STATUS_RETRYING);
                    $notification->setTries($notification->getTries() + 1);
                },
                function () use ($notification, $em) {
                    $em->flush($notification);
                }
            );
        }
    }

}