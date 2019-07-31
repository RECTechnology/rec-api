<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/19/19
 * Time: 5:42 PM
 */

namespace App\FinancialApiBundle\EventListener;


use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use App\FinancialApiBundle\Entity\DelegatedChange;
use App\FinancialApiBundle\Entity\DelegatedChangeData;
use App\FinancialApiBundle\Entity\EntityWithUploadableFields;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\KYC;
use App\FinancialApiBundle\Entity\User;

class DelegatedChangeStatisticsUpdater
{
    /**
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $dcd) {
            if($dcd instanceof DelegatedChangeData) {
                $dc = $this->getDelegatedChangeUpdated($dcd);
                $this->save($dc, $em, $uow);
            }
        }

        foreach ($uow->getScheduledEntityDeletions() as $dcd) {
            if($dcd instanceof DelegatedChangeData) {
                $dc = $this->getDelegatedChangeUpdated($dcd, 2 *  $dcd->getAmount(), -1);
                $this->save($dc, $em, $uow);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $dcd) {
            if($dcd instanceof DelegatedChangeData) {

                $changes = $uow->getEntityChangeSet($dcd);
                if(isset($changes['status'])) {
                    /** @var DelegatedChange $dc */
                    $dc = $dcd->getDelegatedChange();
                    if ($changes['status'] === DelegatedChangeData::STATUS_SUCCESS) {
                        $dc->setResult("success_tx", $dc->getStatistics()['result']['success_tx'] + 1);
                        $dc->setResult("issued_rec", $dc->getStatistics()['result']['issued_rec'] + $dcd->getAmount());
                    } elseif ($changes['status'] === DelegatedChangeData::STATUS_ERROR) {
                        $dc->setResult("error_tx", $dc->getStatistics()['result']['error_tx'] + 1);
                    }
                    $this->save($dc, $em, $uow);
                }
                if(isset($changes['amount'])) {
                    $old_dcd = $uow->getOriginalEntityData($dcd);
                    $dc = $this->getDelegatedChangeUpdated($dcd, $old_dcd['amount']);
                    $this->save($dc, $em, $uow);
                }
            }
        }

    }

    /**
     * @param DelegatedChangeData $dcd
     * @param int $oldAmount
     * @param int $newEntries
     * @return DelegatedChange
     */
    private function getDelegatedChangeUpdated(DelegatedChangeData $dcd, $oldAmount = 0, $newEntries = 1){
        /** @var DelegatedChange $dc */
        $dc = $dcd->getDelegatedChange();
        $stats = $dc->getStatistics();
        $dc->setTxToExecute($stats['scheduled']['tx_to_execute'] + $newEntries);
        $dc->setRecToBeIssued($stats['scheduled']['rec_to_be_issued'] - $oldAmount * 1e6 + $dcd->getAmount() * 1e6);
        return $dc;
    }

    /**
     * @param $entity
     * @param EntityManagerInterface $em
     * @param UnitOfWork $uow
     */
    private function save($entity, EntityManagerInterface $em, UnitOfWork $uow){
        $em->persist($entity);
        $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(ClassUtils::getClass($entity)), $entity);
    }
}