<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221228095851 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add account campaign table and version in campaigns';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE AccountCampaign (id INT AUTO_INCREMENT NOT NULL, campaign_id INT DEFAULT NULL, account_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, acumulated_bonus BIGINT DEFAULT NULL, spent_bonus BIGINT DEFAULT NULL, INDEX IDX_D3C5BB20F639F774 (campaign_id), INDEX IDX_D3C5BB209B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE AccountCampaign ADD CONSTRAINT FK_D3C5BB20F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id)');
        $this->addSql('ALTER TABLE AccountCampaign ADD CONSTRAINT FK_D3C5BB209B6B5FBA FOREIGN KEY (account_id) REFERENCES fos_group (id)');
        $this->addSql('ALTER TABLE campaign ADD version INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE AccountCampaign');
        $this->addSql('ALTER TABLE campaign DROP version');
    }
}
