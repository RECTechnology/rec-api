<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191017133908 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add translation fields to translatable entities';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Activity ADD name_en VARCHAR(255) DEFAULT NULL, ADD name_es VARCHAR(255) DEFAULT NULL, ADD name_ca VARCHAR(255) DEFAULT NULL, ADD description_en LONGTEXT DEFAULT NULL, ADD description_es LONGTEXT DEFAULT NULL, ADD description_ca LONGTEXT DEFAULT NULL, ADD status VARCHAR(255) NOT NULL, DROP migration_version, CHANGE name name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ProductKind ADD name_es VARCHAR(255) DEFAULT NULL, ADD name_ca VARCHAR(255) DEFAULT NULL, ADD description_es VARCHAR(255) DEFAULT NULL, ADD description_ca VARCHAR(255) DEFAULT NULL, ADD status VARCHAR(255) NOT NULL, DROP migration_version, CHANGE name name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE Mailing ADD subject_es VARCHAR(255) DEFAULT NULL, ADD subject_ca VARCHAR(255) DEFAULT NULL, ADD content_es LONGTEXT DEFAULT NULL, ADD content_ca LONGTEXT NOT NULL, ADD attachments_es LONGTEXT NOT NULL, ADD attachments_ca LONGTEXT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Activity ADD migration_version VARCHAR(255) DEFAULT \'0\' COLLATE utf8_unicode_ci, DROP name_en, DROP name_es, DROP name_ca, DROP description_en, DROP description_es, DROP description_ca, DROP status, CHANGE name name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE Mailing DROP subject_es, DROP subject_ca, DROP content_es, DROP content_ca, DROP attachments_es, DROP attachments_ca');
        $this->addSql('ALTER TABLE ProductKind ADD migration_version VARCHAR(255) DEFAULT \'0\' COLLATE utf8_unicode_ci, DROP name_es, DROP name_ca, DROP description_es, DROP description_ca, DROP status, CHANGE name name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
    }
}
