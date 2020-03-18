<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20200318144306
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20200318144306 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Removes next from tiers';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Tier DROP FOREIGN KEY FK_84AC38B4AA23F6C8');
        $this->addSql('DROP INDEX UNIQ_84AC38B4AA23F6C8 ON Tier');
        $this->addSql('ALTER TABLE Tier DROP next_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Tier ADD next_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Tier ADD CONSTRAINT FK_84AC38B4AA23F6C8 FOREIGN KEY (next_id) REFERENCES Tier (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_84AC38B4AA23F6C8 ON Tier (next_id)');
    }
}
