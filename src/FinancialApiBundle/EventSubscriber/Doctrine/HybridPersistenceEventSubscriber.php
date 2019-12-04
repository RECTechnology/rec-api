<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\Annotations\HybridPropery;
use App\FinancialApiBundle\Annotations\TranslatedProperty;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\HybridPersistent;
use App\FinancialApiBundle\Entity\Translatable;
use App\FinancialApiBundle\Exception\AppLogicException;
use App\FinancialApiBundle\Exception\NoSuchTranslationException;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
     * @throws AnnotationException
     * @throws \ReflectionException
     * @throws NoSuchTranslationException
     */
    public function preUpdate(PreUpdateEventArgs $args){
        $this->saveHybridIdentifier($args->getEntity(), $args->getEntityChangeSet());
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws NoSuchTranslationException
     */
    public function prePersist(LifecycleEventArgs $args){
        $this->saveHybridIdentifier($args->getEntity());
    }

    /**
     * @param $entity
     * @param array $changeSet
     * @throws NoSuchTranslationException
     */
    function saveHybridIdentifier($entity, $changeSet = []){
        if($entity instanceof HybridPersistent){
        }
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws \ReflectionException
     * @throws AnnotationException
     */
    public function postLoad(LifecycleEventArgs $args){
        $entity = $args->getEntity();
        if($entity instanceof HybridPersistent){
            $rc = new \ReflectionClass($entity);
            foreach($rc->getProperties() as $rp){
                $ar = new AnnotationReader();
                foreach ($ar->getPropertyAnnotations($rp) as $an){
                    if($an instanceof HybridPropery){
                        $identifierField = $an->getIdentifier();
                        $rp->setAccessible(true);
                        if(!$rc->hasProperty($identifierField))
                            throw new AppLogicException("Invalid identifier '$identifierField', not found in object");
                        $identifierProperty = $rc->getProperty($identifierField);
                        $identifierProperty->setAccessible(true);
                        /** @var ObjectManager $om */
                        $om = $this->container->get($an->getManager());
                        $id = $identifierProperty->getValue($entity);
                        if($id != null) {
                            $object = $om->getRepository($an->getTargetEntity())->find($id);
                            $rp->setValue($entity, $identifierProperty->getValue($object->getId()));
                        }
                    }
                }
            }
        }
    }
}