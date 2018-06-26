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

class UpdateAddressCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:update:address')
            ->setDescription('Update rec address')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $em = $this->getContainer()->get('doctrine')->getManager();
        $groupList = $em->getRepository('TelepayFinancialApiBundle:Group')->findAll();
        $contador=0;
        foreach ( $groupList as $account ){
            $address = $account->getRecAddress();
            $providerName = 'net.telepay.in.rec.v1';
            $cryptoProvider = $this->getContainer()->get($providerName);
            if(!$cryptoProvider->validateaddress($address)){
                $new_address = $cryptoProvider->getnewaddress();
                $account->setRecAddress($new_address);
                $em->persist($account);
                $contador++;
            }
        }
        $em->flush();
        $text=$contador.' wallets creados';
        $output->writeln($text);
    }
}