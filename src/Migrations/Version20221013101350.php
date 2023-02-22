<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221013101350 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Connect token_reward with challenge';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE TokenReward ADD challenge_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE TokenReward ADD CONSTRAINT FK_E607106F98A21AC6 FOREIGN KEY (challenge_id) REFERENCES Challenge (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E607106F98A21AC6 ON TokenReward (challenge_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE KYC CHANGE dateBirth dateBirth VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE TokenReward DROP FOREIGN KEY FK_E607106F98A21AC6');
        $this->addSql('DROP INDEX UNIQ_E607106F98A21AC6 ON TokenReward');
        $this->addSql('ALTER TABLE TokenReward DROP challenge_id');
    }
}
