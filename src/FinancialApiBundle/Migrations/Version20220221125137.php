<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220221125137 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add b2b needed fields to group entity';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE fos_group ADD rezero_b2b_username VARCHAR(255) DEFAULT NULL, ADD rezero_b2b_access VARCHAR(255) NOT NULL DEFAULT "not_granted"');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B019DDB586B8E10 ON fos_group (rezero_b2b_username)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_4B019DDB586B8E10 ON fos_group');
        $this->addSql('ALTER TABLE fos_group DROP rezero_b2b_username, DROP rezero_b2b_access');
    }
}
