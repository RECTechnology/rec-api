<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use App\FinancialApiBundle\Entity\LemonDocument;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class Version20200324122257
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20200324122257 extends AbstractMigration implements ContainerAwareInterface {

    use ContainerAwareTrait;

    public function getDescription() : string
    {
        return 'Adds auto-fetched field to external objects';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Document ADD auto_fetched TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE Iban ADD auto_fetched TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function postUp(Schema $schema): void
    {
        parent::postUp($schema);

        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $repo = $em->getRepository(LemonDocument::class);

        /** @var LemonDocument $document */
        foreach ($repo->findAll() as $document) {
            if ($document->getStatus() == 'auto_fetched'){
                $document->setAutoFetched(true);
                $lwStatus = $document->getExternalInfo()->S;
                $document->setStatus(LemonDocument::LW_STATUSES[$lwStatus]);
            }
        }
        $em->flush();
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Document DROP auto_fetched');
        $this->addSql('ALTER TABLE Iban DROP auto_fetched');
    }
}
