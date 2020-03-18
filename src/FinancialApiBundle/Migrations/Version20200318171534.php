<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20200318171534
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20200318171534 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds ON DELETE SET NULL to parent';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Tier DROP FOREIGN KEY FK_84AC38B4727ACA70');
        $this->addSql('ALTER TABLE Tier ADD CONSTRAINT FK_84AC38B4727ACA70 FOREIGN KEY (parent_id) REFERENCES Tier (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Tier DROP FOREIGN KEY FK_84AC38B4727ACA70');
        $this->addSql('ALTER TABLE Tier ADD CONSTRAINT FK_84AC38B4727ACA70 FOREIGN KEY (parent_id) REFERENCES Tier (id)');
    }
}
