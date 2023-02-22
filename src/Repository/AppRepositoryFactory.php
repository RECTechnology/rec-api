<?php


namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Repository\RepositoryFactory;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AppRepositoryFactory implements RepositoryFactory {

    /** @var RequestStack $stack */
    private $stack;

    /** @var ContainerInterface $stack */
    private $container;

    /** @var ObjectRepository[] */
    private $managedRepositories = [];

    public function __construct(ContainerInterface $container, RequestStack $stack) {
        $this->stack = $stack;
        $this->container = $container;
    }

    /**
     * Gets the repository for an entity class.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager The EntityManager instance.
     * @param string $entityName The name of the entity.
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getRepository(EntityManagerInterface $entityManager, $entityName) {
        $metadata            = $entityManager->getClassMetadata($entityName);
        $repositoryServiceId = $metadata->customRepositoryClassName;

        $customRepositoryName = $metadata->customRepositoryClassName;
        if ($customRepositoryName !== null) {
            // fetch from the container
            if ($this->container && $this->container->has($customRepositoryName)) {
                $repository = $this->container->get($customRepositoryName);

                if (! $repository instanceof ObjectRepository) {
                    throw new RuntimeException(sprintf('The service "%s" must implement ObjectRepository (or extend a base class, like ServiceEntityRepository).', $repositoryServiceId));
                }

                return $repository;
            }

            // if not in the container but the class/id implements the interface, throw an error
            if (is_a($customRepositoryName, ServiceEntityRepositoryInterface::class, true)) {
                throw new RuntimeException(sprintf('The "%s" entity repository implements "%s", but its service could not be found. Make sure the service exists and is tagged with "%s".', $customRepositoryName, ServiceEntityRepositoryInterface::class, ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG));
            }

            if (! class_exists($customRepositoryName)) {
                throw new RuntimeException(sprintf('The "%s" entity has a repositoryClass set to "%s", but this is not a valid class. Check your class naming. If this is meant to be a service id, make sure this service exists and is tagged with "%s".', $metadata->name, $customRepositoryName, ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG));
            }

            // allow the repository to be created below
        }

        return $this->getOrCreateRepository($entityManager, $metadata, $this->stack);
    }


    private function getOrCreateRepository(EntityManagerInterface $entityManager, ClassMetadata $metadata, RequestStack $stack)
    {
        $repositoryHash = $metadata->getName() . spl_object_hash($entityManager);
        if (isset($this->managedRepositories[$repositoryHash])) {
            return $this->managedRepositories[$repositoryHash];
        }

        $repositoryClassName = $metadata->customRepositoryClassName ?: $entityManager->getConfiguration()->getDefaultRepositoryClassName();

        $repo = new $repositoryClassName($entityManager, $metadata, $stack);
        if($repo instanceof ContainerAwareInterface)
            $repo->setContainer($this->container);
        return $this->managedRepositories[$repositoryHash] = $repo;
    }
}