<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220524120258 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create C2B and B2B schema and generate badges';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE AccountAward (id INT AUTO_INCREMENT NOT NULL, account_id INT DEFAULT NULL, award_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, score INT NOT NULL, INDEX IDX_821D2DAA9B6B5FBA (account_id), INDEX IDX_821D2DAA3D5282CF (award_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Award (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, name VARCHAR(255) NOT NULL, name_es VARCHAR(255) NOT NULL, name_ca VARCHAR(255) NOT NULL, golden_threshold INT NOT NULL, silver_threshold INT NOT NULL, bronze_threshold INT NOT NULL, UNIQUE INDEX UNIQ_4B9A01E35E237E06 (name), UNIQUE INDEX UNIQ_4B9A01E35E71561D (name_es), UNIQUE INDEX UNIQ_4B9A01E39A4B4B46 (name_ca), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Badge (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, name VARCHAR(255) NOT NULL, name_es VARCHAR(255) NOT NULL, name_ca VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, description_es LONGTEXT DEFAULT NULL, description_ca LONGTEXT DEFAULT NULL, enabled TINYINT(1) NOT NULL, image_url VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_3F3167195E237E06 (name), UNIQUE INDEX UNIQ_3F3167195E71561D (name_es), UNIQUE INDEX UNIQ_3F3167199A4B4B46 (name_ca), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE badge_group (badge_id INT NOT NULL, group_id INT NOT NULL, INDEX IDX_90FE2E9F7A2C2FC (badge_id), INDEX IDX_90FE2E9FE54D947 (group_id), PRIMARY KEY(badge_id, group_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ConfigurationSetting (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, scope VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE Qualification (id INT AUTO_INCREMENT NOT NULL, reviewer_id INT DEFAULT NULL, account_id INT DEFAULT NULL, badge_id INT DEFAULT NULL, created DATETIME NOT NULL, updated DATETIME NOT NULL, value TINYINT(1) DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, INDEX IDX_46CBB3B70574616 (reviewer_id), INDEX IDX_46CBB3B9B6B5FBA (account_id), INDEX IDX_46CBB3BF7A2C2FC (badge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE AccountAward ADD CONSTRAINT FK_821D2DAA9B6B5FBA FOREIGN KEY (account_id) REFERENCES fos_group (id)');
        $this->addSql('ALTER TABLE AccountAward ADD CONSTRAINT FK_821D2DAA3D5282CF FOREIGN KEY (award_id) REFERENCES Award (id)');
        $this->addSql('ALTER TABLE badge_group ADD CONSTRAINT FK_90FE2E9F7A2C2FC FOREIGN KEY (badge_id) REFERENCES Badge (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE badge_group ADD CONSTRAINT FK_90FE2E9FE54D947 FOREIGN KEY (group_id) REFERENCES fos_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Qualification ADD CONSTRAINT FK_46CBB3B70574616 FOREIGN KEY (reviewer_id) REFERENCES fos_group (id)');
        $this->addSql('ALTER TABLE Qualification ADD CONSTRAINT FK_46CBB3B9B6B5FBA FOREIGN KEY (account_id) REFERENCES fos_group (id)');
        $this->addSql('ALTER TABLE Qualification ADD CONSTRAINT FK_46CBB3BF7A2C2FC FOREIGN KEY (badge_id) REFERENCES Badge (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE AccountAward DROP FOREIGN KEY FK_821D2DAA3D5282CF');
        $this->addSql('ALTER TABLE badge_group DROP FOREIGN KEY FK_90FE2E9F7A2C2FC');
        $this->addSql('ALTER TABLE Qualification DROP FOREIGN KEY FK_46CBB3BF7A2C2FC');
        $this->addSql('DROP TABLE AccountAward');
        $this->addSql('DROP TABLE Award');
        $this->addSql('DROP TABLE Badge');
        $this->addSql('DROP TABLE badge_group');
        $this->addSql('DROP TABLE ConfigurationSetting');
        $this->addSql('DROP TABLE Qualification');
    }

    public function postUp(Schema $schema): void
    {

        $this->insertBadge('Delivery', 'A domicilio', 'A domicili', '', '', 'El comerç fa enviament de comandes a domicili', true, 'https://rec.barcelona/wp-content/uploads/2022/05/Moto.svg');
        $this->insertBadge('Take away', 'Recoger pedido', 'Recollir comanda', '', '', 'El comerç permet fer comandes per telèfon per recollir-les quan estiguin preparades', true, 'https://rec.barcelona/wp-content/uploads/2022/05/Nota.svg');
        $this->insertBadge('Personal attention', 'Trato personal', 'Tracte personal', '', '', 'El comerç ofereix un tracte personal i proper', true, 'https://rec.barcelona/wp-content/uploads/2022/05/Near.svg');
        $this->insertBadge('Handmade', 'Hecho a mano', 'Fet a mà', '', '', 'El começ ven productes artesans', true, 'https://rec.barcelona/wp-content/uploads/2022/05/Muscle.svg');
        $this->insertBadge('Vegan', 'Vegano', 'Vegà', '', '', 'El comerç té productes vegans', true, 'https://rec.barcelona/wp-content/uploads/2022/05/Greenheart.svg');
        $this->insertBadge('In bulk', 'A granel', 'A granel', '', '', 'El comerç té productes a granel', true, 'https://rec.barcelona/wp-content/uploads/2022/05/Cesta.svg');
        $this->insertBadge('Local', 'Local', 'Local', '', '', 'El comerç ven productes de proximitat', true, 'https://rec.barcelona/wp-content/uploads/2022/05/Vecinos.svg');
        $this->insertBadge('Ecological', 'Ecológico', 'Ecològic', '', '', 'El comerç ven productes ecològics', true, 'https://rec.barcelona/wp-content/uploads/2022/05/Planta.svg');
        $this->insertBadge('Zero waste', 'Residuo cero', 'Residu zero', '', '', 'El comerç disposa de productes que fomenten el residu zero', true, 'https://rec.barcelona/wp-content/uploads/2022/05/Zerowaste.svg');

        $this->insertConfigurationSettings('qualifications', 'qualifications_system_status', 'disabled');
        $this->insertConfigurationSettings('badges', 'threshold', '0.5');
        $this->insertConfigurationSettings('badges', 'min_qualifications', '5');
        $this->insertConfigurationSettings('badges', 'max_qualifications', '5');

        $this->insertActivity('Green commerce','Comercio verde', 'Comerç verd', 'created', '188');
        parent::postUp($schema); // TODO: Change the autogenerated stub
    }

    public function insertBadge($name, $name_es, $name_ca, $description, $description_es, $description_ca, $enabled, $image){
        $date = new \DateTime();
        $this->connection->insert('Badge', array(
            'name' => $name,
            'name_es' => $name_es,
            'name_ca' => $name_ca,
            'description' => $description,
            'description_es' => $description_es,
            'description_ca' => $description_ca,
            'enabled' => $enabled,
            'image_url' => $image,
            'created' => $date->format('Y-m-d H:i:s'),
            'updated' => $date->format('Y-m-d H:i:s')
        ));
    }

    public function insertConfigurationSettings($scope, $name, $value){
        $date = new \DateTime();
        $this->connection->insert('ConfigurationSetting', array(
            'name' => $name,
            'scope' => $scope,
            'value' => $value,
            'created' => $date->format('Y-m-d H:i:s'),
            'updated' => $date->format('Y-m-d H:i:s')
        ));
    }

    public function insertActivity($name, $name_es, $name_ca, $status, $upc_code){
        $date = new \DateTime();
        $this->connection->insert('Activity', array(
            'name' => $name,
            'name_es' => $name_es,
            'name_ca' => $name_ca,
            'status' => $status,
            'upc_code' => $upc_code,
            'created' => $date->format('Y-m-d H:i:s'),
            'updated' => $date->format('Y-m-d H:i:s')
        ));
    }

}
