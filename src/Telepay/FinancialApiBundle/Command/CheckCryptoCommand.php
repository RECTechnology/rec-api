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
        while($n<100) {
            $method_cname = array('fac', 'btc');
            $type = 'in';

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $em = $this->getContainer()->get('doctrine')->getManager();
            $repo = $em->getRepository('TelepayFinancialApiBundle:User');
            $repoGroup = $em->getRepository('TelepayFinancialApiBundle:Group');

            foreach ($method_cname as $method) {
                $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                    ->field('method')->equals($method)
                    ->field('status')->in(array('created', 'received'))
                    ->getQuery();

                $resArray = [];
                foreach ($qb->toArray() as $transaction) {

                    $data = $transaction->getPayInInfo();

                    if (isset($data['expires_in'])) {

                        $resArray [] = $transaction;
                        $previous_status = $transaction->getStatus();

                        $transaction = $this->check($transaction);

                        if ($previous_status != $transaction->getStatus()) {
                            $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                            $transaction->setUpdated(new \MongoDate());

                        }

                        $dm->persist($transaction);
                        $dm->flush();

                        if ($transaction->getStatus() == Transaction::$STATUS_SUCCESS) {
                            //hacemos el reparto
                            //primero al user
                            $id = $transaction->getUser();
                            $groupId = $transaction->getGroup();

                            $transaction_id = $transaction->getId();

                            $user = $repo->find($id);
                            $group = $repoGroup->find($groupId);

                            $wallets = $group->getWallets();
                            $service_currency = $transaction->getCurrency();
                            $current_wallet = null;

                            foreach ($wallets as $wallet) {
                                if ($wallet->getCurrency() == $service_currency) {
                                    $current_wallet = $wallet;
                                }
                            }

                            $amount = $data['amount'];

                            //TODO if group has
                            if (!$group->hasRole('ROLE_SUPER_ADMIN')) {

                                $fixed_fee = $transaction->getFixedFee();
                                $variable_fee = $transaction->getVariableFee();
                                $total_fee = $fixed_fee + $variable_fee;
                                $total = $amount - $total_fee;

                                $current_wallet->setAvailable($current_wallet->getAvailable() + $total);
                                $current_wallet->setBalance($current_wallet->getBalance() + $total);

                                $em->persist($current_wallet);
                                $em->flush();

                                if ($total_fee != 0) {
                                    // restar las comisiones
                                    $feeTransaction = new Transaction();
                                    $feeTransaction->setStatus('success');
                                    $feeTransaction->setScale($transaction->getScale());
                                    $feeTransaction->setAmount($total_fee);
                                    $feeTransaction->setUser($user->getId());
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
                                        'previous_balance' => $current_wallet->getBalance(),
                                        'previous_transaction' => $transaction->getId()
                                    ));
                                    $feeTransaction->setTotal(-$total_fee);
                                    $feeTransaction->setCurrency($transaction->getCurrency());
                                    $feeTransaction->setService($method);
                                    $feeTransaction->setMethod($method);
                                    $feeTransaction->setType('fee');


                                    $dm->persist($feeTransaction);
                                    $dm->flush();

                                    $em->persist($current_wallet);
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

                            } else {
                                $current_wallet->setAvailable($current_wallet->getAvailable() + $amount);
                                $current_wallet->setBalance($current_wallet->getBalance() + $amount);

                                $em->persist($current_wallet);
                                $em->flush();
                            }

                        } elseif ($transaction->getStatus() == Transaction::$STATUS_EXPIRED) {
                            //SEND AN EMAIL
                            $this->sendEmail(
                                $method . ' Expired --> ' . $transaction->getStatus(),
                                'Transaction created at: ' . $transaction->getCreated() . ' - Updated at: ' . $transaction->getUpdated() . ' Time server: ' . date("Y-m-d H:i:s"));
                        }
                    }

                }

                $output->writeln($method . ' transactions checked');
            }

            $dm->flush();

            $output->writeln('(' + $n + ')Crypto transactions checked');
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