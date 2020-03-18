<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200318110839 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE documentkind_tier (documentkind_id INT NOT NULL, tier_id INT NOT NULL, INDEX IDX_A1EF6E0677317A4C (documentkind_id), INDEX IDX_A1EF6E06A354F9DC (tier_id), PRIMARY KEY(documentkind_id, tier_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE documentkind_tier ADD CONSTRAINT FK_A1EF6E0677317A4C FOREIGN KEY (documentkind_id) REFERENCES DocumentKind (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE documentkind_tier ADD CONSTRAINT FK_A1EF6E06A354F9DC FOREIGN KEY (tier_id) REFERENCES Tier (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE DocumentKind DROP FOREIGN KEY FK_1D062301A354F9DC');
        $this->addSql('DROP INDEX IDX_1D062301A354F9DC ON DocumentKind');
        $this->addSql('ALTER TABLE DocumentKind DROP tier_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE documentkind_tier');
        $this->addSql('ALTER TABLE DocumentKind ADD tier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE DocumentKind ADD CONSTRAINT FK_1D062301A354F9DC FOREIGN KEY (tier_id) REFERENCES Tier (id)');
        $this->addSql('CREATE INDEX IDX_1D062301A354F9DC ON DocumentKind (tier_id)');
    }
}
