<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\DependencyInjection\App\Commons\GardenHandler;
use App\FinancialApiBundle\DependencyInjection\App\Commons\ShopBadgeHandler;
use App\FinancialApiBundle\Entity\Qualification;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Container\ContainerInterface;

class QualificationEventSubscriber implements EventSubscriber
{

    /** @var EntityManagerInterface $em */
    private $em;
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
            Events::preUpdate,
            Events::postUpdate
        ];
    }

    public function preUpdate(PreUpdateEventArgs $args){
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
        $uow = $entityManager->getUnitOfWork();
        if($entity instanceof Qualification){
            if($args->hasChangedField('value')){
                $entity->setStatus(Qualification::STATUS_REVIEWED);
                //TODO update metadata NFT cliente
                /** @var GardenHandler $gardenHandler */
                $gardenHandler = $this->container->get('net.app.commons.garden_handler');
                $gardenHandler->updateGarden(GardenHandler::ACTION_MAKE_REVIEW);

            }
        }
    }

    public function postUpdate(LifecycleEventArgs $args){
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();

        if($entity instanceof Qualification){
            //TODO recalculate shopBadge
            /** @var ShopBadgeHandler $shopBadgeHandler */
            $shopBadgeHandler = $this->container->get('net.app.commons.shop_badge_handler');
            $shopBadgeHandler->recalculateShopBadge($entity);
        }
    }
}