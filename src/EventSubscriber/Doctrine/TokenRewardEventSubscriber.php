<?php

namespace App\EventSubscriber\Doctrine;

use App\Entity\TokenReward;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class TokenRewardEventSubscriber
 * @package App\EventSubscriber\Doctrine
 */
class TokenRewardEventSubscriber implements EventSubscriber {

    /** @var ContainerInterface $container */
    private $container;

    protected $logger;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
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
            Events::preRemove
        ];
    }

    public function preRemove(LifecycleEventArgs $args){
        $entity = $args->getEntity();
        if($entity instanceof TokenReward){
            if($entity->getStatus() === TokenReward::STATUS_MINTED){
                throw new HttpException(403, 'Token reward is '.$entity->getStatus().' and can not be removed');
            }
        }
    }

}