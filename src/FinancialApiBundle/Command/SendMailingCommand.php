<?php
namespace App\FinancialApiBundle\Command;

use App\FinancialApiBundle\Entity\Mailing;
use App\FinancialApiBundle\Entity\MailingDelivery;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SendMailingCommand extends SynchronizedContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('rec:mailing:send');
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Init command');
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository(Mailing::class);
        $mailings = $repo->findBy(['processed' => 0], null, 10);
        foreach ($mailings as $mailing){
            $now = (new DateTime())->getTimestamp();
            $scheduledAt = (new DateTime($mailing->getScheduledAt()))->getTimestamp();
            if($now <= $scheduledAt){
                /** @var MailingDelivery $delivery */
                foreach($mailing->getDeliveries() as $delivery){
                    if($delivery->getStatus() == MailingDelivery::STATUS_CREATED)
                        $delivery->setStatus(MailingDelivery::STATUS_SCHEDULED);
                }
            }
            $mailing->setProcessed(true);
        }
        $em->flush();
        $output->writeln('Finish command');
    }
}