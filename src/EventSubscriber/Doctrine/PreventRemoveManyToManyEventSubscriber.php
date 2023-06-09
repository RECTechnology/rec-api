<?php

namespace App\EventSubscriber\Doctrine;

use App\Entity\PreDeleteChecks;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

/**
 * Class PreventRemoveManyToManyEventSubscriber
 * @package App\EventSubscriber\Doctrine
 */
class PreventRemoveManyToManyEventSubscriber implements EventSubscriber {

    /** @var EntityManagerInterface $em */
    private $em;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents() {
        return [
            Events::preRemove,
        ];
    }

    public function preRemove(LifecycleEventArgs $args){
        $entity = $args->getEntity();
        if($entity instanceof PreDeleteChecks){
            $entity->isDeleteAllowed();
        }
    }

}