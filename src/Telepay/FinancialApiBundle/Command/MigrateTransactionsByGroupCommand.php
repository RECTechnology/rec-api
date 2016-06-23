<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/15/14
 * Time: 1:27 PM
 */

namespace Telepay\FinancialApiBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Document\Transaction;

class MigrateTransactionsByGroupCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:migrate:transactions-by-group')
            ->setDescription('Migrate transactions to groups')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();

        $transactions = $dm->getRepository("TelepayFinancialApiBundle:Transaction")->findAll();
        $userRepo = $em->getRepository("TelepayFinancialApiBundle:User");

        $output->writeln('Migrating '.count($transactions).' transactions...');
        $progress = new ProgressBar($output, count($transactions));
        $progress->start();

        $counterTransactions = 0;
        $counterTransactionsWithOutUserId = 0;
        $counterTransactionsWithOutUser = 0;
        $counterTransactionsWithOutGroup = 0;
        $counterTransactionsExchange = 0;
        foreach($transactions as $transaction){
            /*
            $user_id = $transaction->getUser();
            if($user_id){
                $user = $userRepo->find($user_id);
                if($user){
                    $group = $user->getGroups()[0];
                    if($group){
                        $transaction->setGroup($group->getId());
                        $dm->persist($transaction);
                        $dm->flush($transaction);
                    }else{
                        $counterTransactionsWithOutGroup++;
                    }

                }else{
                    $counterTransactionsWithOutUser ++;
                }

            }else{
                $counterTransactionsWithOutUserId ++;
            }
            */

            if($transaction->getMethod() == 'exchange' && $transaction->getType() == 'fee') {
                $counterTransactionsExchange++;
                $method = $transaction->getService();
                if($method) {
                    $transaction->setMethod($method);
                    $dm->persist($transaction);
                    $dm->flush($transaction);
                }
            }

            $progress->advance();
            $counterTransactions ++;
        }

        $progress->finish();

        $output->writeln('');
        $output->writeln($counterTransactions.' transactions updated');
        $output->writeln($counterTransactionsWithOutUserId.' transactionsWithOutUserId');
        $output->writeln($counterTransactionsWithOutUser.' transactionsWithOutUser');
        $output->writeln($counterTransactionsWithOutGroup.' transactionsWithOutGroup');
        $output->writeln($counterTransactionsExchange.' transactionsExchange');

        $output->writeln('All done');
    }

}