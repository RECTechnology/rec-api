<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221025090235 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add configurations settings';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $now = new \DateTime();
        $settings = [
            ['scope' => 'admin_panel', 'name' => 'menu_item_qualifications', 'value' => 'disabled', 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'admin_panel', 'name' => 'menu_item_b2b', 'value' => 'disabled', 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'admin_panel', 'name' => 'menu_item_email', 'value' => 'disabled', 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'admin_panel', 'name' => 'menu_item_reports', 'value' => 'disabled', 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
        ];

        foreach ($settings as $setting) {
            $this->addSql('INSERT INTO ConfigurationSetting (scope, name, value, created, updated) VALUES (:scope, :name, :value, :created, :updated )', $setting);
        }

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

    }
}
