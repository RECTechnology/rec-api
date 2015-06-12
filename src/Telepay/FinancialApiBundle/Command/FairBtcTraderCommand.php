<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WebSocket\Client;
use WebSocket\Exception;

class FairBtcTraderCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:trader:fair-btc')
            ->setDescription('Trades with FairCoin -> Bitcoin')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fairBtcTrader = $this->getContainer()->get('net.telepay.trader.FACxBTC');

        $bittrexBtcWallet = $this->getContainer()->get('net.telepay.wallet.bittrex.btc');
        $krakenBtcWallet = $this->getContainer()->get('net.telepay.wallet.kraken.btc');

        //$fairBtcTrader->sell(2368.53045801);
        //$output->writeln(print_r($fairBtcTrader->withdraw(1.26707722, '13bkZYQgC46W4QHK3snE3NwN5RnDb1Jjsc'), true));

        //$output->writeln(print_r($bittrexBtcWallet->getAddress(), true));
        //$output->writeln(print_r($bittrexBtcWallet->getAvailable(), true));

        //$output->writeln(print_r($krakenBtcWallet->getAvailable(), true));
        //$output->writeln(print_r($krakenBtcWallet->getAddress(), true));

        //$client = new Client("wss://echo.websocket.org");
        /*
        $client = new Client("wss://ws.blockchain.info/inv");
        $i=0;
        while($i==0 || $client->isConnected()) {
            $client->send(json_encode(array(
                "op" => "addr_sub",
                "addr" => "14DuJ7Q9AG2JT27e8MMur8cv61PshaxRvU"
            )));
            $i++;
            sleep(1);
            try {
                echo $client->receive() . "\n";
            }catch (Exception $e){
                echo "No data\n";
            }
            $client->send(json_encode(array(
                "op" => "ping_tx"
            )));
            $i++;
            sleep(1);
            try {
                echo $client->receive() . "\n";
            }catch (Exception $e){
                echo "No data ping\n";
            }
        }
        */
        $output->writeln("Sold");


    }
}