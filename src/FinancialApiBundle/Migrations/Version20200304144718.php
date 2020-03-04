<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20200304144718
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20200304144718 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'adds nullable to lemon reference and lemon status';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Iban CHANGE lemon_reference lemon_reference VARCHAR(255) DEFAULT NULL, CHANGE lemon_status lemon_status INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Iban CHANGE lemon_reference lemon_reference VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, CHANGE lemon_status lemon_status INT NOT NULL');
    }
}
