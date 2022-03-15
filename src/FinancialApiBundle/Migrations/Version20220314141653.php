<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220314141653 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add rezero_b2b_access_granted sms template';
    }

    public function up(Schema $schema) : void
    {
        $now = new \DateTime();
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('INSERT INTO SmsTemplates (type, body,  created, updated) VALUES ("rezero_b2b_access_granted", "ACCESO CONCEDIDO: Ya puedes acceder a la plataforma Comerç verd", :date, :date)', array("date" => $now->format("Y-m-d")));
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM SmsTemplates WHERE type = "rezero_b2b_access_granted"');
    }
}
