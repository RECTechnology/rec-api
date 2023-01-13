<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/15/14
 * Time: 1:27 PM
 */

namespace App\FinancialApiBundle\Command;


use App\FinancialApiBundle\Entity\Group;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Financial\Currency;

class CreateWalletCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:create:wallet')
            ->setDescription('Generate wallets if not exists')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $em = $this->getContainer()->get('doctrine')->getManager();
        $groupRepo = $em->getRepository(Group::class)->findAll();
        $walletsRepo = $em->getRepository(UserWallet::class);
        $container = $this->getContainer();
        $crypto_currency = $container->getParameter('crypto_currency');
        $fiat_currency = $container->getParameter('fiat_currency');
        $currencies = [$fiat_currency, $crypto_currency];
        $contador=0;
        foreach ( $groupRepo as $group ){
            foreach ( $currencies as $currency ){
                $wallet = $walletsRepo->findOneBy(array('group' => $group,'currency' => $currency));
                if(!$wallet){
                    //creamos el wallet de esta currency si no existe
                    $user_wallet = new UserWallet();
                    $user_wallet->setBalance(0);
                    $user_wallet->setAvailable(0);
                    $user_wallet->setCurrency($currency);
                    $user_wallet->setGroup($group);
                    $em->persist($user_wallet);
                    $em->flush();
                    $contador++;
                    $output->writeln($group->getId() . " created " . $currency);
                }
            }
        }
        $em->flush();
        $text = $contador.' wallets creados';
        $output->writeln($text);

        $output->writeln('Create addresses if not exists');
        $incomplete_accounts = $em->getRespository(Group::class)->findBy(array('rec_address' => null));

        $methodDriver = $this->getContainer()->get('net.app.in.'.strtolower($crypto_currency).'.v1');
        $output->writeln('Found '.count($incomplete_accounts). ' without address');
        foreach ($incomplete_accounts as $incomplete_account){
            $paymentInfo = $methodDriver->getPayInInfo($incomplete_account->getId(), 0);
            $incomplete_account->setRecAddress($paymentInfo['address']);

        }
        $em->flush();
    }
}