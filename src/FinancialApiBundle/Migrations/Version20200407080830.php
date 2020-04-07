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

        $this->addSql('DROP TABLE POS');
        $this->addSql('CREATE TABLE Pos (id INT AUTO_INCREMENT NOT NULL, account_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, active TINYINT(1) NOT NULL, notification_url VARCHAR(255) NOT NULL, access_secret VARCHAR(255) NOT NULL, access_key VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_B894A04C9B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE PaymentOrder (id INT AUTO_INCREMENT NOT NULL, pos_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, status VARCHAR(255) NOT NULL, ip_address VARCHAR(255) NOT NULL, payment_address VARCHAR(255) NOT NULL, amount VARCHAR(255) NOT NULL, ko_url VARCHAR(255) NOT NULL, ok_url VARCHAR(255) NOT NULL, INDEX IDX_507F800B41085FAE (pos_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Pos ADD CONSTRAINT FK_B894A04C9B6B5FBA FOREIGN KEY (account_id) REFERENCES fos_group (id)');
        $this->addSql('ALTER TABLE PaymentOrder ADD CONSTRAINT FK_507F800B41085FAE FOREIGN KEY (pos_id) REFERENCES Pos (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE PaymentOrder DROP FOREIGN KEY FK_507F800B41085FAE');
        $this->addSql('DROP TABLE Pos');
        $this->addSql('DROP TABLE PaymentOrder');
    }
}
