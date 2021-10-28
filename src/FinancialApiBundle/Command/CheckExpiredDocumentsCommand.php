<?php

namespace App\FinancialApiBundle\Command;


use App\FinancialApiBundle\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Financial\Currency;

class CheckExpiredDocumentsCommand extends ContainerAwareCommand{
    protected function configure()
    {
        $this
            ->setName('rec:check:expired:documents')
            ->setDescription('Check and remove invalid documents')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $output->writeln("Init expired documents command");
        $em = $this->getContainer()->get('doctrine')->getManager();

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
            ->setParameter('status', 'rec_approved')
            ->setParameter('today', $today);

        return $queryBuilder->getQuery()->getResult();
    }
}