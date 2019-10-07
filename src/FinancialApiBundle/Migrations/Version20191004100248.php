<?php

declare(strict_types=1);

namespace App\FinancialApiBundle\Migrations;

use App\FinancialApiBundle\Entity\User;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class Version20191004100248
 * @package App\FinancialApiBundle\Migrations
 */
final class Version20191004100248 extends AbstractMigration implements ContainerAwareInterface
{

    use ContainerAwareTrait;

    public function getDescription() : string
    {
        return 'Adds locale to users';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE fos_user ADD locale VARCHAR(255) NULL');
    }

    public function postUp(Schema $schema): void {
        parent::postUp($schema);
        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository(User::class);

        foreach ($repo->findAll() as $user) {
            $user->setLocale('es');
            $em->persist($user);
        }
        $em->flush();
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE fos_user DROP locale');
    }
}
