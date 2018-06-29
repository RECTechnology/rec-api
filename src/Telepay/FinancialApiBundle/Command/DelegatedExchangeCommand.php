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

class DelegatedExchangeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:delegated:exchange')
            ->setDescription('Delegated exchange')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $em = $this->getContainer()->get('doctrine')->getManager();
        $providerName = 'net.telepay.in.lemonway.v1';
        $moneyProvider = $this->getContainer()->get($providerName);

        $new_account = $moneyProvider->RegisterWallet('ivan002','ivan002@robotunion.org', 'Ivan', 'test2', 'M');
        $text='register=>' . json_encode($new_account, JSON_PRETTY_PRINT);

        //$new_payment = $moneyProvider->CreditCardPayment('MKP','10.50');
        //$text='payment=>' . json_encode($new_payment, JSON_PRETTY_PRINT);

        $payment_P2P_data = array(
            'from' => 'ivan001',
            'to' => 'ivan002',
            'amount' => '50'
        );
        //$new_p2p_payment = $moneyProvider->send($payment_P2P_data);
        //$text='payment=>' . json_encode($new_p2p_payment, JSON_PRETTY_PRINT);

        $output->writeln($text);
    }
}