<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use App\FinancialApiBundle\Entity\Document;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class Version20191211104723
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20191211104723 extends AbstractMigration implements ContainerAwareInterface {

    use ContainerAwareTrait;

    public function getDescription() : string
    {
        return 'Removes nullable from Document.content';
    }

    public function preUp(Schema $schema): void
    {
        parent::preUp($schema);

        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $repo = $em->getRepository(Document::class);

        /** @var Document $document */
        foreach ($repo->findAll() as $document) {
            if($document->getContent() == null) {
                $em->remove($document);
            }
        }
        $em->flush();
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Document CHANGE content content VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE Document CHANGE content content VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`');
    }

}
