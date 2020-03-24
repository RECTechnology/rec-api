<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20200324142107
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20200324142107 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Removes lemon_status field from documents';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Document DROP lemon_status');
        $this->addSql('ALTER TABLE Iban DROP lemon_status');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Document ADD lemon_status INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Iban ADD lemon_status INT DEFAULT NULL');
    }
}
