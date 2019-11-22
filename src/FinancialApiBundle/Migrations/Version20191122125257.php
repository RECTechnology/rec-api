<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191122125257 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds relationship between documentkind and tiers';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE DocumentKind ADD tier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE DocumentKind ADD CONSTRAINT FK_1D062301A354F9DC FOREIGN KEY (tier_id) REFERENCES Tier (id)');
        $this->addSql('CREATE INDEX IDX_1D062301A354F9DC ON DocumentKind (tier_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE DocumentKind DROP FOREIGN KEY FK_1D062301A354F9DC');
        $this->addSql('DROP INDEX IDX_1D062301A354F9DC ON DocumentKind');
        $this->addSql('ALTER TABLE DocumentKind DROP tier_id');
    }
}
