<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221104124343 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add package and configuration settings';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE Package (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, name VARCHAR(255) NOT NULL, purchased TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ConfigurationSetting ADD package_id INT DEFAULT NULL, ADD type VARCHAR(255) DEFAULT NULL, ADD platform VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ConfigurationSetting ADD CONSTRAINT FK_45211111F44CABFF FOREIGN KEY (package_id) REFERENCES Package (id)');
        $this->addSql('CREATE INDEX IDX_45211111F44CABFF ON ConfigurationSetting (package_id)');


        //delete current values to recreate in the new form
        $this->addSql('DELETE FROM ConfigurationSetting');

        $now = new \DateTime();
        $packages = [
            ['created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s'), 'name' => 'b2b_atarca', 'purchased' => 0],
            ['created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s'), 'name' => 'bulk_mailing', 'purchased' => 0],
            ['created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s'), 'name' => 'badges', 'purchased' => 0],
            ['created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s'), 'name' => 'reports', 'purchased' => 0],
            ['created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s'), 'name' => 'challenges', 'purchased' => 0],
            ['created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s'), 'name' => 'nft_wallet', 'purchased' => 0],
            ['created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s'), 'name' => 'qualifications', 'purchased' => 0],
        ];
        foreach ($packages as $package){
            $this->addSql('INSERT INTO Package (created, updated, name, purchased) VALUES (:created, :updated, :name, :purchased)', $package);
        }

        $settings = [
            ['scope' => 'admin_panel', 'name' => 'menu_item_b2b', 'value' => 'disabled', 'type' => 'boolean', 'platform' => 'admin_panel', 'package_id' => 1, 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'admin_panel', 'name' => 'menu_item_email', 'value' => 'disabled', 'type' => 'boolean', 'platform' => 'admin_panel', 'package_id' => 2, 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'admin_panel', 'name' => 'menu_item_qualifications', 'value' => 'disabled', 'type' => 'boolean', 'platform' => 'admin_panel', 'package_id' => 3, 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'admin_panel', 'name' => 'menu_item_reports', 'value' => 'disabled', 'type' => 'boolean', 'platform' => 'admin_panel', 'package_id' => 4, 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'nft_wallet', 'name' => 'c2b_challenges_status', 'value' => 'disabled', 'type' => 'boolean', 'platform' => 'api', 'package_id' => 5, 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'nft_wallet', 'name' => 'create_nft_wallet', 'value' => 'disabled', 'type' => 'boolean', 'platform' => 'api', 'package_id' => 6, 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'nft_wallet', 'name' => 'default_funding_amount', 'value' => '10000000000000000', 'type' => 'int', 'platform' => 'api', 'package_id' => 6, 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'badges', 'name' => 'max_qualifications', 'value' => '10', 'type' => 'int', 'platform' => 'api', 'package_id' => 3, 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'badges', 'name' => 'min_qualifications', 'value' => '10', 'type' => 'int', 'platform' => 'api', 'package_id' => 3, 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'badges', 'name' => 'threshold', 'value' => '0.5', 'type' => 'double', 'platform' => 'api', 'package_id' => 3, 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'app', 'name' => 'c2b_challenges_status', 'value' => 'enabled', 'type' => 'boolean', 'platform' => 'app', 'package_id' => 5, 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'app', 'name' => 'map_badges_filter_status', 'value' => 'enabled', 'type' => 'boolean', 'platform' => 'app', 'package_id' => 3, 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'qualifications', 'name' => 'qualifications_system_status', 'value' => 'disabled', 'type' => 'boolean', 'platform' => 'app', 'package_id' => 7, 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'app', 'name' => 'profile_pis_status', 'value' => 'enabled', 'type' => 'boolean', 'platform' => 'senfake', 'package_id' => 3, 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
        ];

        foreach ($settings as $setting) {
            $this->addSql('INSERT INTO ConfigurationSetting (scope, name, value, type, platform, created, updated) VALUES (:scope, :name, :value, :type, :platform, :created, :updated )', $setting);
        }

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ConfigurationSetting DROP FOREIGN KEY FK_45211111F44CABFF');
        $this->addSql('DROP TABLE Package');
        $this->addSql('DROP INDEX IDX_45211111F44CABFF ON ConfigurationSetting');
        $this->addSql('ALTER TABLE ConfigurationSetting DROP package_id, DROP type, DROP platform');
    }
}
