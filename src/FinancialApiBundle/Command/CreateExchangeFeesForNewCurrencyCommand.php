<?php
namespace App\FinancialApiBundle\Command;

use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Exception\ShellCommandFailureException;
use App\FinancialApiBundle\Entity\Exchange;
use App\FinancialApiBundle\Entity\ServiceFee;
use App\FinancialApiBundle\Entity\StatusMethod;
use App\FinancialApiBundle\Financial\Currency;

class CreateExchangeFeesForNewCurrencyCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:exchange:create_fees_by_currency')
            ->setDescription('Create all fees for this currency exchange')
            ->addOption(
                'currency',
                null,
                InputOption::VALUE_REQUIRED,
                'Set new currency.',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $em = $this->getContainer()->get('doctrine')->getManager();

        $masterCurrency = strtoupper($input->getOption('currency'));

        $output->writeln('GENERATING FEES FOR '.$masterCurrency.' currency');
        $companies = $em->getRepository('FinancialApiBundle:Group')->findAll();
        $currencies = Currency::$ALL;

        $progress = new ProgressBar($output, count($companies));
        $progress->start();

        foreach ($companies as $company){
            foreach ($currencies as $currency){
                if($currency != $masterCurrency){
                    $exchange_normal = new ServiceFee();
                    $exchange_normal->setVariable(1);
                    $exchange_normal->setFixed(0);
                    $exchange_normal->setCurrency($masterCurrency);
                    $exchange_normal->setGroup($company);
                    $exchange_normal->setServiceName('exchange_'.$currency.'to'.$masterCurrency);

                    $em->persist($exchange_normal);
                    $em->flush();

                    $exchange_gufy = new ServiceFee();
                    $exchange_gufy->setVariable(1);
                    $exchange_gufy->setFixed(0);
                    $exchange_gufy->setCurrency($currency);
                    $exchange_gufy->setGroup($company);
                    $exchange_gufy->setServiceName('exchange_'.$masterCurrency.'to'.$currency);

                    $em->persist($exchange_gufy);
                    $em->flush();

                }

            }
            $progress->advance();
        }

        $progress->finish();

        $output->writeln('FINISHED');

    }

}