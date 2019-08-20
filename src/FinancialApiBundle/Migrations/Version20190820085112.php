<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190820085112 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'sets description nullable, AND creates accounts, neighbourhoods, activities and products relationships';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE activity_group (activity_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_73C2727681C06096 (activity_id), INDEX IDX_73C27276FE54D947 (group_id), PRIMARY KEY(activity_id, group_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ProductKind (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE accounts_products_producing (productkind_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_D1A3B782AB2F8A8E (productkind_id), INDEX IDX_D1A3B782FE54D947 (group_id), PRIMARY KEY(productkind_id, group_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE accounts_products_consuming (productkind_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_FB2BA368AB2F8A8E (productkind_id), INDEX IDX_FB2BA368FE54D947 (group_id), PRIMARY KEY(productkind_id, group_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE activities_products_producing (productkind_id INT NOT NULL, activity_id INT NOT NULL, INDEX IDX_D7976FC7AB2F8A8E (productkind_id), INDEX IDX_D7976FC781C06096 (activity_id), PRIMARY KEY(productkind_id, activity_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE activities_products_consuming (productkind_id INT NOT NULL, activity_id INT NOT NULL, INDEX IDX_FD1F7B2DAB2F8A8E (productkind_id), INDEX IDX_FD1F7B2D81C06096 (activity_id), PRIMARY KEY(productkind_id, activity_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE activity_group ADD CONSTRAINT FK_73C2727681C06096 FOREIGN KEY (activity_id) REFERENCES Activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activity_group ADD CONSTRAINT FK_73C27276FE54D947 FOREIGN KEY (group_id) REFERENCES fos_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE accounts_products_producing ADD CONSTRAINT FK_D1A3B782AB2F8A8E FOREIGN KEY (productkind_id) REFERENCES ProductKind (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE accounts_products_producing ADD CONSTRAINT FK_D1A3B782FE54D947 FOREIGN KEY (group_id) REFERENCES fos_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE accounts_products_consuming ADD CONSTRAINT FK_FB2BA368AB2F8A8E FOREIGN KEY (productkind_id) REFERENCES ProductKind (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE accounts_products_consuming ADD CONSTRAINT FK_FB2BA368FE54D947 FOREIGN KEY (group_id) REFERENCES fos_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activities_products_producing ADD CONSTRAINT FK_D7976FC7AB2F8A8E FOREIGN KEY (productkind_id) REFERENCES ProductKind (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activities_products_producing ADD CONSTRAINT FK_D7976FC781C06096 FOREIGN KEY (activity_id) REFERENCES Activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activities_products_consuming ADD CONSTRAINT FK_FD1F7B2DAB2F8A8E FOREIGN KEY (productkind_id) REFERENCES ProductKind (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activities_products_consuming ADD CONSTRAINT FK_FD1F7B2D81C06096 FOREIGN KEY (activity_id) REFERENCES Activity (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE Product');
        $this->addSql('ALTER TABLE fos_group ADD neighbourhood_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fos_group ADD CONSTRAINT FK_4B019DDBF05C3E1C FOREIGN KEY (neighbourhood_id) REFERENCES Neighbourhood (id)');
        $this->addSql('CREATE INDEX IDX_4B019DDBF05C3E1C ON fos_group (neighbourhood_id)');
        $this->addSql('ALTER TABLE Neighbourhood CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE Activity CHANGE description description LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE accounts_products_producing DROP FOREIGN KEY FK_D1A3B782AB2F8A8E');
        $this->addSql('ALTER TABLE accounts_products_consuming DROP FOREIGN KEY FK_FB2BA368AB2F8A8E');
        $this->addSql('ALTER TABLE activities_products_producing DROP FOREIGN KEY FK_D7976FC7AB2F8A8E');
        $this->addSql('ALTER TABLE activities_products_consuming DROP FOREIGN KEY FK_FD1F7B2DAB2F8A8E');
        $this->addSql('CREATE TABLE Product (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, description LONGTEXT NOT NULL COLLATE utf8_unicode_ci, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE activity_group');
        $this->addSql('DROP TABLE ProductKind');
        $this->addSql('DROP TABLE accounts_products_producing');
        $this->addSql('DROP TABLE accounts_products_consuming');
        $this->addSql('DROP TABLE activities_products_producing');
        $this->addSql('DROP TABLE activities_products_consuming');
        $this->addSql('ALTER TABLE Activity CHANGE description description LONGTEXT NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE Neighbourhood CHANGE description description LONGTEXT NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE fos_group DROP FOREIGN KEY FK_4B019DDBF05C3E1C');
        $this->addSql('DROP INDEX IDX_4B019DDBF05C3E1C ON fos_group');
        $this->addSql('ALTER TABLE fos_group DROP neighbourhood_id');
    }
}
