<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230224181740 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change transaction_block_log from varchar to text';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transaction_block_log CHANGE log log LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transaction_block_log CHANGE log log VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
