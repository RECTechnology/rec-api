<?php
namespace App\FinancialApiBundle\Command;

use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Mailing;
use App\FinancialApiBundle\Entity\MailingDelivery;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendMailingCommand extends SynchronizedContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('rec:mailing:send');
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output) {
        $output->writeln('Init ' . $this->getName());
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository(Mailing::class);
        $mailings = $repo->findBy(['processed' => false], null, 10);
        $output->writeln("Processing " . count($mailings) . " mailings");
        foreach ($mailings as $mailing){
            $output->writeln("Mailing: " . $mailing->getId());
            $now = (new DateTime())->getTimestamp();
            $scheduledAt = $mailing->getScheduledAt()->getTimestamp();
            $output->writeln("Now: $now, scheduledAt: $scheduledAt, send: " . (($now > $scheduledAt)? "true": "false"));
            if($now > $scheduledAt){
                /** @var MailingDelivery $delivery */
                foreach($mailing->getDeliveries() as $delivery){
                    /** @var Group $account */
                    $account = $delivery->getAccount();
                    $output->writeln("Delivering to: " . $account->getEmail());
                    if($delivery->getStatus() == MailingDelivery::STATUS_CREATED)
                        $delivery->setStatus(MailingDelivery::STATUS_SCHEDULED);
                }
                $mailing->setProcessed(true);
            }
        }
        $em->flush();
        $output->writeln('Finish ' . $this->getName());
    }
}