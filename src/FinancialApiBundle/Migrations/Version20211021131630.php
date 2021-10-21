<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211021131630 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adding relation with parent activity';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Activity ADD parent_id INT DEFAULT NULL, DROP parent');
        $this->addSql('ALTER TABLE Activity ADD CONSTRAINT FK_55026B0C727ACA70 FOREIGN KEY (parent_id) REFERENCES Activity (id)');
        $this->addSql('CREATE INDEX IDX_55026B0C727ACA70 ON Activity (parent_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Activity DROP FOREIGN KEY FK_55026B0C727ACA70');
        $this->addSql('DROP INDEX IDX_55026B0C727ACA70 ON Activity');
        $this->addSql('ALTER TABLE Activity ADD parent VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, DROP parent_id');
    }
}
