<?php

namespace App\FinancialApiBundle\EventSubscriber;

use App\FinancialApiBundle\Entity\Localizable;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Gedmo\Translatable\Entity\Translation;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class TranslationsEventSubscriber
 * @package App\FinancialApiBundle\EventSubscriber
 */
class TranslationsEventSubscriber implements EventSubscriber {

    /** @var RequestStack $stack */
    private $stack;

    /** @var EntityManagerInterface $em */
    private $em;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param RequestStack $stack
     * @param EntityManagerInterface $em
     */
    public function __construct(RequestStack $stack, EntityManagerInterface $em)
    {
        $this->stack = $stack;
        $this->em = $em;
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

    public function preUpdate(LifecycleEventArgs $args){
        $this->prePersist($args);
    }

    public function prePersist(LifecycleEventArgs $args){
        $entity = $args->getEntity();
        if($entity instanceof Localizable && $entity->getTranslatableLocale() === null){
            $entity->setTranslatableLocale($this->getRequestLocale());
        }
    }

    public function postLoad(LifecycleEventArgs $args){
        $entity = $args->getEntity();
        if($entity instanceof Localizable && $entity->getTranslatableLocale() === null){
            if($this->getRequestLocale() === 'all') {
                $repository = $this->em->getRepository(Translation::class);
                $entity->setTranslations($repository->findTranslations($entity));
            }
            else {
                $entity->setTranslatableLocale($this->getRequestLocale());
                $this->em->refresh($entity);
            }
        }
    }

    /**
     * @return string
     */
    private function getRequestLocale(){
        $request = $this->stack->getCurrentRequest();
        $method = $request->getMethod();
        $headers = $request->headers;
        if(in_array($method, ['POST', 'PUT']) && $headers->has('content-language')){
            return $headers->get('content-language');
        }
        return $headers->get('accept-language', $request->getLocale());
    }

}