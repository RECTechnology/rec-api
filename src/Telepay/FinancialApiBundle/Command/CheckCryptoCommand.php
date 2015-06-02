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
use Telepay\FinancialApiBundle\Financial\Currency;

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
                            $variable_fee=$check->getVariableFee();
                            $total_fee=$fixed_fee+$variable_fee;
                            $total=$amount-$total_fee;

                            $current_wallet->setAvailable($current_wallet->getAvailable()+$total);
                            $current_wallet->setBalance($current_wallet->getBalance()+$total);

                            if($total_fee != 0){
                                // restar las comisiones
                                $feeTransaction=new Transaction();
                                $feeTransaction->setStatus('success');
                                $feeTransaction->setScale($check->getScale());
                                $feeTransaction->setAmount($total_fee);
                                $feeTransaction->setUser($user->getId());
                                $feeTransaction->setCreated(new \MongoDate());
                                $feeTransaction->setTimeOut(new \MongoDate());
                                $feeTransaction->setTimeIn(new \MongoDate());
                                $feeTransaction->setUpdated(new \MongoDate());
                                $feeTransaction->setIp($check->getIp());
                                $feeTransaction->setFixedFee($fixed_fee);
                                $feeTransaction->setVariableFee($variable_fee);
                                $feeTransaction->setVersion($check->getVersion());
                                $feeTransaction->setDataIn(array(
                                    'previous_transaction'  =>  $check->getId(),
                                    'amount'    =>  -$total_fee
                                ));
                                $feeTransaction->setDebugData(array(
                                    'previous_balance'  =>  $current_wallet->getBalance(),
                                    'previous_transaction'  =>  $check->getId()
                                ));
                                $feeTransaction->setTotal(-$total_fee);
                                $feeTransaction->setCurrency($check->getCurrency());
                                $feeTransaction->setService($service);

                                $dm->persist($feeTransaction);
                                $dm->flush();

                                $em->persist($current_wallet);
                                $em->flush();

                                $creator=$group->getCreator();

                                //luego a la ruleta de admins
                                $dealer=$this->getContainer()->get('net.telepay.commons.fee_deal');
                                $dealer->deal($creator,$amount,$service,$service_currency,$total_fee,$transaction_id,$check->getVersion());
                            }

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

        if($transaction->getStatus() === 'success' || $transaction->getStatus() === 'expired')
            return $transaction;

        $address = $currentData['address'];
        $amount = $currentData['amount'];
        if($transaction->getCurrency() === Currency::$BTC)
            $providerName = 'net.telepay.provider.btc';
        else
            $providerName = 'net.telepay.provider.fac';

        $cryptoProvider = $this->getContainer()->get($providerName);

        $allReceived = $cryptoProvider->listreceivedbyaddress(0, true);

        if($amount<=100)
            $margin = 0;
        else
            $margin = 100;

        $allowed_amount = $amount - $margin;

        foreach($allReceived as $cryptoData){
            if($cryptoData['address'] === $address){
                $currentData['received'] = doubleval($cryptoData['amount'])*1e8;; //doubleval($cryptoData['amount'])*1e8;
                if(doubleval($cryptoData['amount'])*1e8 >= $allowed_amount){
                    $currentData['confirmations'] = $cryptoData['confirmations'];
                    if($currentData['confirmations'] >= $currentData['min_confirmations'])
                        $transaction->setStatus("success");
                    else
                        $transaction->setStatus("received");

                }
                $transaction->setUpdated(new \MongoDate());
                $transaction->setData($currentData);
                $transaction->setDataOut($currentData);
                return $transaction;
            }

        }

        if($transaction->getStatus() === 'created' && $this->hasExpired($transaction))
            $transaction->setStatus('expired');

        return $transaction;
    }
    private function hasExpired($transaction){
        return $transaction->getTimeIn()->getTimestamp()+$transaction->getData()['expires_in'] < time();
    }
}