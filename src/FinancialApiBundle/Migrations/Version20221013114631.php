<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221013114631 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'hange realtion between nft transaction and token reward';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE NFTTransaction DROP INDEX UNIQ_E6A0CF5990A321E6, ADD INDEX IDX_E6A0CF5990A321E6 (token_reward_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE NFTTransaction DROP INDEX IDX_E6A0CF5990A321E6, ADD UNIQUE INDEX UNIQ_E6A0CF5990A321E6 (token_reward_id)');
    }
}
