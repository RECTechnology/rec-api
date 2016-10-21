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
                    $receivedTransactions = $receivedTransactions * 1e8;
                    $output->writeln($receivedTransactions. ' total amount');
                    $output->writeln($method.' transactions');

                    $totalDepositedTransactions = $em->getRepository('TelepayFinancialApiBundle:CashInDeposit')->findBy(array(
                        'token'    =>  $token
                    ));

                    $totalDeposited = 0;
                    if($totalDepositedTransactions){
                        $output->writeln('Total depositedTransaction exists');
                        foreach($totalDepositedTransactions as $deposited){
                            $totalDeposited += $deposited->getAmount();
                        }
                    }
                    $output->writeln('Total deposited: '.$totalDeposited);
                    if($totalDeposited < $receivedTransactions){
                        $output->writeln('New transaction detected');
                        $depositAmount = $receivedTransactions - $totalDeposited;

                        //new deposit
                        $output->writeln('Creating new deposit');
                        $deposit = new CashInDeposit();
                        $deposit->setToken($token);
                        $deposit->setStatus(CashInDeposit::$STATUS_DEPOSITED);
                        $deposit->setAmount($depositAmount);
                        $deposit->setConfirmations(1);
                        $deposit->setHash(uniqid('hash-'));

                        $em->persist($deposit);
                        $em->flush();

                        //get fees
                        $companyFees = $feeManipulator->getMethodFees($token->getCompany(), $methodDriver);
                        $fixed_fee = $companyFees->getFixed();
                        $variable_fee = $companyFees->getVariable();

                        //Generate new transaction
                        $output->writeln('Generate new transaction');
                        $depositTransaction = new Transaction();
                        $depositTransaction->setStatus(Transaction::$STATUS_SUCCESS);
                        $depositTransaction->setScale(Currency::$SCALE[$token->getCurrency()]);
                        $depositTransaction->setAmount($depositAmount);
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
                            'received' => $depositAmount,
                            'min_confirmations' => 1,
                            'confirmations' => 1,
                            'status'    =>  Transaction::$STATUS_SUCCESS,
                            'final'     =>  true
                        ));

                        $dm->persist($depositTransaction);
                        $dm->flush();

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
                            $output->writeln('Creating fee transaction');
                            $feeTransaction = new Transaction();
                            $feeTransaction->setStatus('success');
                            $feeTransaction->setScale($depositTransaction->getScale());
                            $feeTransaction->setAmount($total_fee);
                            $feeTransaction->setGroup($depositTransaction->getGroup());
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