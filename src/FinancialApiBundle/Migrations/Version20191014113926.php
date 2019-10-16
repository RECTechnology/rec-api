<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use App\FinancialApiBundle\Entity\Activity;
use App\FinancialApiBundle\Entity\ProductKind;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class Version20191014113926
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20191014113926 extends AbstractMigration implements ContainerAwareInterface{

    use ContainerAwareTrait;

    public function getDescription() : string
    {
        return 'Adds status to activities and products (only schema)';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Activity ADD status VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE ProductKind ADD status VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Activity DROP status');
        $this->addSql('ALTER TABLE ProductKind DROP status');
    }
}
