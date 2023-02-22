<?php
namespace App\Command;

use App\Entity\PaymentOrder;
use App\Entity\Qualification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ExpirePendingQualificationsCommand extends SynchronizedContainerAwareCommand implements ContainerAwareInterface
{
    /** Time To Expire in seconds */
    const MAX_TIME = 300;

    use ContainerAwareTrait;

    protected function configure()
    {
        $this->setName('rec:qualifications:expire');
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Start Qualifications expire command');

        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository(Qualification::class);
        $qualifications = $repo->findBy(['status' => Qualification::STATUS_PENDING]);

        $output->writeln("Found " . count($qualifications) . " pending qualifications");
        /** @var Qualification $qualification */
        foreach($qualifications as $qualification){
            $output->write("Checking qualification {$qualification->getId()}... ");
            $now = new \DateTime();
            $diff = $now->getTimestamp() - $qualification->getCreated()->getTimestamp();
            if($diff > self::MAX_TIME){
                $output->writeln("expired");
                $qualification->setStatus(Qualification::STATUS_IGNORED);
            }
            else {
                $output->writeln("not expired, {$diff} seconds elapsed");
            }
        }
        $em->flush();

        $output->writeln('Finish command');

    }
}