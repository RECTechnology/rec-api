<?php

namespace App\EventSubscriber\Doctrine;

use App\Annotations\StatusProperty;
use App\Entity\Stateful;
use App\Exception\AttemptToChangeFinalObjectException;
use App\Exception\AttemptToChangeStatusException;
use App\Exception\InvalidInitialValueException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class StatusEventSubscriber
 * @package App\EventSubscriber\Doctrine
 */
class StatusEventSubscriber implements EventSubscriber {

    /** @var EntityManagerInterface $em */
    private $em;
    protected $container;
    protected $logger;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $em, ContainerInterface $container, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents() {
        return [
            Events::preUpdate,
            Events::prePersist,
        ];
    }

    public function prePersist(LifecycleEventArgs $args){
        $entity = $args->getEntity();
        if($entity instanceof Stateful){
            $rc = new \ReflectionClass($entity);
            foreach($rc->getProperties() as $rp){
                $ar = new AnnotationReader();
                foreach ($ar->getPropertyAnnotations($rp) as $an){
                    if($an instanceof StatusProperty){
                        $rp->setAccessible(true);
                        $value = $rp->getValue($entity);
                        if($value == null){
                            $rp->setValue($entity, $an->getInitialStatuses()[0]);
                        }
                        elseif(!in_array($value, $an->getInitialStatuses())) {
                            throw new InvalidInitialValueException("Invalid initial value for '{$rp->name}'");
                        }
                    }
                }
            }
        }
    }

    public function preUpdate(PreUpdateEventArgs $args){
        $entity = $args->getEntity();
        if($entity instanceof Stateful && !$entity->statusChecksSkipped()){
            $rc = new \ReflectionClass($entity);
            foreach($rc->getProperties() as $rp){
                if(array_key_exists($rp->name, $args->getEntityChangeSet())){
                    $ar = new AnnotationReader();
                    foreach ($ar->getPropertyAnnotations($rp) as $an){
                        if($an instanceof StatusProperty){
                            $rp->setAccessible(true);
                            if($an->isFinal($args->getOldValue($rp->name)))
                                throw new AttemptToChangeFinalObjectException("Cannot update final object");
                            if(!$an->isStatusChangeAllowed($args->getOldValue($rp->name), $args->getNewValue($rp->name))){
                                $this->logger->info('variable: '.$rp->name);
                                $this->logger->info('old value: '.$args->getOldValue($rp->name));
                                $this->logger->info('new value: '.$args->getNewValue($rp->name));
                                throw new AttemptToChangeStatusException("Changing '{$rp->name}' is not allowed");
                            }
                        }
                    }
                }
            }
        }
    }

}