<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220314131653 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add more rezero fields to group';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE fos_group ADD rezero_b2b_api_key VARCHAR(255) DEFAULT NULL, ADD rezero_b2b_user_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B019DDBD4BEC3F0 ON fos_group (rezero_b2b_api_key)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4B019DDBBAC2FDF8 ON fos_group (rezero_b2b_user_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_4B019DDBD4BEC3F0 ON fos_group');
        $this->addSql('DROP INDEX UNIQ_4B019DDBBAC2FDF8 ON fos_group');
        $this->addSql('ALTER TABLE fos_group DROP rezero_b2b_api_key, DROP rezero_b2b_user_id');
    }
}
