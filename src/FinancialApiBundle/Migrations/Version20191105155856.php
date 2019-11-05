<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use App\FinancialApiBundle\Document\Transaction;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Version20191105155856
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20191105155856 extends AbstractMigration implements ContainerAwareInterface {
    use ContainerAwareTrait;

    public function getDescription() : string
    {
        return 'migrates mongodb date string to isodate mongodb object';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }

    public function postUp(Schema $schema): void
    {
        parent::postUp($schema);
        /** @var DocumentManager $odm */
        $odm = $this->container->get('doctrine.odm.mongodb.document_manager');

        $repo = $odm->getRepository(Transaction::class);

        foreach ($repo->findAll() as $transaction){
            $created = $transaction->getCreated();
            if(!$created instanceof \DateTime) {
                $created = new \DateTime($created);
                $transaction->setCreated($created);
            }

            $updated = $transaction->getUpdated();
            if(!$updated instanceof \DateTime) {
                $updated = new \DateTime($updated);
                $transaction->setUpdated($updated);
            }

            $odm->persist($transaction);
        }
        $odm->flush();
    }


    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }

}
