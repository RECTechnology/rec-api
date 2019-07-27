<?php
namespace App\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\DependencyInjection\App\Commons\FeeDeal;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Exchange;
use App\FinancialApiBundle\Financial\Currency;

class CheckCryptoPOSv2Command extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:crypto_pos:check-V2')
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
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $groupRepo = $em->getRepository('FinancialApiBundle:Group');

        if(isset($trans_id)){
            $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
                ->field('type')->in(array('POS-BTC', 'POS-FAC'))
                ->field('id')->equals($trans_id)
                ->field('status')->in(array('created','received'))
                ->getQuery();
        }
        else{
            $mongoDateBefore1MinuteAgo = new \MongoDate(strtotime( date('Y-m-d H:i:s',\time() - 1 * 50) ) );
            $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction')
                ->field('type')->in(array('POS-BTC', 'POS-FAC'))
                ->field('status')->in(array('created','received'))
                ->field('last_check')->lte($mongoDateBefore1MinuteAgo)
                ->getQuery();
        }

        $resArray = [];

        foreach($qb->toArray() as $transaction){

            $resArray [] = $transaction;
            $previous_status = $transaction->getStatus();
            $service_cname = $transaction->getType();

            $transaction = $this->check($transaction);

            if($previous_status != $transaction->getStatus()){
                $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                $transaction->setUpdated(new \MongoDate());

            }

            $transaction->setLastCheck(new \DateTime());
            $dm->persist($transaction);
            $dm->flush();

            if($transaction->getStatus()== Transaction::$STATUS_SUCCESS){
                $output->writeln('CRYPTO_POS_V2 status success');
                //hacemos el reparto, primero al user
                $id = $transaction->getGroup();
                $transaction_id = $transaction->getId();
                $group = $groupRepo->find($id);

                $wallet = $group->getWallet($transaction->getCurrency());
                $currency_out = $transaction->getCurrency();

                $amount = $transaction->getAmount();

                if(!$group->hasRole('ROLE_SUPER_ADMIN')){
                    $fixed_fee = $transaction->getFixedFee();
                    $variable_fee = $transaction->getVariableFee();
                    $total_fee = $fixed_fee + $variable_fee;
                    $total = $amount - $total_fee;

                    $wallet->setAvailable($wallet->getAvailable() + $total);
                    $wallet->setBalance($wallet->getBalance() + $total);

                    //TODO balancer
                    $balancer = $this->getContainer()->get('net.app.commons.balance_manipulator');
                    //rest balance
                    $balancer->addBalance($group,$amount, $transaction, "crypto pos commmand");

                    $em->persist($wallet);
                    $em->flush();

                    if($total_fee != 0){
                        // restar las comisiones
                        $output->writeln('CRYPTO_POS_V2 generating fees');
                        $feeTransaction = new Transaction();
                        $feeTransaction->setStatus('success');
                        $feeTransaction->setScale($transaction->getScale());
                        $feeTransaction->setAmount($total_fee);
                        $feeTransaction->setGroup($group->getId());
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
                            'previous_balance'  =>  $wallet->getBalance(),
                            'previous_transaction'  =>  $transaction->getId()
                        ));
                        $feeTransaction->setTotal(-$total_fee);
                        $feeTransaction->setCurrency($transaction->getCurrency());
                        $feeTransaction->setService($service_cname);
                        $feeTransaction->setType('fee');

                        $dm->persist($feeTransaction);
                        $dm->flush();

                        $balancer->addBalance($group,-$total_fee, $feeTransaction, "crypto pos command fee");

                        $em->persist($wallet);
                        $em->flush();

                        $creator = $group->getCreator();

                        //luego a la ruleta de admins
                        $dealer = $this->getContainer()->get('net.app.commons.fee_deal');
                        if($service_cname == 'POS-BTC') {
                            $output->writeln('CRYPTO_POS_V2 POS BTC');
                            $dealer->deal(
                                $creator,
                                $amount,
                                'POS',
                                'BTC',
                                $currency_out,
                                $total_fee,
                                $transaction_id,
                                $transaction->getVersion());
                        }
                        elseif($service_cname == 'POS-FAC'){
                            $output->writeln('CRYPTO_POS_V2 CRYPTO POS FAC');
                            $dealer->deal(
                                $creator,
                                $amount,
                                'POS',
                                'FAC',
                                $currency_out,
                                $total_fee,
                                $transaction_id,
                                $transaction->getVersion());
                        }
                    }

                    //check for exchange
                    $paymentInfo = $transaction->getPayInInfo();
                    $tpvRepo = $em->getRepository('FinancialApiBundle:POS')->findOneBy(array(
                        'pos_id'    =>  $transaction->getPosId()
                    ));
                    $posType = $tpvRepo->getType();
                    $pos_config = $this->getContainer()->get('net.app.config.pos_'.strtolower($posType))->getInfo();

                    if($paymentInfo['currency_out'] != $pos_config['default_currency']){
                        //create exchange fee and dealer
                        $output->writeln('CRYPTO_POS_V2 doing exchange');
                        $service = 'exchange'.'_'.strtoupper($pos_config['default_currency']).'to'.strtoupper($transaction->getCurrency());
                        $fees = $group->getCommissions();

                        $exchange_fixed_fee = 0;
                        $exchange_variable_fee = 0;

                        foreach($fees as $fee){
                            if($fee->getServiceName() == $service){
                                $exchange_fixed_fee = $fee->getFixed();
                                $exchange_variable_fee = round((($fee->getVariable()/100) * $transaction->getAmount()), 0);
                            }
                        }
                        $output->writeln('CRYPTO_POS_V2 GETTING EXCHANGER');
                        $exchanger = $this->getContainer()->get('net.app.commons.exchange_manipulator');
                        $price = $exchanger->getPrice($pos_config['default_currency'], $transaction->getCurrency());

                        //create fake transaction to generate exchange fees correctly
                        $fakeTrans = new Transaction();
                        $fakeTrans->setStatus(Transaction::$STATUS_SUCCESS);
                        $fakeTrans->setIp('127.0.0.1');
                        $fakeTrans->setVersion(1);
                        $fakeTrans->setAmount($transaction->getAmount());
                        $fakeTrans->setCurrency($transaction->getCurrency());
                        $fakeTrans->setFixedFee($exchange_fixed_fee);
                        $fakeTrans->setVariableFee($exchange_variable_fee);
                        $fakeTrans->setTotal($transaction->getAmount());
                        $fakeTrans->setService($service);
                        $fakeTrans->setType(Transaction::$TYPE_IN);
                        $fakeTrans->setMethod($service);
                        $fakeTrans->setUser($transaction->getUser());
                        $fakeTrans->setGroup($transaction->getGroup());
                        $fakeTrans->setScale($transaction->getScale());
                        $fakeTrans->setPayInInfo(array(
                            'amount'    =>  $transaction->getAmount(),
                            'currency'  =>  $transaction->getCurrency(),
                            'scale'     =>  $transaction->getScale(),
                            'concept'   =>  'Exchange '.$pos_config['default_currency'].' to '.$transaction->getCurrency(),
                            'price'     =>  $price,
                        ));

                        $exchangeWallet = $group->getWallet($fakeTrans->getCurrency());

                        $dealer = $this->getContainer()->get('net.app.commons.fee_deal');
                        $output->writeln('CRYPTO_POS_V2 creating fees');
                        try{
                            $dealer->createFees2($fakeTrans, $exchangeWallet);
                        }catch (HttpException $e){
                            $output->writeln('CRYPTO_POS_V2 cerate fees failed '.$e->getMessage());
                        }

                    }

                }else{
                    $wallet->setAvailable($wallet->getAvailable() + $amount);
                    $wallet->setBalance($wallet->getBalance() + $amount);

                    $em->persist($wallet);
                    $em->flush();
                }

            }

        }

        if(isset($trans_id)){
            if(count($qb)==0){
                $output->writeln($trans_id);
            }
            else {
                $output->writeln($transaction->getId());
            }
        }
        else{
            $output->writeln('Crypto POS v2 transactions checked');
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
            $providerName = 'net.app.provider.btc';
            if($amount <= 100)
                $margin = 0;
            else
                $margin = 100;
        }else{
            $providerName = 'net.app.provider.fac';
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
                $paymentInfo['received'] = doubleval($cryptoData['amount'])*1e8;
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
                $exchange = $em->getRepository('FinancialApiBundle:Exchange')->findOneBy(
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
        $posRepo = $em->getRepository('FinancialApiBundle:POS')->findBy(array(
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