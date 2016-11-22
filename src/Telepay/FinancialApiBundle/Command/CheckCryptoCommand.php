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

class CheckCryptoCommand extends SyncronizedContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:crypto:check')
            ->setDescription('Check crypto transactions')
        ;
    }

    protected function executeSyncronized(InputInterface $input, OutputInterface $output){
        $n = 0;
        $exec_n_times = 1000;
        while($n<$exec_n_times) {
            $method_cname = array('fac', 'btc');
            $type = 'in';

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $em = $this->getContainer()->get('doctrine')->getManager();
            $repoGroup = $em->getRepository('TelepayFinancialApiBundle:Group');
            $output->writeln('CHECK CRYPTO');
            foreach ($method_cname as $method) {
                $output->writeln($method . ' INIT');

                $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                    ->field('method')->equals($method)
                    ->field('status')->in(array('created', 'received'))
                    ->getQuery();

                $resArray = [];
                foreach ($qb->toArray() as $transaction) {
                    $output->writeln('CHECK CRYPTO ID: '.$transaction->getId());
                    $data = $transaction->getPayInInfo();
                    $output->writeln('CHECK CRYPTO concept: '.$data['concept']);
                    if (isset($data['expires_in'])) {

                        $resArray [] = $transaction;
                        $previous_status = $transaction->getStatus();

                        $transaction = $this->check($transaction);
                        $output->writeln('CHECK CRYPTO status: '.$transaction->getStatus());
                        if ($previous_status != $transaction->getStatus()) {
                            $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                            $transaction->setUpdated(new \DateTime);
                        }

                        $dm->persist($transaction);
                        $dm->flush();

                        if ($transaction->getStatus() == Transaction::$STATUS_SUCCESS) {
                            //hacemos el reparto
                            //primero al user
                            $output->writeln('CHECK CRYPTO success');
                            $id = $transaction->getUser();
                            $groupId = $transaction->getGroup();

                            $transaction_id = $transaction->getId();
                            //$user = $repo->find($id);
                            $group = $repoGroup->find($groupId);

                            $service_currency = $transaction->getCurrency();
                            $wallet = $group->getWallet($service_currency);

                            $amount = $data['amount'];

                            //if group has
                            if (!$group->hasRole('ROLE_SUPER_ADMIN')) {
                                $output->writeln('CHECK CRYPTO no superadmin');
                                $fixed_fee = $transaction->getFixedFee();
                                $variable_fee = $transaction->getVariableFee();
                                $total_fee = $fixed_fee + $variable_fee;
                                $total = $amount - $total_fee;
                                $output->writeln('CHECK CRYPTO add to wallet');
                                $wallet->setAvailable($wallet->getAvailable() + $total);
                                $wallet->setBalance($wallet->getBalance() + $total);

                                $em->persist($wallet);
                                $em->flush();

                                if ($total_fee != 0) {
                                    $output->writeln('CHECK CRYPTO fees');
                                    // restar las comisiones
                                    $feeTransaction = new Transaction();
                                    $feeTransaction->setStatus('success');
                                    $feeTransaction->setScale($transaction->getScale());
                                    $feeTransaction->setAmount($total_fee);
                                    $feeTransaction->setUser($id);
                                    $feeTransaction->setGroup($group->getId());
                                    $feeTransaction->setCreated(new \MongoDate());
                                    $feeTransaction->setUpdated(new \MongoDate());
                                    $feeTransaction->setIp($transaction->getIp());
                                    $feeTransaction->setFixedFee($fixed_fee);
                                    $feeTransaction->setVariableFee($variable_fee);
                                    $feeTransaction->setVersion($transaction->getVersion());
                                    $feeTransaction->setDataIn(array(
                                        'previous_transaction' => $transaction->getId(),
                                        'amount' => -$total_fee
                                    ));
                                    $feeTransaction->setDebugData(array(
                                        'previous_balance' => $wallet->getBalance(),
                                        'previous_transaction' => $transaction->getId()
                                    ));
                                    $feeTransaction->setTotal(-$total_fee);
                                    $feeTransaction->setCurrency($transaction->getCurrency());
                                    $feeTransaction->setService($method);
                                    $feeTransaction->setMethod($method);
                                    $feeTransaction->setType('fee');

                                    $dm->persist($feeTransaction);
                                    $dm->flush();

                                    $em->persist($wallet);
                                    $em->flush();

                                    $creator = $group->getGroupCreator();

                                    //luego a la ruleta de admins
                                    $dealer = $this->getContainer()->get('net.telepay.commons.fee_deal');
                                    $dealer->deal(
                                        $creator,
                                        $amount,
                                        $method,
                                        $type,
                                        $service_currency,
                                        $total_fee,
                                        $transaction_id,
                                        $transaction->getVersion());
                                }

                                //TODO exchange if needed
                                $dataIn = $transaction->getDataIn();
                                if(isset($dataIn['currency_out']) && $dataIn['currency_out'] != strtoupper($service_currency)){
                                    $cur_in = strtoupper($transaction->getCurrency());
                                    $cur_out = strtoupper($dataIn['currency_out']);
                                    $exchanger = $this->getContainer()->get('net.telepay.commons.exchange_manipulator');
                                    $exchangeAmount = $exchanger->exchange($total, $transaction->getCurrency(), $dataIn['currency_out']);

                                    //TODO exchange in transaction
                                    //TODO discount fees
                                    //check group exchange limits
                                    $service = 'exchange'.'_'.$cur_in.'to'.$cur_out;
                                    $limit = $em->getRepository('TelepayFinancialApiBundle:LimitDefinition')->findOneBy(array(
                                        'cname'     =>  $service,
                                        'group'     => $groupId
                                    ));

                                    //checkWallet sender
                                    $senderWallet = $group->getWallet($cur_in);
                                    $receiverWallet = $group->getWallet($cur_out);

                                    //getFees
                                    $fees = $group->getCommissions();

                                    $exchange_fixed_fee = null;
                                    $exchange_variable_fee = null;

                                    foreach($fees as $fee){
                                        if($fee->getServiceName() == $service){
                                            $exchange_fixed_fee = $fee->getFixed();
                                            $exchange_variable_fee = round((($fee->getVariable()/100) * $exchangeAmount), 0);
                                        }
                                    }

                                    $price = $exchanger->getPrice($cur_in, $cur_out);

                                    $totalExchangeFee = $exchange_fixed_fee + $exchange_variable_fee;

                                    $params = array(
                                        'amount'    => 0,
                                        'from'  =>  $cur_in,
                                        'to'    => $cur_out
                                    );
                                    //cashOut transaction
                                    $cashOut = new Transaction();
                                    $cashOut->setIp('');
                                    $cashOut->setStatus(Transaction::$STATUS_CREATED);
                                    $cashOut->setNotificationTries(0);
                                    $cashOut->setMaxNotificationTries(3);
                                    $cashOut->setNotified(false);
                                    $cashOut->setAmount($exchangeAmount);
                                    $cashOut->setCurrency($cur_out);
                                    $cashOut->setDataIn($params);
                                    $cashOut->setFixedFee(0);
                                    $cashOut->setVariableFee(0);
                                    $cashOut->setTotal(-$amount);
                                    $cashOut->setType('out');
                                    $cashOut->setMethod($service);
                                    $cashOut->setService($service);
                                    $cashOut->setUser($user->getId());
                                    $cashOut->setGroup($userGroup->getId());
                                    $cashOut->setVersion(1);
                                    $cashOut->setScale($senderWallet->getScale());
                                    $cashOut->setStatus(Transaction::$STATUS_SUCCESS);
                                    $cashOut->setPayOutInfo(array(
                                        'amount'    =>  $amount,
                                        'currency'  =>  $from,
                                        'scale'     =>  $senderWallet->getScale(),
                                        'concept'   =>  'Exchange '.$from.' to '.$to,
                                        'price'     =>  $price,
                                    ));

                                    $dm->persist($cashOut);
                                    $dm->flush();

                                    $paramsOut = $params;
                                    $paramsOut['amount'] = $exchange;
                                    //cashIn transaction
                                    $cashIn = Transaction::createFromRequest($request);
                                    $cashIn->setAmount($exchange);
                                    $cashIn->setCurrency($to);
                                    $cashIn->setDataIn($params);
                                    $cashIn->setFixedFee($fixed_fee);
                                    $cashIn->setVariableFee($variable_fee);
                                    $cashIn->setTotal($exchange);
                                    $cashIn->setService($service);
                                    $cashIn->setType('in');
                                    $cashIn->setMethod($service);
                                    $cashIn->setUser($user->getId());
                                    $cashIn->setGroup($userGroup->getId());
                                    $cashIn->setVersion(1);
                                    $cashIn->setScale($receiverWallet->getScale());
                                    $cashIn->setStatus(Transaction::$STATUS_SUCCESS);
                                    $cashIn->setDataIn($paramsOut);
                                    $cashIn->setPayInInfo(array(
                                        'amount'    =>  $exchange,
                                        'currency'  =>  $to,
                                        'scale'     =>  $receiverWallet->getScale(),
                                        'concept'   =>  'Exchange '.$from.' to '.$to,
                                        'price'     =>  $price,
                                    ));

                                    $dm->persist($cashIn);
                                    $dm->flush();

                                    //update wallets
                                    $senderWallet->setAvailable($senderWallet->getAvailable() - $amount);
                                    $senderWallet->setBalance($senderWallet->getBalance() - $amount);

                                    $receiverWallet->setAvailable($receiverWallet->getAvailable() + $exchange - $fixed_fee - $variable_fee);
                                    $receiverWallet->setBalance($receiverWallet->getBalance() + $exchange - $fixed_fee - $variable_fee);

                                    $em->persist($senderWallet);
                                    $em->persist($receiverWallet);
                                    $em->flush();



                                }

                            } else {
                                $wallet->setAvailable($wallet->getAvailable() + $amount);
                                $wallet->setBalance($wallet->getBalance() + $amount);

                                $em->persist($wallet);
                                $em->flush();
                            }



                        } elseif ($transaction->getStatus() == Transaction::$STATUS_EXPIRED) {
                            //SEND AN EMAIL
//                            $this->sendEmail(
//                                $method . ' Expired --> ' . $transaction->getStatus(),
//                                'Transaction created at: ' . $transaction->getCreated() . ' - Updated at: ' . $transaction->getUpdated() . ' Time server: ' . date("Y-m-d H:i:s"));
                        }
                    }

                }

                $output->writeln($method . ' transactions checked');
            }

            $dm->flush();

            $output->writeln('(' . $n . ')Crypto transactions checked');
            $n++;
        }
        $output->writeln('Crypto transactions finished');
    }

    public function check(Transaction $transaction){

        $paymentInfo = $transaction->getPayInInfo();

        if($transaction->getStatus() === 'success' || $transaction->getStatus() === 'expired'){
            return $transaction;
        }

        $providerName = 'net.telepay.'.$transaction->getType().'.'.$transaction->getMethod().'.v1';
        $cryptoProvider = $this->getContainer()->get($providerName);

        $paymentInfo = $cryptoProvider->getPayInStatus($paymentInfo);

        $transaction->setStatus($paymentInfo['status']);
        $transaction->setPayInInfo($paymentInfo);

        if($transaction->getStatus() === 'created' && $this->hasExpired($transaction)){
            $transaction->setStatus('expired');
        }

        return $transaction;
    }

    private function hasExpired($transaction){

        return $transaction->getCreated()->getTimestamp() + $transaction->getPayInInfo()['expires_in'] < time();

    }

    private function sendEmail($subject, $body){

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom('no-reply@chip-chap.com')
            ->setTo(array(
                'pere@chip-chap.com'
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:support.html.twig',
                        array(
                            'message'        =>  $body
                        )
                    )
            );

        $this->getContainer()->get('mailer')->send($message);
    }
}