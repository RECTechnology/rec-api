<?php
namespace App\Command;

use App\DependencyInjection\Transactions\Core\Notificator;
use App\Document\Transaction;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RetryTransactionNotificationsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:notifications:retry')
            ->setDescription('Retry all failed notifications')
            ->addArgument(
                'limit',
                InputArgument::OPTIONAL,
                'limit the notifications to send (default: 10)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Listing transactions ...');
        $limit = intval($input->getArgument('limit'));
        if(!$limit) $limit = 10;

        /** @var DocumentManager $dm */
        $dm = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');

        /** @var Transaction $transaction */
        $transactions = $dm
            ->getRepository(Transaction::class)
            ->findBy(["notified" => false], null, $limit);

        if($transactions) {
            foreach ($transactions as $transaction) {

                $output->writeln('Transaction => ' . $transaction->getId());
                $output->writeln('Url notification => '.$transaction->getDataIn()['url_notification']);

                $output->writeln('Sending notification');

                /** @var Notificator $notificator */
                $notificator = $this->getContainer()->get('messenger');
                $transaction = $notificator->notificate($transaction);

                if($transaction->getNotified()){
                    $output->writeln('NOTIFIED TRANSACTION');
                }else{
                    $output->writeln('Notification FAILED');
                }
            }

        }else{
            $output->writeln('Transactions not found');
        }

    }

}