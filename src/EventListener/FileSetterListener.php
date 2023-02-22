<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/19/19
 * Time: 5:42 PM
 */

namespace App\EventListener;


use App\DependencyInjection\Commons\UploadManager;
use App\Entity\Uploadable;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FileSetterListener
{
    /** @var ContainerInterface $container */
    protected $container;

    /** @var UploadManager */
    private $fileManager;

    /** @var LoggerInterface */
    private $logger;

    /**
     * FileSetterListener constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, UploadManager $uploadManager, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->fileManager = $uploadManager;
        $this->logger = $logger;
    }

    /**
     * @param Uploadable $entity
     * @param $file_fields_changed
     * @param EntityManagerInterface $em
     * @param UnitOfWork $uow
     * @throws \ReflectionException
     */
    private function save(Uploadable $entity, $file_fields_changed, EntityManagerInterface $em, UnitOfWork $uow){

        foreach($file_fields_changed as $field => $filter){

            $class = new \ReflectionClass(ClassUtils::getClass($entity));
            $this->logger->info("[FileSetterListener] class=" . ClassUtils::getClass($entity));
            $prop = $class->getProperty($field);
            $prop->setAccessible(true);
            $fileSrc = $prop->getValue($entity);
            if($fileSrc){
                $this->logger->info("[FileSetterListener] Changing '$field' for class '" . ClassUtils::getClass($entity) . "', orig=" . $fileSrc);

                //download the file
                $fileContents = $this->fileManager->readFileUrl($fileSrc);
                $filename = $this->fileManager->saveFile($fileContents, $filter);

                $prop->setValue($entity, $filename);
                $this->logger->info("[FileSetterListener] Set '$field' for class '" . ClassUtils::getClass($entity) . "', new=" . $filename);
            }
            else{
                $this->logger->info("[FileSetterListener] Changing '$field' for class '" . ClassUtils::getClass($entity) . "', new=null");
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
            if($entity instanceof Uploadable) {
                $this->save($entity, $entity->getUploadableFields(), $em, $uow);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if($entity instanceof Uploadable) {
                $file_fields_changed = array_intersect_key($entity->getUploadableFields(), $uow->getEntityChangeSet($entity));
                $this->save($entity, $file_fields_changed, $em, $uow);
            }
        }

    }

}