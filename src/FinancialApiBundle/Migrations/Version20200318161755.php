<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20200318161755
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20200318161755 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Changes tier-tier relationship to 1-n';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Tier DROP FOREIGN KEY FK_84AC38B42DE62210');
        $this->addSql('DROP INDEX UNIQ_84AC38B42DE62210 ON Tier');
        $this->addSql('ALTER TABLE Tier CHANGE previous_id parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Tier ADD CONSTRAINT FK_84AC38B4727ACA70 FOREIGN KEY (parent_id) REFERENCES Tier (id)');
        $this->addSql('CREATE INDEX IDX_84AC38B4727ACA70 ON Tier (parent_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Tier DROP FOREIGN KEY FK_84AC38B4727ACA70');
        $this->addSql('DROP INDEX IDX_84AC38B4727ACA70 ON Tier');
        $this->addSql('ALTER TABLE Tier CHANGE parent_id previous_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Tier ADD CONSTRAINT FK_84AC38B42DE62210 FOREIGN KEY (previous_id) REFERENCES Tier (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_84AC38B42DE62210 ON Tier (previous_id)');
    }
}
