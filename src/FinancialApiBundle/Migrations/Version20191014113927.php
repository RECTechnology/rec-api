<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use App\FinancialApiBundle\Entity\Activity;
use App\FinancialApiBundle\Entity\ProductKind;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class Version20191014113927
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20191014113927 extends AbstractMigration implements ContainerAwareInterface{

    use ContainerAwareTrait;

    public function getDescription() : string
    {
        return 'Sets status default value (only data)';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }

    public function postUp(Schema $schema): void {
        parent::postUp($schema);
        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository(Activity::class);
        /** @var Activity $entity */
        foreach ($repo->findAll() as $entity){
            $entity->setStatus(Activity::STATUS_CREATED);
            $em->persist($entity);
        }
        $repo = $em->getRepository(ProductKind::class);
        /** @var ProductKind $activity */
        foreach ($repo->findAll() as $entity){
            $entity->setStatus(Activity::STATUS_CREATED);
            $em->persist($entity);
        }
        $em->flush();
    }
}
