<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20200408094646
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20200408094646 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds more properties to PaymentOrder and changes id to GUID';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE PaymentOrder ADD access_key VARCHAR(255) NOT NULL, ADD signature VARCHAR(255) NOT NULL, ADD signature_version VARCHAR(255) NOT NULL, ADD reference VARCHAR(255) DEFAULT NULL, ADD concept VARCHAR(255) DEFAULT NULL, CHANGE id id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE PaymentOrder DROP access_key, DROP signature, DROP signature_version, DROP reference, DROP concept, CHANGE id id INT AUTO_INCREMENT NOT NULL');
    }
}
