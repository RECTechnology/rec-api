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
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

class MigratePOSCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:migrate:POS')
            ->setDescription('Migrate POS')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Migrating POS');

        $em = $this->getContainer()->get('doctrine')->getManager();
        $posRepo = $em->getRepository("TelepayFinancialApiBundle:POS");
        $poses = $posRepo->findAll();

        $output->writeln('Found '.count($poses).' POS');

        foreach ($poses as $pos) {
            if($pos->getCurrency() == 'EUR'){
                $type = 'PNP';
            }else{
                $type = $pos->getCurrency();
            }
            $pos->setType($type);
            $em->persist($pos);
            $em->flush();

        }

        $output->writeln('All done');
    }


}