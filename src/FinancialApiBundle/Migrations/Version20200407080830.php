<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20200407080830
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20200407080830 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds PaymentOrder and recicles POS';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE PaymentOrder (id INT AUTO_INCREMENT NOT NULL, pos_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, status VARCHAR(255) NOT NULL, ip_address VARCHAR(255) NOT NULL, payment_address VARCHAR(255) NOT NULL, amount VARCHAR(255) NOT NULL, ko_url VARCHAR(255) NOT NULL, ok_url VARCHAR(255) NOT NULL, INDEX IDX_507F800B41085FAE (pos_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE PaymentOrder ADD CONSTRAINT FK_507F800B41085FAE FOREIGN KEY (pos_id) REFERENCES Pos (id)');
        $this->addSql('ALTER TABLE POS DROP FOREIGN KEY FK_167EA426FE54D947');
        $this->addSql('DROP INDEX IDX_167EA426FE54D947 ON POS');
        $this->addSql('DROP INDEX UNIQ_167EA4265E237E06 ON POS');
        $this->addSql('ALTER TABLE POS ADD created DATETIME NOT NULL, ADD updated DATETIME NOT NULL, ADD notification_url VARCHAR(255) NOT NULL, ADD access_secret VARCHAR(255) NOT NULL, ADD access_key VARCHAR(255) NOT NULL, DROP name, DROP cname, DROP currency, DROP type, DROP pos_id, DROP expires_in, DROP linking_code, DROP linked, CHANGE group_id account_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE POS ADD CONSTRAINT FK_B894A04C9B6B5FBA FOREIGN KEY (account_id) REFERENCES fos_group (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B894A04C9B6B5FBA ON POS (account_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE PaymentOrder');
        $this->addSql('ALTER TABLE Pos DROP FOREIGN KEY FK_B894A04C9B6B5FBA');
        $this->addSql('DROP INDEX UNIQ_B894A04C9B6B5FBA ON Pos');
        $this->addSql('ALTER TABLE Pos ADD name VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, ADD cname VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, ADD currency VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, ADD type VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, ADD pos_id VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, ADD expires_in INT NOT NULL, ADD linking_code VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, ADD linked TINYINT(1) NOT NULL, DROP created, DROP updated, DROP notification_url, DROP access_secret, DROP access_key, CHANGE account_id group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Pos ADD CONSTRAINT FK_167EA426FE54D947 FOREIGN KEY (group_id) REFERENCES fos_group (id)');
        $this->addSql('CREATE INDEX IDX_167EA426FE54D947 ON Pos (group_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_167EA4265E237E06 ON Pos (name)');
    }
}
