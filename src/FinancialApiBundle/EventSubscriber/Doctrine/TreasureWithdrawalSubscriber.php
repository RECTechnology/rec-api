<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\Annotations\HybridPropery;
use App\FinancialApiBundle\Annotations\TranslatedProperty;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\HybridPersistent;
use App\FinancialApiBundle\Entity\Translatable;
use App\FinancialApiBundle\Entity\TreasureWithdrawal;
use App\FinancialApiBundle\Entity\TreasureWithdrawalAuthorizedEmail;
use App\FinancialApiBundle\Entity\TreasureWithdrawalValidation;
use App\FinancialApiBundle\Exception\AppLogicException;
use App\FinancialApiBundle\Exception\NoSuchTranslationException;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\Collection;
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
 * Class TreasureWithdrawalSubscriber
 * @package App\FinancialApiBundle\EventSubscriber\Doctrine
 */
class TreasureWithdrawalSubscriber implements EventSubscriber {

    /** @var EntityManagerInterface $em */
    private $em;

    /**
     * TreasureWithdrawalSubscriber constructor.
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
            Events::prePersist,
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws NoSuchTranslationException
     */
    public function prePersist(LifecycleEventArgs $args){
        $withdrawal = $args->getEntity();
        /*
        if($withdrawal instanceof TreasureWithdrawal){
            $emails = $this->em->getRepository(TreasureWithdrawalAuthorizedEmail::class)->findAll();
            if(!$emails)
                throw new AppLogicException("Cannot create Treasure withdrawal without any validator email");
        */
            /** @var TreasureWithdrawalAuthorizedEmail $email */
        /*
            foreach ($emails as $email){
                $validation = new TreasureWithdrawalValidation();
                $validation->setWithdrawal($withdrawal);
                $validation->setEmail($email);
                $this->em->persist($validation);
                $this->em->persist($email);
                $this->em->persist($withdrawal);
            }
        }
        $this->em->flush();
    */
    }

}