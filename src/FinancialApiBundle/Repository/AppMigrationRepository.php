<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/4/19
 * Time: 9:29 PM
 */

namespace App\FinancialApiBundle\Repository;

use App\FinancialApiBundle\Entity\MigratingEntity;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\ResultSetMapping;
use ReflectionException;
use Symfony\Component\HttpFoundation\RequestStack;

class AppMigrationRepository extends AppRepository {
    /**
     * AppMigrationRepository constructor.
     * @param EntityManagerInterface $em
     * @param ClassMetadata $class
     * @param RequestStack $stack
     * @throws NonUniqueResultException
     * @throws ReflectionException
     * @throws DBALException
     */
    public function __construct(EntityManagerInterface $em, ClassMetadata $class, RequestStack $stack)
    {

        if($em->getConnection()->getDatabasePlatform()->getName() == 'mysql') {
            $rc = $class->getReflectionClass();
            foreach ($rc->getInterfaces() as $interface) {
                if ($interface->getName() == MigratingEntity::class) {
                    $oldEntity = $rc->getMethod('getOldEntity')->invoke(null);
                    $migrationVersion = $rc->getMethod('getMigrationVersion')->invoke(null);
                    $rsm = new ResultSetMapping();
                    $rsm->addScalarResult("version", "version");

                    $q = $em->createNativeQuery(
                        "SELECT version from migration_versions ORDER BY version DESC limit 1",
                        $rsm
                    );
                    $version = $q->getSingleScalarResult();
                    if (!$version) $version = bcadd(0, 0);

                    if (bccomp($version, $migrationVersion) == -1) {
                        $class = $em->getClassMetadata($oldEntity);
                    }
                }
            }
        }

        parent::__construct($em, $class, $stack);
    }

}