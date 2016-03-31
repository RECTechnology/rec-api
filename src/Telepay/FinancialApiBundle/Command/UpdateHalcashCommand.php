<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Document\Transaction;

class UpdateHalcashCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:halcash:update')
            ->setDescription('Update swift halcash transactions')
            ->addOption(
                'id',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Define the id of the swift transaction.',
                null
            )
            ->addOption(
                'phone',
                null,
                InputArgument::OPTIONAL,
                'Sets phone for transaction.',
                null
            )
            ->addOption(
                'prefix',
                null,
                InputArgument::OPTIONAL,
                'Sets prefix for transaction.',
                null
            )
            ->addOption(
                'status',
                null,
                InputArgument::OPTIONAL,
                'Sets status for cash-in transaction.',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        if($input->getOption('id')){
            $id = $input->getOption('id');
        }
        else{
            $output->writeln('Id is required');
            return 0;
        }
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('id')->equals($id)
            ->getQuery();

        foreach($qb->toArray() as $transaction){
            $pay_out_info = $transaction->getPayOutInfo();

            if($input->getOption('status')){
                $transaction->setStatus('created');
                $pay_out_info['status'] = $input->getOption('status');
            }

            if($input->getOption('phone')){
                $pay_out_info['phone'] = $input->getOption('phone');
            }

            if($input->getOption('prefix')){
                $pay_out_info['prefix'] = $input->getOption('prefix');
            }
            $transaction->setPayOutInfo($pay_out_info);


            $transaction->setUpdated(new \MongoDate());
            $transaction->setCreated(new \MongoDate());
            $dm->persist($transaction);
            $dm->flush();
            $output->writeln('Transaction: ' . $transaction->getId() . 'updated');
        }

        $output->writeln('END');
    }
}