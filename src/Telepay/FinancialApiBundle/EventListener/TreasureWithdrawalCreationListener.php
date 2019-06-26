<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/19/19
 * Time: 5:42 PM
 */

namespace Telepay\FinancialApiBundle\EventListener;


use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\TreasureWithdrawalAttempt;
use Telepay\FinancialApiBundle\Entity\TreasureWithdrawalValidation;
use Telepay\FinancialApiBundle\Entity\User;

class TreasureWithdrawalCreationListener {

    /** @var ContainerInterface $container */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param $entity
     * @param EntityManagerInterface $em
     * @param UnitOfWork $uow
     */
    private function insert($entity, EntityManagerInterface $em, UnitOfWork $uow){
        $em->persist($entity);
        $uow->computeChangeSet($em->getClassMetadata(ClassUtils::getClass($entity)), $entity);
    }

    /**
     * @param $entity
     * @param EntityManagerInterface $em
     * @param UnitOfWork $uow
     */
    private function update($entity, EntityManagerInterface $em, UnitOfWork $uow){
        $em->persist($entity);
        $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(ClassUtils::getClass($entity)), $entity);
    }

    /**
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs) {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $attempt) {
            if($attempt instanceof TreasureWithdrawalAttempt) {
                $userRepo = $em->getRepository('TelepayFinancialApiBundle:User');
                $admin_ids = $this->container->getParameter('authorized_admins');
                foreach ($admin_ids as $admin_id){
                    /** @var User $admin */
                    $admin = $userRepo->find($admin_id);
                    if($admin){
                        $validation = new TreasureWithdrawalValidation();
                        $validation->setAttempt($attempt);
                        $validation->setValidator($admin);
                        $validation->setAccepted(false);
                        $this->insert($validation, $em, $uow);
                        $attempt->addValidation($validation);
                    }
                }
                $this->update($attempt, $em, $uow);
            }
        }
    }
}