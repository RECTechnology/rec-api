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
        return 'Adding lemonway fields to documents';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE DocumentKind ADD discr VARCHAR(255) NOT NULL, ADD lemon_doctype INT DEFAULT NULL');
        $this->addSql('ALTER TABLE Document ADD discr VARCHAR(255) NOT NULL, ADD lemon_reference VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Document DROP discr, DROP lemon_reference');
        $this->addSql('ALTER TABLE DocumentKind DROP discr, DROP lemon_doctype');
    }
}
