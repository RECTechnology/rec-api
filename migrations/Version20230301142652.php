<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230301142652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activities_products (productkind_id INT NOT NULL, activity_id INT NOT NULL, INDEX IDX_16B89D15AB2F8A8E (productkind_id), INDEX IDX_16B89D1581C06096 (activity_id), PRIMARY KEY(productkind_id, activity_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE activities_products ADD CONSTRAINT FK_16B89D15AB2F8A8E FOREIGN KEY (productkind_id) REFERENCES ProductKind (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activities_products ADD CONSTRAINT FK_16B89D1581C06096 FOREIGN KEY (activity_id) REFERENCES Activity (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE activities_products_consuming');
        $this->addSql('DROP TABLE activities_products_producing');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activities_products_consuming (productkind_id INT NOT NULL, activity_id INT NOT NULL, INDEX IDX_FD1F7B2DAB2F8A8E (productkind_id), INDEX IDX_FD1F7B2D81C06096 (activity_id), PRIMARY KEY(productkind_id, activity_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE activities_products_producing (productkind_id INT NOT NULL, activity_id INT NOT NULL, INDEX IDX_D7976FC781C06096 (activity_id), INDEX IDX_D7976FC7AB2F8A8E (productkind_id), PRIMARY KEY(productkind_id, activity_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE activities_products_consuming ADD CONSTRAINT FK_FD1F7B2D81C06096 FOREIGN KEY (activity_id) REFERENCES Activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activities_products_consuming ADD CONSTRAINT FK_FD1F7B2DAB2F8A8E FOREIGN KEY (productkind_id) REFERENCES ProductKind (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activities_products_producing ADD CONSTRAINT FK_D7976FC781C06096 FOREIGN KEY (activity_id) REFERENCES Activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE activities_products_producing ADD CONSTRAINT FK_D7976FC7AB2F8A8E FOREIGN KEY (productkind_id) REFERENCES ProductKind (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE activities_products');
    }
}
