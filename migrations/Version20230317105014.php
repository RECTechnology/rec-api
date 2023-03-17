<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230317105014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adding plural to ProductKinds names';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ProductKind ADD name_plural VARCHAR(255) DEFAULT NULL, ADD name_es_plural VARCHAR(255) DEFAULT NULL, ADD name_ca_plural VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B02ED1D5D32C3E66 ON ProductKind (name_plural)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B02ED1D51FD9EDA2 ON ProductKind (name_es_plural)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B02ED1D5525DA27E ON ProductKind (name_ca_plural)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_B02ED1D5D32C3E66 ON ProductKind');
        $this->addSql('DROP INDEX UNIQ_B02ED1D51FD9EDA2 ON ProductKind');
        $this->addSql('DROP INDEX UNIQ_B02ED1D5525DA27E ON ProductKind');
        $this->addSql('ALTER TABLE ProductKind DROP name_plural, DROP name_es_plural, DROP name_ca_plural');
    }
}
