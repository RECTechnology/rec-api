<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/15/14
 * Time: 1:27 PM
 */

namespace Telepay\FinancialApiBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Financial\Currency;

class MigratePOSFeesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:migrate:POS-fees')
            ->setDescription('Migrate POS fees')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Getting Groups');

        $em = $this->getContainer()->get('doctrine')->getManager();
        $groupRepo = $em->getRepository("TelepayFinancialApiBundle:Group");
        $groups = $groupRepo->findAll();

        $output->writeln('Found '.count($groups).' Groups');

        $posTypes = array(
            'POS-BTC',
            'POS-PNP'
        );

        $currencies = Currency::$ALL;

        $feeCount = 0;
        foreach ($groups as $group) {

            foreach($posTypes as $posType){
                foreach($currencies as $currency){
                    $fee = $em->getRepository("TelepayFinancialApiBundle:ServiceFee")->findOneBy(array(
                        'group'         =>  $group,
                        'service_name' =>  $posType,
                        'currency'      =>  $currency
                    ));

                    if(!$fee){
                        //creamos la fee
                        $fee = new ServiceFee();
                        $fee->setCurrency($currency);
                        $fee->setGroup($group);
                        $fee->setFixed(0);
                        $fee->setVariable(0);
                        $fee->setServiceName($posType);

                        $em->persist($fee);
                        $em->flush();

                        $feeCount++;
                    }
                }
            }




        }

        $output->writeln($feeCount.' created fees');
        $output->writeln('All done');
    }


}