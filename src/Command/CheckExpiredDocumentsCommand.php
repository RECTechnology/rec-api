<?php

namespace App\Command;


use App\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use App\Entity\UserWallet;
use App\Financial\Currency;

class CheckExpiredDocumentsCommand extends SynchronizedContainerAwareCommand {
    protected function configure()
    {
        $this
            ->setName('rec:check:expired:documents')
            ->setDescription('Check and remove invalid documents')
        ;
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output){
        $output->writeln("Init expired documents command");
        $em = $this->container->get('doctrine.orm.entity_manager');

        $documents = $this->findExpiredDocuments($em);

        $output->writeln(count($documents)." expired documents found");

        /** @var Document $document */
        foreach ($documents as $document){
            $document->setStatus('rec_expired');
            $document->setStatusText("Requiere documento nuevo");

            $em->flush();
        }

        $output->writeln('Command ended successfully');

    }

    public function findExpiredDocuments(EntityManagerInterface $em){
        $today = new \DateTime();
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder->select('d')
            ->from(Document::class, 'd')
            ->where('d.valid_until < :today')
            ->andWhere('d.status = :status')
            ->setParameter('status', Document::STATUS_APP_APPROVED)
            ->setParameter('today', $today);

        return $queryBuilder->getQuery()->getResult();
    }
}