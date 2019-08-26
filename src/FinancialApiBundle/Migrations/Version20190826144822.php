<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190826144822 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'adds mailing stuff to schema';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE MailingDelivery (id INT AUTO_INCREMENT NOT NULL, account_id INT DEFAULT NULL, mailing_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_23AAB4CA9B6B5FBA (account_id), INDEX IDX_23AAB4CA3931AB76 (mailing_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Mailing (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, subject VARCHAR(255) NOT NULL, content LONGTEXT DEFAULT NULL, attachments LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', scheduled_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mailing_mailingdelivery (mailing_id INT NOT NULL, mailingdelivery_id INT NOT NULL, INDEX IDX_45F471B83931AB76 (mailing_id), INDEX IDX_45F471B89709C028 (mailingdelivery_id), PRIMARY KEY(mailing_id, mailingdelivery_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE MailingDelivery ADD CONSTRAINT FK_23AAB4CA9B6B5FBA FOREIGN KEY (account_id) REFERENCES fos_group (id)');
        $this->addSql('ALTER TABLE MailingDelivery ADD CONSTRAINT FK_23AAB4CA3931AB76 FOREIGN KEY (mailing_id) REFERENCES Mailing (id)');
        $this->addSql('ALTER TABLE mailing_mailingdelivery ADD CONSTRAINT FK_45F471B83931AB76 FOREIGN KEY (mailing_id) REFERENCES Mailing (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mailing_mailingdelivery ADD CONSTRAINT FK_45F471B89709C028 FOREIGN KEY (mailingdelivery_id) REFERENCES MailingDelivery (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE Mail');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mailing_mailingdelivery DROP FOREIGN KEY FK_45F471B89709C028');
        $this->addSql('ALTER TABLE MailingDelivery DROP FOREIGN KEY FK_23AAB4CA3931AB76');
        $this->addSql('ALTER TABLE mailing_mailingdelivery DROP FOREIGN KEY FK_45F471B83931AB76');
        $this->addSql('CREATE TABLE Mail (id INT AUTO_INCREMENT NOT NULL, subject LONGTEXT NOT NULL COLLATE utf8_unicode_ci, body LONGTEXT NOT NULL COLLATE utf8_unicode_ci, dst VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, status VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, created DATETIME NOT NULL, updated DATETIME NOT NULL, counter INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE MailingDelivery');
        $this->addSql('DROP TABLE Mailing');
        $this->addSql('DROP TABLE mailing_mailingdelivery');
    }
}
