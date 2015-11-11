<?php
namespace Telepay\FinancialApiBundle\Command;

use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Exception\ShellCommandFailureException;
use Telepay\FinancialApiBundle\Entity\Exchange;
use Telepay\FinancialApiBundle\Financial\Currency;

class ExchangePriceCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:exchange:price:update')
            ->setDescription('Update all exchange prices')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $em = $this->getContainer()->get('doctrine')->getManager();
        foreach(Currency::$ALL as $inputCurrency){
            foreach(Currency::$ALL as $outputCurrency){
                if($inputCurrency !== $outputCurrency){
                    $providerName = 'net.telepay.ticker.' . $inputCurrency . 'x' . $outputCurrency;
                    try {
                        $provider = $this->getContainer()->get($providerName);
                        if($provider->getInCurrency() === $inputCurrency && $provider->getOutCurrency() === $outputCurrency) {
                            $price = $provider->getPrice() * pow(10, (Currency::$SCALE[$outputCurrency] - Currency::$SCALE[$inputCurrency]));
                            $exchange = new Exchange();
                            $exchange->setSrc($inputCurrency);
                            $exchange->setDst($outputCurrency);
                            $exchange->setPrice($price);
                            $date = new \DateTime();
                            $exchange->setDate($date);
                            $em->persist($exchange);
                            $em->flush();
                            $output->writeln(
                                "SUCCESS: price (" . $inputCurrency . " -> " . $outputCurrency . "): " . $price
                            );
                        }
                        else {
                            throw new \LogicException("ERROR: Bad exchange, unexpected input or output currencies");
                        }
                    }catch (Exception $e) {
                        $output->writeln("ERROR: " . $e->getMessage());
                    }
                }

            }
        }

        $output->writeln("FINISHED");
    }
}