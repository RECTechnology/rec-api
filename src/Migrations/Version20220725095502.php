<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220725095502 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add table to store funding transactions';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE FundingNFTWalletTransaction (id INT AUTO_INCREMENT NOT NULL, account_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, status VARCHAR(255) NOT NULL, tx_id VARCHAR(255) DEFAULT NULL, amount INT NOT NULL, INDEX IDX_5E6246069B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE FundingNFTWalletTransaction ADD CONSTRAINT FK_5E6246069B6B5FBA FOREIGN KEY (account_id) REFERENCES fos_group (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE FundingNFTWalletTransaction');
    }
}
