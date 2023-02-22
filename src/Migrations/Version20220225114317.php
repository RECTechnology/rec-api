<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220225114317 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add table for payment order nonce storage';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE PaymentOrderUsedNonce (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', pos_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, nonce INT NOT NULL, INDEX IDX_C4FF513C41085FAE (pos_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE PaymentOrderUsedNonce ADD CONSTRAINT FK_C4FF513C41085FAE FOREIGN KEY (pos_id) REFERENCES Pos (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE PaymentOrderUsedNonce');
        $this->addSql('DROP INDEX UNIQ_4B019DDBCB40B7BC ON fos_group');
    }
}
