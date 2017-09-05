<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\FeeDeal;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\LimitAdder;
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
        $init = time();
        $now = time();
        while($n<$exec_n_times && ($now - $init) < 58) {
            $method_cname = array('fac', 'btc', 'crea', 'eth');
            $type = 'in';

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $em = $this->getContainer()->get('doctrine')->getManager();
            $repoGroup = $em->getRepository('TelepayFinancialApiBundle:Group');
            $output->writeln('CHECK CRYPTO');
            foreach ($method_cname as $method) {
                $output->writeln($method . ' INIT');

                $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                    ->field('method')->equals($method)
                    ->field('type')->equals($type)
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
                            $output->writeln('Notificate init:');
                            $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                            $output->writeln('Notificate end');
                            $transaction->setUpdated(new \DateTime);
                        }

                        $dm->flush();

                        $groupId = $transaction->getGroup();
                        $group = $repoGroup->find($groupId);

                        $fixed_fee = $transaction->getFixedFee();
                        $variable_fee = $transaction->getVariableFee();
                        $total_fee = $fixed_fee + $variable_fee;
                        $amount = $data['amount'];
                        $total = $amount - $total_fee;

                        if ($transaction->getStatus() == Transaction::$STATUS_SUCCESS) {
                            //hacemos el reparto
                            //primero al user
                            $output->writeln('CHECK CRYPTO success');
                            $id = $transaction->getUser();
                            $transaction_id = $transaction->getId();

                            $service_currency = $transaction->getCurrency();
                            $wallet = $group->getWallet($service_currency);

                            //insert new line in the balance fro this group
                            $balancer = $this->getContainer()->get('net.telepay.commons.balance_manipulator');
                            $balancer->addBalance($group, $amount, $transaction);

                            //if group has
                            if (!$group->hasRole('ROLE_SUPER_ADMIN')) {
                                $output->writeln('CHECK CRYPTO no superadmin');

                                $output->writeln('CHECK CRYPTO add to wallet');
                                $wallet->setAvailable($wallet->getAvailable() + $total);
                                $wallet->setBalance($wallet->getBalance() + $total);

                                $em->flush();

                                //recorrer el arbol aunque la fee sea 0
                                if ($total_fee != 0) {
                                    $output->writeln('CHECK CRYPTO fees');
                                    // restar las comisiones
                                    $feeTransaction = new Transaction();
                                    $feeTransaction->setStatus(Transaction::$STATUS_SUCCESS);
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

                                    $balancer->addBalance($group, -$total_fee, $feeTransaction);
                                }

                                //exchange if needed
                                $dataIn = $transaction->getDataIn();
                                if(isset($dataIn['request_currency_out']) && $dataIn['request_currency_out'] != strtoupper($service_currency)){

                                    $cur_in = strtoupper($transaction->getCurrency());
                                    $cur_out = strtoupper($dataIn['request_currency_out']);
                                    //THIS is the service for get the limits
                                    $service = 'exchange'.'_'.$cur_in.'to'.$cur_out;
                                    $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($id);
                                    $output->writeln('CHECK CRYPTO exchanger');
                                    $exchanger = $this->getContainer()->get('net.telepay.commons.exchange_manipulator');
                                    $exchangeAmount = $exchanger->exchange($total, $transaction->getCurrency(), $cur_out);
                                    $output->writeln('CHECK CRYPTO exchange->'.$total.' '.$transaction->getCurrency().' = '.$exchangeAmount.' '.$cur_out);
                                    try{
                                        $exchanger->doExchange($total, $cur_in, $cur_out, $group, $user);
                                    }catch (HttpException $e){
                                        //TODO send message alerting that this exchange has failed for some reason
                                    }

                                }

                            } else {
                                $wallet->setAvailable($wallet->getAvailable() + $amount);
                                $wallet->setBalance($wallet->getBalance() + $amount);

                                $em->flush();
                            }

                        } elseif ($transaction->getStatus() == Transaction::$STATUS_EXPIRED) {
                            $output->writeln('TRANSACTION EXPIRED');

                            $output->writeln('NOTIFYING EXPIRED');
                            $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                            $output->writeln('Notificate end');
                            //if delete_on_expire==true delete transaction
                            $paymentInfo = $transaction->getPayInInfo();
                            if ($transaction->getDeleteOnExpire() == true && $paymentInfo['received'] == 0) {
                                $transaction->setStatus('deleted');
                                $em->flush();
                                $output->writeln('NOTIFYING DELETE ON EXPIRE');
                                $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                                $output->writeln('Notificate end');
                                $output->writeln('DELETE ON EXPIRE');
//                                $dm->remove($transaction);
                                $dm->flush();
                            }

                        }
                    }

                }

                $output->writeln($method . ' transactions checked');
            }

            $dm->flush();

            $output->writeln('(' . $n . ')Crypto transactions checked in ' . $now - $init . ' seconds');
            $n++;
            $now = time();
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