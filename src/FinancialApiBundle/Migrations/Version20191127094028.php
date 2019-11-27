<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191127094028 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'relate tiers and accounts through level_id';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE fos_group ADD level_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fos_group ADD CONSTRAINT FK_4B019DDB5FB14BA7 FOREIGN KEY (level_id) REFERENCES Tier (id)');
        $this->addSql('CREATE INDEX IDX_4B019DDB5FB14BA7 ON fos_group (level_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE fos_group DROP FOREIGN KEY FK_4B019DDB5FB14BA7');
        $this->addSql('DROP INDEX IDX_4B019DDB5FB14BA7 ON fos_group');
        $this->addSql('ALTER TABLE fos_group DROP level_id');
    }
}
