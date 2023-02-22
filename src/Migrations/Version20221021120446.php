<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221021120446 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Relate badges with challenges';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE badge_challenge (badge_id INT NOT NULL, challenge_id INT NOT NULL, INDEX IDX_E6A5D6C5F7A2C2FC (badge_id), INDEX IDX_E6A5D6C598A21AC6 (challenge_id), PRIMARY KEY(badge_id, challenge_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE badge_challenge ADD CONSTRAINT FK_E6A5D6C5F7A2C2FC FOREIGN KEY (badge_id) REFERENCES Badge (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE badge_challenge ADD CONSTRAINT FK_E6A5D6C598A21AC6 FOREIGN KEY (challenge_id) REFERENCES Challenge (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE badge_challenge');
    }
}
