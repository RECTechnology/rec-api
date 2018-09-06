<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints\DateTime;
use Telepay\FinancialApiBundle\Entity\Balance;
use Telepay\FinancialApiBundle\Entity\Exchange;

class StartBalanceCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:balance:start')
            ->setDescription('Create start balance')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em= $this->getContainer()->get('doctrine')->getManager();


        //buscamos el user
        $companies =$em->getRepository('TelepayFinancialApiBundle:Group')->findAll();

        $progress = new ProgressBar($output, count($companies));

        $progress->start();
        foreach ($companies as $company){
            $wallets = $company->getWallets();
            foreach ($wallets as $wallet){
                $balance = new Balance();
                $balance->setCurrency($wallet->getCurrency());
                $balance->setConcept('Start Balance');
                $balance->setAmount($wallet->getAvailable());
                $balance->setBalance($wallet->getAvailable());
                $balance->setDate(new \DateTime());
                $balance->setGroup($company);
                $balance->setTransactionId(0);

                $em->persist($balance);
                $em->flush();
            }
            $progress->advance();
        }
        $progress->finish();
    }
}