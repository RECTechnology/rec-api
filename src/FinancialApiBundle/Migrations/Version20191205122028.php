<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20191205122028
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20191205122028 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Refactoring treasure withdrawal';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE TreasureWithdrawalAuthorizedEmail');
        $this->addSql('DROP TABLE TreasureWithdrawalValidation');
        $this->addSql('DROP TABLE TreasureWithdrawalAttempt');

        $this->addSql('CREATE TABLE TreasureWithdrawalAuthorizedEmail (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, email VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE TreasureWithdrawalValidation (id INT AUTO_INCREMENT NOT NULL, withdrawal_id INT DEFAULT NULL, email_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, status VARCHAR(255) NOT NULL, token VARCHAR(255) DEFAULT NULL, INDEX IDX_1CE35B54697D393B (withdrawal_id), INDEX IDX_1CE35B54A832C1C9 (email_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE TreasureWithdrawal (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, status VARCHAR(255) NOT NULL, transaction_id VARCHAR(255) DEFAULT NULL, amount INT NOT NULL, expires_at DATETIME DEFAULT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE TreasureWithdrawalValidation ADD CONSTRAINT FK_1CE35B54697D393B FOREIGN KEY (withdrawal_id) REFERENCES TreasureWithdrawal (id)');
        $this->addSql('ALTER TABLE TreasureWithdrawalValidation ADD CONSTRAINT FK_1CE35B54A832C1C9 FOREIGN KEY (email_id) REFERENCES TreasureWithdrawalAuthorizedEmail (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE TreasureWithdrawalValidation DROP FOREIGN KEY FK_1CE35B54A832C1C9');
        $this->addSql('ALTER TABLE TreasureWithdrawalValidation DROP FOREIGN KEY FK_1CE35B54697D393B');
        $this->addSql('DROP TABLE TreasureWithdrawalAuthorizedEmail');
        $this->addSql('DROP TABLE TreasureWithdrawalValidation');
        $this->addSql('DROP TABLE TreasureWithdrawal');
    }
}
