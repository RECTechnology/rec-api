<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220718114139 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add table NFTTransactions';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE NFTTransaction (id INT AUTO_INCREMENT NOT NULL, from_id INT DEFAULT NULL, to_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, method VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, tx_id VARCHAR(255) NOT NULL, topic_id VARCHAR(255) NOT NULL, original_token_id VARCHAR(255) NOT NULL, shared_token_id VARCHAR(255) NOT NULL, INDEX IDX_E6A0CF5978CED90B (from_id), INDEX IDX_E6A0CF5930354A65 (to_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE NFTTransaction ADD CONSTRAINT FK_E6A0CF5978CED90B FOREIGN KEY (from_id) REFERENCES fos_group (id)');
        $this->addSql('ALTER TABLE NFTTransaction ADD CONSTRAINT FK_E6A0CF5930354A65 FOREIGN KEY (to_id) REFERENCES fos_group (id)');
        $this->addSql('ALTER TABLE fos_group ADD nft_wallet LONGTEXT NOT NULL, ADD nft_wallet_pk LONGTEXT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE NFTTransaction');
        $this->addSql('ALTER TABLE fos_group DROP nft_wallet, DROP nft_wallet_pk');
    }
}
