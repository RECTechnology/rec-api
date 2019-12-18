<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20191218124228
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20191218124228 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds table iban and references it to accounts';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE Iban (id INT AUTO_INCREMENT NOT NULL, account_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, status VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, number VARCHAR(255) NOT NULL, INDEX IDX_5AE7C15C9B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Iban ADD CONSTRAINT FK_5AE7C15C9B6B5FBA FOREIGN KEY (account_id) REFERENCES fos_group (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE Iban');
    }
}
