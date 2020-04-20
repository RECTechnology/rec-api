<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20200420184329
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20200420184329 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds refund_transaction_id to PaymentOrder and renames transaction_id to payment_transaction_id';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE PaymentOrder ADD refund_transaction_id VARCHAR(255) DEFAULT NULL, CHANGE transaction_id payment_transaction_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE PaymentOrder ADD transaction_id VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, DROP payment_transaction_id, DROP refund_transaction_id');
    }
}
