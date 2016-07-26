<?php
namespace Telepay\FinancialApiBundle\Command;

use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Exception\ShellCommandFailureException;
use Telepay\FinancialApiBundle\Entity\StatusMethod;
use Telepay\FinancialApiBundle\Financial\Currency;

class AddExchangeMethodsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:methods:create_exchanges')
            ->setDescription('Create all exchange methods')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $em = $this->getContainer()->get('doctrine')->getManager();
        foreach(Currency::$ALL as $inputCurrency){
            foreach(Currency::$ALL as $outputCurrency){
                if($inputCurrency !== $outputCurrency){
                    $method = new StatusMethod();
                    $method->setBalance(0);
                    $method->setCurrency($inputCurrency.'to'.$outputCurrency);
                    $method->setMethod($inputCurrency.'to'.$outputCurrency);
                    $method->setType('exchange');
                    $method->setStatus('available');

                    $em->persist($method);
                    $em->flush();
                }
            }
        }

        $output->writeln("FINISHED");
    }

}