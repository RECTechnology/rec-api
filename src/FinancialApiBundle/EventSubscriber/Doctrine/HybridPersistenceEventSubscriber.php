<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\Annotations\HybridProperty;
use App\FinancialApiBundle\Entity\HybridPersistent;
use App\FinancialApiBundle\Exception\AppLogicException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class HybridPersistenceEventSubscriber
 * @package App\FinancialApiBundle\EventSubscriber\Doctrine
 */
class HybridPersistenceEventSubscriber implements EventSubscriber {

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents() {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::postLoad,
        ];
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args){
        $this->saveHybridIdentifier($args->getEntity(), $args->getEntityChangeSet());
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args){
        $this->saveHybridIdentifier($args->getEntity());
    }

    /**
     * @param $entity
     * @param array $changeSet
     */
    function saveHybridIdentifier($entity, $changeSet = []){
        $this->forEachHybridProperty(
            $entity,
            function (\ReflectionProperty $property, HybridProperty $hybridProperty) use ($entity) {
                $class = $property->getDeclaringClass();
                $identifierField = $hybridProperty->getIdentifier();
                $property->setAccessible(true);
                if(!$class->hasProperty($identifierField))
                    throw new AppLogicException("Invalid identifier '$identifierField', not found in object");
                $identifierProperty = $class->getProperty($identifierField);
                $identifierProperty->setAccessible(true);
                $object = $property->getValue($entity);
                if($object) $identifierProperty->setValue($entity, $object->getId());
            });
    }


    /**
     * @param $entity
     * @param $func
     * @throws \ReflectionException
     */
    protected function forEachHybridProperty($entity, $func){
        if($entity instanceof HybridPersistent){
            $rc = new \ReflectionClass($entity);
            foreach($rc->getProperties() as $rp){
                $ar = new AnnotationReader();
                foreach ($ar->getPropertyAnnotations($rp) as $an){
                    if($an instanceof HybridProperty){
                        $func($rp, $an);
                    }
                }
            }
        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws \ReflectionException
     */
    public function postLoad(LifecycleEventArgs $args){
        $entity = $args->getEntity();
        $this->forEachHybridProperty(
            $entity,
            function (\ReflectionProperty $property, HybridProperty $hybridProperty) use ($entity) {
                $class = $property->getDeclaringClass();
                $identifierField = $hybridProperty->getIdentifier();
                $property->setAccessible(true);
                if(!$class->hasProperty($identifierField))
                    throw new AppLogicException("Invalid identifier '$identifierField', not found in object");
                $identifierProperty = $class->getProperty($identifierField);
                $identifierProperty->setAccessible(true);
                /** @var ObjectManager $om */
                $om = $this->container->get($hybridProperty->getManager());
                $id = $identifierProperty->getValue($entity);
                if($id) {
                    $object = $om->getRepository($hybridProperty->getTargetEntity())->find($id);
                    $property->setValue($entity, $object);
                }
            });
    }
}