<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20191128124327
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20191128124327 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Renaming table for treasure withdrawals and adding lemonway stuff to documents';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE TreasureWithdrawalValidation DROP FOREIGN KEY FK_1CE35B54B191BE6B');
        $this->addSql('CREATE TABLE TreasureWithdrawal (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, status VARCHAR(255) NOT NULL, transaction_id VARCHAR(255) DEFAULT NULL, amount INT NOT NULL, expires_at DATETIME DEFAULT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP TABLE TreasureWithdrawalAttempt');
        $this->addSql('ALTER TABLE DocumentKind ADD discr VARCHAR(255) NOT NULL, ADD lemon_doctype INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Document ADD discr VARCHAR(255) NOT NULL, ADD lemon_reference VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_1CE35B54B191BE6B ON TreasureWithdrawalValidation');
        $this->addSql('ALTER TABLE TreasureWithdrawalValidation ADD status VARCHAR(255) NOT NULL, DROP accepted, CHANGE attempt_id withdrawal_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE TreasureWithdrawalValidation ADD CONSTRAINT FK_1CE35B54697D393B FOREIGN KEY (withdrawal_id) REFERENCES TreasureWithdrawal (id)');
        $this->addSql('CREATE INDEX IDX_1CE35B54697D393B ON TreasureWithdrawalValidation (withdrawal_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE TreasureWithdrawalValidation DROP FOREIGN KEY FK_1CE35B54697D393B');
        $this->addSql('CREATE TABLE TreasureWithdrawalAttempt (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, transaction_id VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, amount INT NOT NULL, expires_at DATETIME DEFAULT NULL, description VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE TreasureWithdrawal');
        $this->addSql('ALTER TABLE Document DROP discr, DROP lemon_reference');
        $this->addSql('ALTER TABLE DocumentKind DROP discr, DROP lemon_doctype');
        $this->addSql('DROP INDEX IDX_1CE35B54697D393B ON TreasureWithdrawalValidation');
        $this->addSql('ALTER TABLE TreasureWithdrawalValidation ADD accepted TINYINT(1) DEFAULT NULL, DROP status, CHANGE withdrawal_id attempt_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE TreasureWithdrawalValidation ADD CONSTRAINT FK_1CE35B54B191BE6B FOREIGN KEY (attempt_id) REFERENCES TreasureWithdrawalAttempt (id)');
        $this->addSql('CREATE INDEX IDX_1CE35B54B191BE6B ON TreasureWithdrawalValidation (attempt_id)');
    }
}
