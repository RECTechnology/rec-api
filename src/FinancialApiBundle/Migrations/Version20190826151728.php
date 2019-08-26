<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190826151728 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE mailing_mailingdelivery');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE mailing_mailingdelivery (mailing_id INT NOT NULL, mailingdelivery_id INT NOT NULL, INDEX IDX_45F471B89709C028 (mailingdelivery_id), INDEX IDX_45F471B83931AB76 (mailing_id), PRIMARY KEY(mailing_id, mailingdelivery_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE mailing_mailingdelivery ADD CONSTRAINT FK_45F471B83931AB76 FOREIGN KEY (mailing_id) REFERENCES Mailing (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mailing_mailingdelivery ADD CONSTRAINT FK_45F471B89709C028 FOREIGN KEY (mailingdelivery_id) REFERENCES MailingDelivery (id) ON DELETE CASCADE');
    }
}
