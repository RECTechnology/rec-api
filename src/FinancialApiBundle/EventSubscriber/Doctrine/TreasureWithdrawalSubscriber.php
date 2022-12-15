<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\Entity\TreasureWithdrawal;
use App\FinancialApiBundle\Entity\TreasureWithdrawalValidation;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\ORMException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TreasureWithdrawalSubscriber
 * @package App\FinancialApiBundle\EventSubscriber\Doctrine
 */
class TreasureWithdrawalSubscriber implements EventSubscriber {

    /** @var ContainerInterface */
    private $container;

    /**
     * TreasureWithdrawalSubscriber constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents() {
        return [
            Events::onFlush,
        ];
    }

    /**
     * @param OnFlushEventArgs $args
     * @throws ORMException
     */
    public function onFlush(OnFlushEventArgs $args){
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $entities = $uow->getScheduledEntityInsertions();
        foreach ($entities as $withdrawal){
            if($withdrawal instanceof TreasureWithdrawal){
                $mails = json_decode($this->container->getParameter('list_emails'));
                foreach ($mails as $mail){
                    $validation = new TreasureWithdrawalValidation();
                    $validation->setWithdrawal($withdrawal);
                    $validation->setEmail($mail);
                    $validation->setStatus(TreasureWithdrawalValidation::STATUS_CREATED);
                    $em->persist($validation);
                    $uow->computeChangeSet(
                        $em->getClassMetadata(TreasureWithdrawalValidation::class),
                        $validation
                    );
                    $withdrawal->addValidation($validation);
                }
                $withdrawal->setStatus(TreasureWithdrawal::STATUS_PENDING);
                $em->persist($withdrawal);
                $uow->recomputeSingleEntityChangeSet(
                    $em->getClassMetadata(TreasureWithdrawal::class),
                    $withdrawal
                );
            }
        }
    }

}