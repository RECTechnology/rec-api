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

        //find all tokens eth and change scale
        $tokens = $em->getRepository('TelepayFinancialApiBundle:CashInTokens')->findBy(array(
            'currency'  =>  'eth'
        ));

        $output->writeln('Found '.count($tokens).' tokens');
        $count = 0;
        foreach ($tokens as $token){
            $deposits = $token->getDeposits();
            foreach ($deposits as $deposit){
                //$deposit->setAmount($deposit->getAmount() / pow(10,10));
                //$em->flush();
                $count++;
            }
        }

        $output->writeln('Collected '.$count.' deposits');

        $output->writeln('END');
    }
}