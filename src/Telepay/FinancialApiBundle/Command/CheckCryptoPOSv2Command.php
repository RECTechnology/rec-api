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

class CheckCryptoPOSv2Command extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:crypto_pos:check-V2')
            ->setDescription('Check crypto POS-btc transactions')
            ->addOption(
                'transaction-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Define transaction id.',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $trans_id=$input->getOption('transaction-id');
        $service_cname = 'POS-BTC';

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $userRepo = $em->getRepository('TelepayFinancialApiBundle:User');

        if(isset($trans_id)){
            $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('type')->equals($service_cname)
                ->field('id')->equals($trans_id)
                ->field('status')->in(array('created','received'))
                ->getQuery();
        }
        else{
            $mongoDateBefore1MinuteAgo = new \MongoDate(strtotime( date('Y-m-d H:i:s',\time() - 1 * 50) ) );
            $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('type')->equals($service_cname)
                ->field('status')->in(array('created','received'))
                ->field('last_check')->lte($mongoDateBefore1MinuteAgo)
                ->getQuery();
        }

        $resArray = [];

        foreach($qb->toArray() as $transaction){

            $resArray [] = $transaction;
            $previous_status = $transaction->getStatus();

            $transaction = $this->check($transaction);

            if($previous_status != $transaction->getStatus()){
                $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                $transaction->setUpdated(new \MongoDate());

            }

            $transaction->setLastCheck(new \DateTime());
            $dm->persist($transaction);
            $dm->flush();

            if($transaction->getStatus()=='success'){
                //hacemos el reparto
                //primero al user
                $id = $transaction->getUser();

                $transaction_id = $transaction->getId();

                $user = $userRepo->find($id);

                $wallets = $user->getWallets();
                $currency_out = $transaction->getCurrency();
                $current_wallet = null;

                foreach ( $wallets as $wallet){
                    if ($wallet->getCurrency() == $currency_out){
                        $current_wallet = $wallet;
                    }
                }

                $amount = $transaction->getAmount();

                if(!$user->hasRole('ROLE_SUPER_ADMIN')){
                    $group = $user->getGroups()[0];

                    $fixed_fee = $transaction->getFixedFee();
                    $variable_fee = $transaction->getVariableFee();
                    $total_fee = $fixed_fee + $variable_fee;
                    $total = $amount - $total_fee;

                    $current_wallet->setAvailable($current_wallet->getAvailable() + $total);
                    $current_wallet->setBalance($current_wallet->getBalance() + $total);

                    $em->persist($current_wallet);
                    $em->flush();

                    if($total_fee != 0){
                        // restar las comisiones
                        $feeTransaction=new Transaction();
                        $feeTransaction->setStatus('success');
                        $feeTransaction->setScale($transaction->getScale());
                        $feeTransaction->setAmount($total_fee);
                        $feeTransaction->setUser($user->getId());
                        $feeTransaction->setCreated(new \MongoDate());
                        $feeTransaction->setUpdated(new \MongoDate());
                        $feeTransaction->setIp($transaction->getIp());
                        $feeTransaction->setFixedFee($fixed_fee);
                        $feeTransaction->setVariableFee($variable_fee);
                        $feeTransaction->setVersion($transaction->getVersion());
                        $feeTransaction->setDataIn(array(
                            'previous_transaction'  =>  $transaction->getId(),
                            'amount'    =>  -$total_fee
                        ));
                        $feeTransaction->setDebugData(array(
                            'previous_balance'  =>  $current_wallet->getBalance(),
                            'previous_transaction'  =>  $transaction->getId()
                        ));
                        $feeTransaction->setTotal(-$total_fee);
                        $feeTransaction->setCurrency($transaction->getCurrency());
                        $feeTransaction->setService($service_cname);
                        $feeTransaction->setType('fee');

                        $dm->persist($feeTransaction);
                        $dm->flush();

                        $em->persist($current_wallet);
                        $em->flush();

                        $creator = $group->getCreator();

                        //luego a la ruleta de admins
                        $dealer = $this->getContainer()->get('net.telepay.commons.fee_deal');
                        $dealer->deal(
                            $creator,
                            $amount,
                            'POS',
                            'BTC',
                            $currency_out ,
                            $total_fee,
                            $transaction_id,
                            $transaction->getVersion());
                    }

                }else{
                    $current_wallet->setAvailable($current_wallet->getAvailable() + $amount);
                    $current_wallet->setBalance($current_wallet->getBalance() + $amount);

                    $em->persist($current_wallet);
                    $em->flush();
                }

            }

        }

        if(isset($trans_id)){
            $output->writeln($transaction->getId());
        }
        else{
            $output->writeln($service_cname.' transactions checked');
        }

        $dm->flush();

    }

    public function check(Transaction $transaction){

        $paymentInfo = $transaction->getPayInInfo();

        if($transaction->getStatus() === 'success' || $transaction->getStatus() === 'expired'){
            return $transaction;
        }

        $address = $paymentInfo['address'];
        $amount = min(intval($paymentInfo['amount']), intval($paymentInfo['previous_amount']));

        if($paymentInfo['currency'] === Currency::$BTC){
            $providerName = 'net.telepay.provider.btc';
            if($amount <= 100)
                $margin = 0;
            else
                $margin = 100;
        }else{
            $providerName = 'net.telepay.provider.fac';
            if($amount <= 10000)
                $margin = 0;
            else
                $margin = 10000;
        }

        $cryptoProvider = $this->getContainer()->get($providerName);

        $allReceived = $cryptoProvider->listreceivedbyaddress(0, true);

        $allowed_amount = $amount - $margin;
        foreach($allReceived as $cryptoData){
            if($cryptoData['address'] === $address){
                $paymentInfo['received'] = doubleval($cryptoData['amount'])*1e8; //doubleval($cryptoData['amount'])*1e8;
                if(doubleval($cryptoData['amount'])*1e8 >= $allowed_amount){
                    $paymentInfo['confirmations'] = $cryptoData['confirmations'];
                    if($paymentInfo['confirmations'] >= $paymentInfo['min_confirmations']){
                        $transaction->setStatus("success");
                    }else{
                        if($transaction->getStatus() != 'received'){
                            $transaction->setStatus("received");
                        }
                    }

                    $transaction->setPayInInfo($paymentInfo);

                    return $transaction;
                }

            }

        }

        if($transaction->getStatus() === 'created' && $this->hasExpired($transaction)){
            $transaction->setStatus('expired');
            $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
        }
        else{
            $lastPriceAt = $transaction->getLastPriceAt();
            $lastPriceAt = $lastPriceAt->getTimestamp();
            $actual = new \MongoDate();
            $actual = $actual->sec;
            //300 seconds => 5 minutes
            $need_new = $lastPriceAt + 300;

            if(strtoupper($paymentInfo['currency_in']) != $paymentInfo['currency'] && $need_new < $actual ){
                $em = $this->getContainer()->get('doctrine')->getManager();
                $exchange = $em->getRepository('TelepayFinancialApiBundle:Exchange')->findOneBy(
                    array(
                        'src'   =>  $paymentInfo['currency_in'],
                        'dst'   =>  $paymentInfo['currency']
                    ),
                    array('id'  =>  'DESC')
                );
                $paymentInfo['previous_amount'] = $paymentInfo['amount'];
                $paymentInfo['amount'] = round($paymentInfo['received_amount']*$exchange->getPrice(),0);
                $transaction->setPayInInfo($paymentInfo);
                $transaction->setLastPriceAt(new \DateTime());
            }
        }

        return $transaction;
    }

    private function hasExpired($transaction){

        $pos_id = $transaction->getPosId();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $posRepo = $em->getRepository('TelepayFinancialApiBundle:POS')->findBy(array(
            'pos_id' =>  $pos_id
        ));

        $expired = false;

        if(!$posRepo){
            $expired = true;
        }else{
            $expires_in = $posRepo[0]->getExpiresIn();
            $created = $transaction->getCreated();
            $created = $created->getTimestamp();

            $actual = new \MongoDate();
            $actual = $actual->sec;

            $expires = $created + $expires_in;

            if($expires < $actual){
                $expired = true;
            }
        }

        return $expired;

    }
}