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
use Telepay\FinancialApiBundle\Entity\CashInDeposit;
use Telepay\FinancialApiBundle\Entity\CashInTokens;
use Telepay\FinancialApiBundle\Entity\Exchange;
use Telepay\FinancialApiBundle\Financial\Currency;

class CheckCryptoDepositCommand extends SyncronizedContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:crypto:deposit:check')
            ->setDescription('Check crypto deposits')
        ;
    }

    protected function executeSyncronized(InputInterface $input, OutputInterface $output){
        $n = 0;
        $exec_n_times = 1;
        while($n<$exec_n_times) {
            $methods = array('fac', 'btc');
            $type = 'in';

            $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
            $em = $this->getContainer()->get('doctrine')->getManager();
            $repo = $em->getRepository('TelepayFinancialApiBundle:User');
            $repoGroup = $em->getRepository('TelepayFinancialApiBundle:Group');

            $feeManipulator = $this->getContainer()->get('net.telepay.commons.fee_manipulator');
            $output->writeln('Init checker...');
            foreach ($methods as $method) {
                $output->writeln($method . ' INIT');
                $tokens = $em->getRepository('TelepayFinancialApiBundle:CashInTokens')->findBy(array(
                    'method'    =>  $method.'-'.$type,
                    'status'    =>  CashInTokens::$STATUS_ACTIVE
                ));

                $methodDriver = $this->getContainer()->get('net.telepay.in.'.$method.'.v1');

                foreach($tokens as $token){
                    $output->writeln($token->getId() . ' TOKEN_id');
                    $output->writeln($token->getToken() . ' TOKEN');
                    $receivedTransactions = $methodDriver->getReceivedByAddress($token->getToken());
                    $output->writeln(count($receivedTransactions. ' received transactions'));
                    $output->writeln($method.' transactions');
                    foreach($receivedTransactions as $received){
                        $output->writeln($received['tx'].' HASH');
                        //TODO habria que ver como se devuelve el hash para compararlo con los guardados
                        $deposit = $em->getRepository('TelepayFinancialApiBundle:CashInDeposit')->findOneBy(
                            array(
                                'token' =>  $token,
                                'hash'  =>  $received['hash']
                            )
                        );

                        if($deposit){
                            if($deposit->getStatus() == CashInDeposit::$STATUS_RECEIVED){
                                if($received['confirmations'] >= 1){

                                    //TODO get fees
                                    $companyFees = $feeManipulator->getMethodFees($token->getCompany(), $method);
                                    $fixed_fee = $companyFees->getFixed();
                                    $variable_fee = $companyFees->getVariable();

                                    //todo Generate new transaction
                                    $depositTransaction = new Transaction();
                                    $depositTransaction->setStatus(Transaction::$STATUS_SUCCESS);
                                    $depositTransaction->setScale(Currency::$SCALE[$token->getCurrency()]);
                                    $depositTransaction->setAmount($deposit->getAmount());
                                    $depositTransaction->setGroup($token->getCompany()->getId());
                                    $depositTransaction->setCreated(new \MongoDate());
                                    $depositTransaction->setUpdated(new \MongoDate());
                                    $depositTransaction->setIp('');
                                    $depositTransaction->setFixedFee($fixed_fee);
                                    $depositTransaction->setVariableFee($variable_fee);
                                    $depositTransaction->setVersion(1);

                                    $depositTransaction->setDebugData(array(
                                        'deposit_id' => $deposit->getId(),
                                        'token_id' => $token->getId()
                                    ));
                                    $depositTransaction->setTotal($deposit->getAmount());
                                    $depositTransaction->setCurrency($token->getCurrency());
                                    $depositTransaction->setService($method);
                                    $depositTransaction->setMethod($method);
                                    $depositTransaction->setType('in');
                                    $depositTransaction->setPayInInfo(array(
                                        'amount'    =>  $deposit->getAmount(),
                                        'currency'  =>  $token->getCurrency(),
                                        'scale' =>  Currency::$SCALE[$token->getCurrency()],
                                        'address' => $token->getToken(),
                                        'expires_in' => intval(1200),
                                        'received' => $received['received'],
                                        'min_confirmations' => 1,
                                        'confirmations' => $received['confirmations'],
                                        'status'    =>  Transaction::$STATUS_SUCCESS,
                                        'final'     =>  true
                                    ));

                                    $dm->persist($depositTransaction);
                                    $dm->flush();


                                    //change status
                                    $deposit->setStatus(CashInDeposit::$STATUS_DEPOSITED);
                                    $em->persist($deposit);
                                    $em->flush();

                                    $wallets = $token->getCompany()->getWallets();
                                    foreach ($wallets as $wallet) {
                                        if ($wallet->getCurrency() == $token->getCurrency()) {
                                            $current_wallet = $wallet;
                                        }
                                    }

                                    $total_fee = $fixed_fee + $variable_fee;
                                    $total = $deposit->getAmount() - $total_fee;

                                    $current_wallet->setAvailable($current_wallet->getAvailable() + $total);
                                    $current_wallet->setBalance($current_wallet->getBalance() + $total);

                                    $em->persist($current_wallet);
                                    $em->flush();

                                    if ($total_fee != 0) {
                                        // restar las comisiones
                                        $feeTransaction = new Transaction();
                                        $feeTransaction->setStatus('success');
                                        $feeTransaction->setScale($depositTransaction->getScale());
                                        $feeTransaction->setAmount($total_fee);
                                        $feeTransaction->setGroup($depositTransaction->getCompany()->getId());
                                        $feeTransaction->setCreated(new \MongoDate());
                                        $feeTransaction->setUpdated(new \MongoDate());
                                        $feeTransaction->setIp($depositTransaction->getIp());
                                        $feeTransaction->setFixedFee($fixed_fee);
                                        $feeTransaction->setVariableFee($variable_fee);
                                        $feeTransaction->setVersion($depositTransaction->getVersion());
                                        $feeTransaction->setDataIn(array(
                                            'previous_transaction' => $depositTransaction->getId(),
                                            'amount' => -$total_fee
                                        ));
                                        $feeTransaction->setDebugData(array(
                                            'previous_balance' => $current_wallet->getBalance(),
                                            'previous_transaction' => $depositTransaction->getId()
                                        ));
                                        $feeTransaction->setTotal(-$total_fee);
                                        $feeTransaction->setCurrency($depositTransaction->getCurrency());
                                        $feeTransaction->setService($method);
                                        $feeTransaction->setMethod($method);
                                        $feeTransaction->setType('fee');

                                        $dm->persist($feeTransaction);
                                        $dm->flush();

                                        $creator = $token->getCompany()->getGroupCreator();

                                        //luego a la ruleta de admins
                                        $dealer = $this->getContainer()->get('net.telepay.commons.fee_deal');
                                        $dealer->deal(
                                            $creator,
                                            $deposit->getAmount(),
                                            $method,
                                            $type,
                                            $token->getCurrency(),
                                            $total_fee,
                                            $depositTransaction->getId(),
                                            $depositTransaction->getVersion());
                                    }

                                }
                            }

                        }else{
                            //TODO new deposit
                            $deposit = new CashInDeposit();
                            $deposit->setToken($token);
                            $deposit->setStatus(CashInDeposit::$STATUS_RECEIVED);
                            $deposit->setAmount($received['received']);
                            $deposit->setConfirmations($received['confirmations']);
                            $deposit->setHash($received['hash']);

                            $em->persist($deposit);
                            $em->flush();
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