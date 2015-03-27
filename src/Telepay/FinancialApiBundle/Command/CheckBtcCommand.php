<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\FeeDeal;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Exchange;

class CheckBtcCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:btc:check')
            ->setDescription('Check btc transactions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $service_cname='btc_pay';

        //$em= $this->getContainer()->get('doctrine')->getManager();
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repo=$em->getRepository('TelepayFinancialApiBundle:User');

        $qb=$dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('service')->equals($service_cname)
            ->field('status')->in(array('created','received'))
            ->getQuery();


        $resArray = [];
        foreach($qb->toArray() as $res){

            $data=$res->getDataIn();
            if(isset($data['expires_in'])){
                $resArray []= $res;
                $check=$this->check($res);
                $dm->flush();
                if($check->getStatus()=='success'){
                    //hacemos el reparto
                    //primero al user
                    $id=$check->getUser();

                    $user=$repo->find($id);

                    $wallets=$user->getWallets();
                    $service_currency = $check->getCurrency();
                    $current_wallet=null;

                    foreach ( $wallets as $wallet){
                        if ($wallet->getCurrency()==$service_currency){
                            $current_wallet=$wallet;
                        }
                    }

                    $group=$user->getGroups()[0];

                    $amount=$data['amount'];

                    $fixed_fee=$check->getFixedFee();
                    $variable_fee=$check->getVariableFee()*$amount;
                    $total_fee=$fixed_fee+$variable_fee;
                    $total=$amount-$total_fee;

                    $current_wallet->setAvailable($current_wallet->getAvailable()+$total);
                    $current_wallet->setBalance($current_wallet->getBalance()+$total);

                    $em->persist($current_wallet);
                    $em->flush();

                    $creator=$group->getCreator();

                    //luego a la ruleta de admins
                    $dealer=new FeeDeal();
                    $dealer->deal($creator,$amount,$service_cname,$service_currency,$total_fee);
                }
            }



        }

        $dm->flush();

        $output->writeln('Btc transactions checked');
    }

    public function check(Transaction $transaction){

        $currentData = $transaction->getData();

        if($transaction->getStatus() === 'created' && $this->hasExpired($transaction))
            $transaction->setStatus('expired');

        if($transaction->getStatus() === 'success' || $transaction->getStatus() === 'expired')
            return $transaction;

        $address = $currentData['address'];
        $amount = $currentData['amount'];
        $allReceived = $this->getContainer()->get('net.telepay.provider.fac')->listreceivedbyaddress(0, true);
        foreach($allReceived as $cryptoData){
            if($cryptoData['address'] == $address && doubleval($cryptoData['amount'])*1e8 >= $amount){
                $currentData['received'] = doubleval($cryptoData['amount'])*1e8;
                $currentData['confirmations'] = $cryptoData['confirmations'];
                if($currentData['confirmations'] >= $currentData['min_confirmations']){
                    $transaction->setStatus("success");
                }else{
                    $transaction->setStatus("received");
                }
                $transaction->setData($currentData);
                $transaction->setDataOut($currentData);
                return $transaction;
            }
        }
        return $transaction;
    }
    private function hasExpired($transaction){
        return $transaction->getTimeIn()->getTimestamp()+$transaction->getData()['expires_in'] < time();
    }
}