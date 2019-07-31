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
use App\FinancialApiBundle\Entity\EntityWithUploadableFields;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\KYC;
use App\FinancialApiBundle\Entity\User;

class FileSetterListener
{
    /** @var ContainerInterface $container */
    protected $container;

    /**
     * FileSetterListener constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param EntityWithUploadableFields $entity
     * @param $file_fields_changed
     * @param EntityManagerInterface $em
     * @param UnitOfWork $uow
     * @throws \ReflectionException
     */
    private function save(EntityWithUploadableFields $entity, $file_fields_changed, EntityManagerInterface $em, UnitOfWork $uow){

        /** @var UploadManager $fileManager */
        $fileManager = $this->container->get('file_manager');

        /** @var LoggerInterface $logger */
        $logger = $this->container->get("logger");

        foreach($file_fields_changed as $field => $filter){

            $class = new \ReflectionClass(ClassUtils::getClass($entity));
            $logger->info("[FileSetterListener] class=" . ClassUtils::getClass($entity));
            $prop = $class->getProperty($field);
            $prop->setAccessible(true);
            $fileSrc = $prop->getValue($entity);
            if($fileSrc){
                $logger->info("[FileSetterListener] Changing '$field' for class '" . ClassUtils::getClass($entity) . "', orig=" . $fileSrc);

                //download the file
                $fileContents = $fileManager->readFileUrl($fileSrc);
                $filename = $fileManager->saveFile($fileContents, $filter);

                $prop->setValue($entity, $filename);
                $logger->info("[FileSetterListener] Set '$field' for class '" . ClassUtils::getClass($entity) . "', new=" . $filename);
            }
            else{
                $logger->info("[FileSetterListener] Changing '$field' for class '" . ClassUtils::getClass($entity) . "', new=null");
            }
        }
        $em->persist($entity);
        $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(ClassUtils::getClass($entity)), $entity);
    }

    /**
     * @param OnFlushEventArgs $eventArgs
     * @throws \ReflectionException
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if($entity instanceof EntityWithUploadableFields) {
                $this->save($entity, $entity->getUploadableFields(), $em, $uow);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if($entity instanceof EntityWithUploadableFields) {
                $file_fields_changed = array_intersect_key($entity->getUploadableFields(), $uow->getEntityChangeSet($entity));
                $this->save($entity, $file_fields_changed, $em, $uow);
            }
        }

    }

}