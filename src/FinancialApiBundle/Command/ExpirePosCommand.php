<?php
namespace App\FinancialApiBundle\Command;

use App\FinancialApiBundle\Entity\PaymentOrder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ExpirePosCommand extends SynchronizedContainerAwareCommand implements ContainerAwareInterface
{
    /** Do not process more than MAX_RESULTS per execution */
    const MAX_RESULTS = 1000;

    use ContainerAwareTrait;

    protected function configure()
    {
        $this->setName('rec:pos:expire');
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Start POS expire command');

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository(PaymentOrder::class);
        $orders = $repo->findBy(['status' => PaymentOrder::STATUS_IN_PROGRESS], null, self::MAX_RESULTS);

        $output->writeln("Found " . count($orders) . " in-progress orders");
        /** @var PaymentOrder $order */
        foreach($orders as $order){
            $output->write("Checking order {$order->getId()}... ");
            $now = new \DateTime();
            $diff = $now->diff($order->getUpdated());
            if($diff->s > PaymentOrder::EXPIRE_TIME){
                $output->writeln("expired");
                $order->setStatus(PaymentOrder::STATUS_EXPIRED);
            }
            else {
                $output->writeln("not expired, {$diff->s} seconds elapsed");
            }
        }
        $em->flush();

        $output->writeln('Finish command');

    }
}