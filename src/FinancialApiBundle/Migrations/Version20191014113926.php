<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use App\FinancialApiBundle\Entity\Activity;
use App\FinancialApiBundle\Entity\ProductKind;
use App\FinancialApiBundle\Entity\User;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191014113926 extends AbstractMigration implements ContainerAwareInterface{

    use ContainerAwareTrait;

    public function getDescription() : string
    {
        return 'Adds status to activities and products';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Activity ADD status VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ProductKind ADD status VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Activity DROP status');
        $this->addSql('ALTER TABLE ProductKind DROP status');
    }

    public function postUp(Schema $schema): void {
        parent::postUp($schema);

        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');
        $this->setAllCreated($em, Activity::class);
        $this->setAllCreated($em, ProductKind::class);
        $em->flush();
    }

    private function setAllCreated(EntityManagerInterface $em, string $className){
        $repo = $em->getRepository($className);
        foreach ($repo->findAll() as $entity) {
            $entity->setStatus(Activity::STATUS_CREATED);
            $em->persist($entity);
        }
    }
}
