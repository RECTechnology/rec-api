<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\Entity\Challenge;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ChallengeEventSubscriber
 * @package App\FinancialApiBundle\EventSubscriber\Doctrine
 */
class ChallengeEventSubscriber implements EventSubscriber {

    /** @var ContainerInterface $container */
    private $container;

    protected $logger;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $this->container->get('logger');
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents() {
        return [
            Events::preUpdate,
            Events::preRemove
        ];
    }

    public function preUpdate(PreUpdateEventArgs $args){
        $challenge = $args->getEntity();
        $em = $args->getEntityManager();
        if($challenge instanceof Challenge) {
            $changes = $args->getEntityChangeSet();
            if(!isset($changes['status'])){
                if ($challenge->getStatus() === Challenge::STATUS_OPEN){
                    $allowed_changes = ['finish_date', 'updated'];
                    foreach ($changes as $key => $value){
                        if(!in_array($key, $allowed_changes)){
                            throw new HttpException(403,$key.' can not be changed when challenge is open');
                        }
                    }
                }elseif ($challenge->getStatus() === Challenge::STATUS_CLOSED){
                    throw new HttpException(403, 'Challenge is closed, can not be editable');
                }
            }else{
                if($changes['status'][0] === Challenge::STATUS_CLOSED){
                    throw new HttpException(403, 'Challenge is closed, can not be re-opened or re-scheduled');
                }
                if ($changes['status'][1] === Challenge::STATUS_OPEN){
                    $allowed_changes = ['finish_date', 'status', 'updated'];
                    foreach ($changes as $key => $value){
                        if(!in_array($key, $allowed_changes)){
                            throw new HttpException(403,$key.' can not be changed when challenge is open');
                        }
                    }
                }elseif ($changes['status'][1] === Challenge::STATUS_CLOSED){
                    $allowed_changes = ['status', 'updated'];
                    foreach ($changes as $key => $value){
                        if(!in_array($key, $allowed_changes)){
                            throw new HttpException(403,$key.' can not be changed when challenge is closed');
                        }
                    }
                }
            }

        }
    }

    public function preRemove(LifecycleEventArgs $args){
        $entity = $args->getEntity();
        if($entity instanceof Challenge){
            if($entity->getStatus() !== Challenge::STATUS_SCHEDULED){
                throw new HttpException(403, 'Challenge is '.$entity->getStatus().' and can not be removed');
            }
        }
    }

}