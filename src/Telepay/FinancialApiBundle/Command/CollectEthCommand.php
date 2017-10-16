<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Entity\CashInDeposit;

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
            $output->writeln("\nToken: " . $token->getId() . "\n");

            $totalDepositedTransactions = $em->getRepository('TelepayFinancialApiBundle:CashInDeposit')->findBy(array(
                'token'    =>  $token,
                'confirmations'    =>  1,
                'status'    =>  'deposited'
            ));

            if($totalDepositedTransactions){
                $output->writeln('Transaction exists');
                foreach($totalDepositedTransactions as $deposited){}
                $depositAmount = $deposited->getAmount();
            }

            if($depositAmount > 0){
                $output->writeln('Total deposited: ' . $depositAmount);

                $methodDriver = $this->getContainer()->get('net.telepay.in.eth.v1');
                $sent = $methodDriver->sendInternal($token->getToken(), $depositAmount);

                if($sent) {
                    //new deposit
                    $output->writeln('Creating new deposit');
                    $deposit = new CashInDeposit();
                    $deposit->setToken($token);
                    $deposit->setStatus(CashInDeposit::$STATUS_COLLECTED);
                    $deposit->setAmount(-$depositAmount);
                    $deposit->setConfirmations(1);
                    $deposit->setHash(uniqid('hash-'));
                    $deposit->setExternalId(uniqid('external_id-'));
                    $em->persist($deposit);
                    $em->flush();
                }
                $count++;
            }
        }

        $output->writeln('Collected '.$count.' deposits');
        $output->writeln('END');
    }
}