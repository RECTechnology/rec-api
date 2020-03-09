<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20200305113745
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20200305113745 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Changes lemon_reference to external_reference in documents and added external reference and info to ibans';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Document ADD external_info LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE lemon_reference external_reference VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE Iban ADD external_reference VARCHAR(255) DEFAULT NULL, ADD external_info LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', DROP lemon_reference, CHANGE lemon_status lemon_status INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Document DROP external_info, CHANGE external_reference lemon_reference VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`');
        $this->addSql('ALTER TABLE Iban ADD lemon_reference VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, DROP external_reference, DROP external_info, CHANGE lemon_status lemon_status INT NOT NULL');
    }
}
