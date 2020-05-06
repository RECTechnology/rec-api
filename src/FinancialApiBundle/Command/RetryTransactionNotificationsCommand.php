<?php
namespace App\FinancialApiBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\DependencyInjection\App\Commons\FeeDeal;
use App\FinancialApiBundle\DependencyInjection\App\Commons\LimitAdder;
use App\FinancialApiBundle\DependencyInjection\Transactions\Core\Notificator;
use App\FinancialApiBundle\Document\Transaction;

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
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        /** @var Transaction $transaction */
        $transactions = $dm
            ->getRepository('FinancialApiBundle:Transaction')
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