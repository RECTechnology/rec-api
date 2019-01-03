<?php

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

class CheckOffersCommand extends ContainerAwareCommand{
    protected function configure()
    {
        $this
            ->setName('rec:check:offers')
            ->setDescription('Check active offers')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $em = $this->getContainer()->get('doctrine')->getManager();

        $now = strtotime("now");
        $output->writeln("INIT");
        $total_offers_actives = 0;
        $list_offers = $em->getRepository('TelepayFinancialApiBundle:Offer')->findAll();
        foreach($list_offers as $offer){
            $output->writeln("OFFER: " . $offer->getId());
            $start = date_timestamp_get($offer->getStart());
            if($start < $now){
                $end = date_timestamp_get($offer->getEnd());
                if($now < $end){
                    $output->writeln("BONA");
                    $total_offers_actives++;
                    $offer->setActive(true);
                    $em->persist($offer);
                    $em->flush();
                }
            }
        }
        $output->writeln("END -> Total active: " . $total_offers_actives);
    }
}