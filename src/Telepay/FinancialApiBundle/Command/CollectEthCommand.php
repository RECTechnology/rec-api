<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Document\Transaction;

class CollectEthCommand extends ContainerAwareCommand{
    protected function configure(){
        $this
            ->setName('telepay:eth:collect')
            ->setDescription('Collect eth to main address')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $em = $this->getContainer()->get('doctrine')->getManager();

        $deposits = $em->getRepository('TelepayFinancialApiBundle:CashInDeposit')->findBy(array(
            'currency'  =>  'eth'
        ));
        $output->writeln('Found '.count($deposits).' deposits');

        $depositCounter = 0;
        foreach ($deposits as $token){
            $deposits = $token->getDeposits();
            foreach ($deposits as $deposit){
                $deposit->setAmount($deposit->getAmount() / pow(10,10));
                $em->flush();
                $depositCounter++;
            }
        }

        $output->writeln('Updated '.$depositCounter.' deposits');
        $output->writeln('END');
    }
}