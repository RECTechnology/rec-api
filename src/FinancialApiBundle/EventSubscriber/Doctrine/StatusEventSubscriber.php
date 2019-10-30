<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\Annotations\StatusProperty;
use App\FinancialApiBundle\Entity\Stateful;
use App\FinancialApiBundle\Entity\Translatable;
use App\FinancialApiBundle\Entity\PreDeleteChecks;
use App\FinancialApiBundle\Entity\ProductKind;
use App\FinancialApiBundle\Exception\AttemptToChangeFinalObjectException;
use App\FinancialApiBundle\Exception\AttemptToChangeStatusException;
use App\FinancialApiBundle\Exception\AttemptToSetAutomaticFieldException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Class StatusEventSubscriber
 * @package App\FinancialApiBundle\EventSubscriber\Doctrine
 */
class StatusEventSubscriber implements EventSubscriber {

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
                        if($value != null && $value != $an->getInitialStatus())
                            throw new AttemptToSetAutomaticFieldException(
                                "Cannot specify '{$rp->name}', initial value is auto-managed"
                            );
                        $rp->setValue($entity, $an->getInitialStatus());
                    }
                }
            }
        }
    }

    public function preUpdate(PreUpdateEventArgs $args){
        $entity = $args->getEntity();
        if($entity instanceof Stateful){
            $rc = new \ReflectionClass($entity);
            foreach($rc->getProperties() as $rp){
                if(array_key_exists($rp->name, $args->getEntityChangeSet())){
                    $ar = new AnnotationReader();
                    foreach ($ar->getPropertyAnnotations($rp) as $an){
                        if($an instanceof StatusProperty){
                            $rp->setAccessible(true);
                            if($an->isFinal($args->getOldValue($rp->name)))
                                throw new AttemptToChangeFinalObjectException("Cannot update final object");
                            if(!$an->isStatusChangeAllowed($args->getOldValue($rp->name), $args->getNewValue($rp->name)))
                                throw new AttemptToChangeStatusException("Changing '{$rp->name}' is not allowed");
                        }
                    }
                }
            }
        }
    }

}