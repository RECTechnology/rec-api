<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Entity\Exchange;

class AddBalanceCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:balance:add')
            ->setDescription('Create exchange')
            ->addOption(
                'user',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Set user.',
                null
            )
            ->addOption(
                'amount',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Set amount in cents/satoshis.',
                null
            )
            ->addOption(
                'currency',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Set currency.',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em= $this->getContainer()->get('doctrine')->getManager();

        $user_id=$input->getOption('user');
        $amount=$input->getOption('amount');
        $currency=$input->getOption('currency');

        $currency=strtoupper($currency);

        if(!$user_id) throw new HttpException(400,'Missing value user.');

        //buscamos el user
        $userRepo=$em->getRepository('TelepayFinancialApiBundle:User');
        $user = $userRepo->find($user_id[0]);

        $wallets=$user->getWallets();

        $current_balance = 0;
        $new_balance = 0;
        $find_wallet = 0;
        foreach ( $wallets as $wallet ){
            if ($wallet->getCurrency() == $currency[0] ){
                $current_balance = $wallet->getBalance();
                $wallet->setAvailable( $wallet->getAvailable() + $amount[0] );
                $wallet->setBalance( $wallet->getBalance() + $amount[0] );

                $find_wallet = 1;
                $em->persist($wallet);

                $em->flush();
                $new_balance = $wallet->getBalance();
            }
        }

        if($find_wallet==0) throw new HttpException(400,'Wallet not found');

        $output->writeln('User: '.$user_id[0].' => Previous Balance: '.$current_balance.' / New Balance: '.$new_balance);

    }
}