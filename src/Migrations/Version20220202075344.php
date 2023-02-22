<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220202075344 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add transaction blobk log table';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE transaction_block_log (id INT AUTO_INCREMENT NOT NULL, block_txs_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, type VARCHAR(255) NOT NULL, log VARCHAR(255) NOT NULL, INDEX IDX_AE933ABC41F700A1 (block_txs_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE transaction_block_log ADD CONSTRAINT FK_AE933ABC41F700A1 FOREIGN KEY (block_txs_id) REFERENCES delegated_changes (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE transaction_block_log');
    }
}
