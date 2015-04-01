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

class CheckCryptoCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:crypto:check')
            ->setDescription('Check crypto transactions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $service_cname=array('fac_pay','btc_pay');

        //$em= $this->getContainer()->get('doctrine')->getManager();
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repo=$em->getRepository('TelepayFinancialApiBundle:User');

        foreach($service_cname as $service){
            $qb=$dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('service')->equals($service)
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

                        $transaction_id=$check->getId();

                        $user=$repo->find($id);

                        $wallets=$user->getWallets();
                        $service_currency = $check->getCurrency();
                        $current_wallet=null;


                        foreach ( $wallets as $wallet){
                            if ($wallet->getCurrency()==$service_currency){
                                $current_wallet=$wallet;
                            }
                        }

                        $amount=$data['amount'];

                        if(!$user->hasRole('ROLE_SUPER_ADMIN')){
                            $group=$user->getGroups()[0];

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
                            $dealer=$this->getContainer()->get('net.telepay.commons.fee_deal');
                            $dealer->deal($creator,$amount,$service,$service_currency,$total_fee,$transaction_id);
                        }else{
                            $current_wallet->setAvailable($current_wallet->getAvailable()+$amount);
                            $current_wallet->setBalance($current_wallet->getBalance()+$amount);

                            $em->persist($current_wallet);
                            $em->flush();
                        }

                    }
                }

            }

            $output->writeln($service.' transactions checked');
        }

        $dm->flush();

        $output->writeln('Crypto transactions checked');
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