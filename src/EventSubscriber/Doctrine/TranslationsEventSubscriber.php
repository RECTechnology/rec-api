<?php

namespace App\EventSubscriber\Doctrine;

use App\Annotations\TranslatedProperty;
use App\Entity\Translatable;
use App\Exception\NoSuchTranslationException;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class TranslationsEventSubscriber
 * @package App\EventSubscriber
 */
class TranslationsEventSubscriber implements EventSubscriber {

    /** @var RequestStack $stack */
    private $stack;

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param RequestStack $stack
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     */
    public function __construct(RequestStack $stack, EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->stack = $stack;
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
        $this->saveTranslations($args->getEntity(), $args->getEntityChangeSet());
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws AnnotationException
     * @throws \ReflectionException
     * @throws NoSuchTranslationException
     */
    public function prePersist(LifecycleEventArgs $args){
        $this->saveTranslations($args->getEntity());
    }

    /**
     * @param $entity
     * @param array $changeSet
     * @throws AnnotationException
     * @throws NoSuchTranslationException
     * @throws \ReflectionException
     */
    function saveTranslations($entity, $changeSet = []){
        if($entity instanceof Translatable){
            if($this->stack->getCurrentRequest()){
                if(!$entity->getLocale())
                    $entity->setLocale($this->stack->getCurrentRequest()->getLocale());
                $locale = $entity->getLocale();
                $defaultLocale = $this->container->getParameter('locale');
                if($locale != $defaultLocale){
                    $rc = new \ReflectionClass($entity);
                    foreach($rc->getProperties() as $rp){
                        if(array_key_exists($rp->name, $changeSet)){
                            $ar = new AnnotationReader();
                            foreach ($ar->getPropertyAnnotations($rp) as $an){
                                if($an instanceof TranslatedProperty){
                                    $rp->setAccessible(true);
                                    $translatedFieldName = implode("_", [$rp->name, $locale]);
                                    if(!$rc->hasProperty($translatedFieldName))
                                        throw new NoSuchTranslationException("This object cannot be translated to " . $locale);
                                    $translationProperty = $rc->getProperty($translatedFieldName);
                                    $translationProperty->setAccessible(true);
                                    $translationProperty->setValue($entity, $rp->getValue($entity));
                                    if(count($changeSet) <= 0) $rp->setValue($entity, null);
                                    else $rp->setValue($entity, $changeSet[$rp->name][0]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Translatable $entity
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    private function translate(Translatable $entity){
        $locale = $entity->getLocale();
        $defaultLocale = $this->container->getParameter('locale');
        if($locale != $defaultLocale){
            $rc = new \ReflectionClass($entity);
            foreach($rc->getProperties() as $rp){
                $ar = new AnnotationReader();
                foreach ($ar->getPropertyAnnotations($rp) as $an){
                    if($an instanceof TranslatedProperty){
                        $rp->setAccessible(true);
                        $translatedFieldName = implode("_", [$rp->name, $locale]);
                        if($rc->hasProperty($translatedFieldName)){
                            $translationProperty = $rc->getProperty($translatedFieldName);
                            $translationProperty->setAccessible(true);
                            $value = $translationProperty->getValue($entity);
                            if($value) $rp->setValue($entity, $value);
                        }
                    }
                }
            }

        }

    }

    /**
     * @param LifecycleEventArgs $args
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function postLoad(LifecycleEventArgs $args){
        $entity = $args->getEntity();
        if($entity instanceof Translatable){
            if(!$entity->getLocale()) {
                if ($this->stack->getCurrentRequest())
                    $entity->setLocale($this->stack->getCurrentRequest()->getLocale());
                else
                    $entity->setLocale($this->container->getParameter('kernel.default_locale'));
            }
            $this->translate($entity);
        }
    }
}