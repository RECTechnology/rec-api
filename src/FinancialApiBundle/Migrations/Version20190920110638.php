<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190920110638 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE delegated_change_data ADD CONSTRAINT FK_D928BDAE934E1641 FOREIGN KEY (delegated_change_id) REFERENCES delegated_changes (id)');
        $this->addSql('ALTER TABLE TreasureWithdrawalAttempt CHANGE expires_at expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE TreasureWithdrawalAttempt CHANGE expires_at expires_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE delegated_change_data DROP FOREIGN KEY FK_D928BDAE934E1641');
    }
}
