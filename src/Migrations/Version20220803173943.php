<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220803173943 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Convert funding amount to bigint';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE FundingNFTWalletTransaction CHANGE amount amount BIGINT NOT NULL');

        $now = new \DateTime();
        $settings = [
            ['scope' => 'nft_wallet', 'name' => 'create_nft_wallet', 'value' => 'disabled', 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'nft_wallet', 'name' => 'default_funding_amount', 'value' => '1000000000000', 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
            ['scope' => 'app', 'name' => 'profile_pis_status', 'value' => 'disabled', 'created' => $now->format('Y-m-d H:i:s'), 'updated' => $now->format('Y-m-d H:i:s')],
        ];

        foreach ($settings as $setting) {
            $this->addSql('INSERT INTO ConfigurationSetting (scope, name, value, created, updated) VALUES (:scope, :name, :value, :created, :updated )', $setting);
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE FundingNFTWalletTransaction CHANGE amount amount INT NOT NULL');
    }
}
