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

        $service_cname = array('fac_pay','btc_pay');

        //$em= $this->getContainer()->get('doctrine')->getManager();
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository('TelepayFinancialApiBundle:User');

        foreach($service_cname as $service){
            $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('service')->equals($service)
                ->field('status')->in(array('created','received'))
                ->getQuery();

            $resArray = [];
            foreach($qb->toArray() as $transaction){

                $data = $transaction->getDataIn();

                if(isset($data['expires_in'])){

                    $resArray [] = $transaction;
                    $previous_status = $transaction->getStatus();

                    $checked_transaction = $this->check($transaction);

                    if($previous_status != $checked_transaction->getStatus()){
                        $checked_transaction = $this->getContainer()->get('notificator')->notificate($checked_transaction);
                        $checked_transaction->setUpdated(new \MongoDate());

                    }

                    $dm->persist($checked_transaction);
                    $dm->flush();

                    if($checked_transaction->getStatus()=='success'){
                        //hacemos el reparto
                        //primero al user
                        $id = $checked_transaction->getUser();

                        $transaction_id = $checked_transaction->getId();

                        $user = $repo->find($id);

                        $wallets = $user->getWallets();
                        $service_currency = $checked_transaction->getCurrency();
                        $current_wallet = null;

                        foreach ( $wallets as $wallet){
                            if ($wallet->getCurrency() == $service_currency){
                                $current_wallet = $wallet;
                            }
                        }

                        $amount = $data['amount'];

                        if(!$user->hasRole('ROLE_SUPER_ADMIN')){
                            $group = $user->getGroups()[0];

                            $fixed_fee = $checked_transaction->getFixedFee();
                            $variable_fee = $checked_transaction->getVariableFee();
                            $total_fee = $fixed_fee + $variable_fee;
                            $total = $amount - $total_fee;

                            $current_wallet->setAvailable($current_wallet->getAvailable()+$total);
                            $current_wallet->setBalance($current_wallet->getBalance()+$total);

                            $em->persist($current_wallet);
                            $em->flush();

                            if($total_fee != 0){
                                // restar las comisiones
                                $feeTransaction=new Transaction();
                                $feeTransaction->setStatus('success');
                                $feeTransaction->setScale($checked_transaction->getScale());
                                $feeTransaction->setAmount($total_fee);
                                $feeTransaction->setUser($user->getId());
                                $feeTransaction->setCreated(new \MongoDate());
                                $feeTransaction->setUpdated(new \MongoDate());
                                $feeTransaction->setIp($checked_transaction->getIp());
                                $feeTransaction->setFixedFee($fixed_fee);
                                $feeTransaction->setVariableFee($variable_fee);
                                $feeTransaction->setVersion($checked_transaction->getVersion());
                                $feeTransaction->setDataIn(array(
                                    'previous_transaction'  =>  $checked_transaction->getId(),
                                    'amount'    =>  -$total_fee
                                ));
                                $feeTransaction->setDebugData(array(
                                    'previous_balance'  =>  $current_wallet->getBalance(),
                                    'previous_transaction'  =>  $checked_transaction->getId()
                                ));
                                $feeTransaction->setTotal(-$total_fee);
                                $feeTransaction->setCurrency($checked_transaction->getCurrency());
                                $feeTransaction->setService($service);

                                $dm->persist($feeTransaction);
                                $dm->flush();

                                $em->persist($current_wallet);
                                $em->flush();

                                $creator = $group->getCreator();

                                //luego a la ruleta de admins
                                $dealer = $this->getContainer()->get('net.telepay.commons.fee_deal');
                                $dealer->deal($creator,$amount,$service,$service_currency,$total_fee,$transaction_id,$checked_transaction->getVersion());
                            }

                        }else{
                            $current_wallet->setAvailable($current_wallet->getAvailable() + $amount);
                            $current_wallet->setBalance($current_wallet->getBalance() + $amount);

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

        if($transaction->getStatus() === 'success' || $transaction->getStatus() === 'expired'){
            return $transaction;
        }

        $address = $currentData['address'];
        $amount = $currentData['amount'];
        if($transaction->getCurrency() === Currency::$BTC)
            $providerName = 'net.telepay.provider.btc';
        else
            $providerName = 'net.telepay.provider.fac';

        $cryptoProvider = $this->getContainer()->get($providerName);

        $allReceived = $cryptoProvider->listreceivedbyaddress(0, true);

        if($amount <= 100)
            $margin = 0;
        else
            $margin = 100;

        $allowed_amount = $amount - $margin;
        foreach($allReceived as $cryptoData){
            if($cryptoData['address'] === $address){
                $currentData['received'] = doubleval($cryptoData['amount'])*1e8; //doubleval($cryptoData['amount'])*1e8;
                if(doubleval($cryptoData['amount'])*1e8 >= $allowed_amount){
                    $currentData['confirmations'] = $cryptoData['confirmations'];
                    if($currentData['confirmations'] >= $currentData['min_confirmations']){
                        $transaction->setStatus("success");
                    }else{
                        if($transaction->getStatus() != 'received'){
                            $transaction->setStatus("received");
                        }
                    }

                    $transaction->setData($currentData);
                    $transaction->setDataOut($currentData);

                    return $transaction;
                }


            }

        }

        if($transaction->getStatus() === 'created' && $this->hasExpired($transaction)){
            $transaction->setStatus('expired');
            $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
        }

        return $transaction;
    }

    private function hasExpired($transaction){

        return $transaction->getCreated()->getTimestamp()+$transaction->getData()['expires_in'] < time();

    }
}