<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20200427084435
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20200427084435 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds table for payment order notifications';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE PaymentOrderNotification (id INT AUTO_INCREMENT NOT NULL, payment_order_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', created DATETIME NOT NULL, updated DATETIME NOT NULL, status VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', tries INT NOT NULL, INDEX IDX_D406A3C0CD592F50 (payment_order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE PaymentOrderNotification ADD CONSTRAINT FK_D406A3C0CD592F50 FOREIGN KEY (payment_order_id) REFERENCES PaymentOrder (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE PaymentOrderNotification');
    }
}
