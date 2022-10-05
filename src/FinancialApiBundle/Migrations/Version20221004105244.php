<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221004105244 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Challenge migrations';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE AccountChallenge (id INT AUTO_INCREMENT NOT NULL, account_id INT DEFAULT NULL, challenge_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, INDEX IDX_14C9D7599B6B5FBA (account_id), INDEX IDX_14C9D75998A21AC6 (challenge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Challenge (id INT AUTO_INCREMENT NOT NULL, token_reward_id INT DEFAULT NULL, owner_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, title VARCHAR(60) NOT NULL, description LONGTEXT NOT NULL, action VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, threshold INT NOT NULL, amount_required INT NOT NULL, start_date DATETIME NOT NULL, finish_date DATETIME NOT NULL, cover_image VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_55F80BF290A321E6 (token_reward_id), INDEX IDX_55F80BF27E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE challenge_activity (challenge_id INT NOT NULL, activity_id INT NOT NULL, INDEX IDX_314AEEBA98A21AC6 (challenge_id), INDEX IDX_314AEEBA81C06096 (activity_id), PRIMARY KEY(challenge_id, activity_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE TokenReward (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, name VARCHAR(60) NOT NULL, description VARCHAR(255) DEFAULT NULL, image VARCHAR(255) NOT NULL, token_id INT DEFAULT NULL, status VARCHAR(255) NOT NULL, author_url VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE AccountChallenge ADD CONSTRAINT FK_14C9D7599B6B5FBA FOREIGN KEY (account_id) REFERENCES fos_group (id)');
        $this->addSql('ALTER TABLE AccountChallenge ADD CONSTRAINT FK_14C9D75998A21AC6 FOREIGN KEY (challenge_id) REFERENCES Challenge (id)');
        $this->addSql('ALTER TABLE Challenge ADD CONSTRAINT FK_55F80BF290A321E6 FOREIGN KEY (token_reward_id) REFERENCES TokenReward (id)');
        $this->addSql('ALTER TABLE Challenge ADD CONSTRAINT FK_55F80BF27E3C61F9 FOREIGN KEY (owner_id) REFERENCES fos_group (id)');
        $this->addSql('ALTER TABLE challenge_activity ADD CONSTRAINT FK_314AEEBA98A21AC6 FOREIGN KEY (challenge_id) REFERENCES Challenge (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE challenge_activity ADD CONSTRAINT FK_314AEEBA81C06096 FOREIGN KEY (activity_id) REFERENCES Activity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE NFTTransaction ADD token_reward_id INT DEFAULT NULL, ADD contract_name VARCHAR(255) DEFAULT NULL, CHANGE topic_id topic_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE NFTTransaction ADD CONSTRAINT FK_E6A0CF5990A321E6 FOREIGN KEY (token_reward_id) REFERENCES TokenReward (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E6A0CF5990A321E6 ON NFTTransaction (token_reward_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE AccountChallenge DROP FOREIGN KEY FK_14C9D75998A21AC6');
        $this->addSql('ALTER TABLE challenge_activity DROP FOREIGN KEY FK_314AEEBA98A21AC6');
        $this->addSql('ALTER TABLE Challenge DROP FOREIGN KEY FK_55F80BF290A321E6');
        $this->addSql('ALTER TABLE NFTTransaction DROP FOREIGN KEY FK_E6A0CF5990A321E6');
        $this->addSql('DROP TABLE AccountChallenge');
        $this->addSql('DROP TABLE Challenge');
        $this->addSql('DROP TABLE challenge_activity');
        $this->addSql('DROP TABLE TokenReward');
        $this->addSql('DROP INDEX UNIQ_E6A0CF5990A321E6 ON NFTTransaction');
        $this->addSql('ALTER TABLE NFTTransaction DROP token_reward_id, DROP contract_name, CHANGE topic_id topic_id VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
    }
}
