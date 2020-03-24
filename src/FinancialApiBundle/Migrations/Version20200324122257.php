<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20200324122257
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20200324122257 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds auto-fetched field to external objects';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Document ADD auto_fetched TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE Iban ADD auto_fetched TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Document DROP auto_fetched');
        $this->addSql('ALTER TABLE Iban DROP auto_fetched');
    }
}
