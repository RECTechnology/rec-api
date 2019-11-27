<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20191127094459
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20191127094459 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'drops ';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE TreasureWithdrawalAuthorizedEmail (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, email VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE TreasureWithdrawalValidation DROP FOREIGN KEY FK_1CE35B54B0644AEC');
        $this->addSql('DROP INDEX IDX_1CE35B54B0644AEC ON TreasureWithdrawalValidation');
        $this->addSql('ALTER TABLE TreasureWithdrawalValidation ADD token VARCHAR(255) DEFAULT NULL, CHANGE validator_id email_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE TreasureWithdrawalValidation ADD CONSTRAINT FK_1CE35B54A832C1C9 FOREIGN KEY (email_id) REFERENCES TreasureWithdrawalAuthorizedEmail (id)');
        $this->addSql('CREATE INDEX IDX_1CE35B54A832C1C9 ON TreasureWithdrawalValidation (email_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE TreasureWithdrawalValidation DROP FOREIGN KEY FK_1CE35B54A832C1C9');
        $this->addSql('DROP TABLE TreasureWithdrawalAuthorizedEmail');
        $this->addSql('DROP INDEX IDX_1CE35B54A832C1C9 ON TreasureWithdrawalValidation');
        $this->addSql('ALTER TABLE TreasureWithdrawalValidation DROP token, CHANGE email_id validator_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE TreasureWithdrawalValidation ADD CONSTRAINT FK_1CE35B54B0644AEC FOREIGN KEY (validator_id) REFERENCES fos_user (id)');
        $this->addSql('CREATE INDEX IDX_1CE35B54B0644AEC ON TreasureWithdrawalValidation (validator_id)');
    }
}
