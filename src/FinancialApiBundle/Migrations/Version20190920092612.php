<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190920092612 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'changes table name delegated_change to delegated_changes';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE delegated_change_data DROP FOREIGN KEY FK_D928BDAE934E1641');
        $this->addSql('CREATE TABLE delegated_changes (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, status VARCHAR(255) NOT NULL, statistics LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\', scheduled_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('DROP TABLE delegated_change');
        $this->addSql('ALTER TABLE delegated_change_data DROP FOREIGN KEY FK_D928BDAE934E1641');
        $this->addSql('ALTER TABLE delegated_change_data ADD CONSTRAINT FK_D928BDAE934E1641 FOREIGN KEY (delegated_change_id) REFERENCES delegated_changes (id)');
        $this->addSql('ALTER TABLE TreasureWithdrawalAttempt CHANGE expires_at expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE delegated_change_data DROP FOREIGN KEY FK_D928BDAE934E1641');
        $this->addSql('CREATE TABLE delegated_change (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, created DATETIME NOT NULL, updated DATETIME NOT NULL, scheduled_at DATETIME DEFAULT NULL, statistics LONGTEXT NOT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json_array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE delegated_changes');
        $this->addSql('ALTER TABLE TreasureWithdrawalAttempt CHANGE expires_at expires_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE delegated_change_data DROP FOREIGN KEY FK_D928BDAE934E1641');
        $this->addSql('ALTER TABLE delegated_change_data ADD CONSTRAINT FK_D928BDAE934E1641 FOREIGN KEY (delegated_change_id) REFERENCES delegated_change (id)');
    }
}
