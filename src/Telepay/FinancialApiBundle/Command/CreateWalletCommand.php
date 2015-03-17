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

class CreateWalletCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:create:wallet')
            ->setDescription('Generate wallets if not exists')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //TODO obtener los users
        $em = $this->getContainer()->get('doctrine')->getManager();
        $usersRepo = $em->getRepository('TelepayFinancialApiBundle:User')->findAll();
        //TODO obtener los wallets
        $walletsRepo = $em->getRepository('TelepayFinancialApiBundle:UserWallet');
        //TODO Obtener las currencies
        $currencies=Currency::$LISTA;
        $contador=0;
        //TODO por cada usuario consultar los wallets que tiene
        foreach ( $usersRepo as $user ){
            foreach ( $currencies as $currency ){
                $wallet=$walletsRepo->findOneBy(array('user' => $user,'currency' => $currency));
                if(!$wallet){
                    //creamos el wallet de esta currency si no existe
                    $user_wallet = new UserWallet();
                    $user_wallet->setBalance(0);
                    $user_wallet->setAvailable(0);
                    $user_wallet->setCurrency($currency);
                    $user_wallet->setUser($user);
                    $em->persist($user_wallet);
                    $contador++;
                }
            }

        }

        $em->flush();

        $text=$contador.' wallets creados';

        $output->writeln($text);
    }
}