<?php
namespace App\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\DependencyInjection\App\Commons\FeeDeal;
use App\FinancialApiBundle\DependencyInjection\App\Commons\LimitAdder;
use App\FinancialApiBundle\DependencyInjection\App\Commons\Notificator;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Exchange;
use App\FinancialApiBundle\Financial\Currency;

class NotificateTransactionCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:notificate:transaction')
            ->setDescription('Notificate a transaction passind the id')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'What trnsaction do you want to notificate?'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Searching transaction');
        $id = $input->getArgument('id');

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $transaction = $dm->getRepository('FinancialApiBundle:Transaction')->find($id);

        if($transaction) {
            $output->writeln('Transaction => '.$transaction->getId());
            $output->writeln('Url notification => '.$transaction->getDataIn()['url_notification']);

            $output->writeln('Sending notification');

            /** @var Notificator $notificator */
            $notificator = $this->getContainer()->get('notificator');
            $transaction = $notificator->notificate($transaction);

            if($transaction->getNotified()){
                $output->writeln('NOTIFICATED TRANSACTION');
            }else{
                $output->writeln('Notification FAILED');
            }

        }else{
            $output->writeln('Transaction not found');
        }

    }

}