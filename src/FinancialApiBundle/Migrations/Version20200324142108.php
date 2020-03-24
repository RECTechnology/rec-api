<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use App\FinancialApiBundle\Entity\LemonDocument;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class Version20200324142108
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20200324142108 extends AbstractMigration implements ContainerAwareInterface {

    use ContainerAwareTrait;

    public function getDescription() : string
    {
        return 'Sets the corresponding lw status to documents';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }

    public function postUp(Schema $schema): void
    {
        parent::postUp($schema);

        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $repo = $em->getRepository(LemonDocument::class);

        /** @var LemonDocument $document */
        foreach ($repo->findAll() as $document) {
            if ($document->isAutoFetched()){
                $externalInfo = $document->getExternalInfo();
                $document->skipStatusChecks();
                $document->setStatus(LemonDocument::LW_STATUSES[$externalInfo['S']]);
                $em->persist($document);
            }
        }
        $em->flush();
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
