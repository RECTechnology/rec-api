<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191125114414 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds migration for self-referencing previous tier';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Tier ADD previous_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Tier ADD CONSTRAINT FK_84AC38B42DE62210 FOREIGN KEY (previous_id) REFERENCES Tier (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_84AC38B42DE62210 ON Tier (previous_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Tier DROP FOREIGN KEY FK_84AC38B42DE62210');
        $this->addSql('DROP INDEX UNIQ_84AC38B42DE62210 ON Tier');
        $this->addSql('ALTER TABLE Tier DROP previous_id');
    }
}
