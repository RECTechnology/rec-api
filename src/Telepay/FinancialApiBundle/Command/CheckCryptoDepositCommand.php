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
        $count_deposits = 0;
        $methods = array('btc','fac', 'crea', 'eth');
        $type = 'in';

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository('TelepayFinancialApiBundle:User');
        $repoGroup = $em->getRepository('TelepayFinancialApiBundle:Group');

        $feeManipulator = $this->getContainer()->get('net.telepay.commons.fee_manipulator');
        $output->writeln('Init checker...');

        $env = $this->getContainer()->getParameter('environment');

        foreach ($methods as $method) {
            $scale = Currency::$SCALE[strtoupper($method)];
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
                $output->writeln($receivedTransactions. ' all received');
                $receivedTransactions = $receivedTransactions * pow(10, $scale);
                $output->writeln($receivedTransactions. ' total amount');
                $output->writeln($method.' transaction');

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
                if($totalDeposited + 100 < $receivedTransactions ){
                    $count_deposits++;
                    $output->writeln('New transaction detected '.$receivedTransactions.' - '.$totalDeposited);
                    $depositAmount = $receivedTransactions - $totalDeposited;
                    $output->writeln('New transaction amount '. $depositAmount);

                    //new deposit
                    $output->writeln('Creating new deposit');
                    $deposit = new CashInDeposit();
                    $deposit->setToken($token);
                    $deposit->setStatus(CashInDeposit::$STATUS_DEPOSITED);
                    $deposit->setAmount($depositAmount);
                    $deposit->setConfirmations(1);
                    $deposit->setHash(uniqid('hash-'));
                    $deposit->setExternalId(uniqid('external_id-'));

                    $em->persist($deposit);
                    $em->flush();

                    //get fees
                    $companyFees = $feeManipulator->getMethodFees($token->getCompany(), $methodDriver);
                    $fixed_fee = $companyFees->getFixed();
                    $variable_fee = round(($companyFees->getVariable()/100) * $depositAmount, 0);

                    //Generate new transaction
                    $output->writeln('Generate new transaction');
                    $depositTransaction = new Transaction();
                    $depositTransaction->setStatus(Transaction::$STATUS_RECEIVED);
                    $depositTransaction->setScale(Currency::$SCALE[$token->getCurrency()]);
                    $depositTransaction->setAmount($depositAmount);
                    $depositTransaction->setGroup($token->getCompany()->getId());
                    $depositTransaction->setUser(-1);
                    $depositTransaction->setCreated(new \MongoDate());
                    $depositTransaction->setUpdated(new \MongoDate());
                    $depositTransaction->setIp('');
                    $depositTransaction->setFixedFee($fixed_fee);
                    $depositTransaction->setVariableFee($variable_fee);
                    $depositTransaction->setVersion(1);
                    $depositTransaction->setMaxNotificationTries(3);
                    $depositTransaction->setNotificationTries(0);
                    $depositTransaction->setNotified(false);

                    $depositTransaction->setDebugData(array(
                        'deposit_id' => $deposit->getId(),
                        'token_id' => $token->getId()
                    ));
                    $depositTransaction->setTotal($deposit->getAmount());
                    $depositTransaction->setCurrency($token->getCurrency());
                    $depositTransaction->setService($method);
                    $depositTransaction->setMethod($method);
                    $depositTransaction->setType('in');

                    $min_confirmations = $this->getContainer()->getParameter($method . '_min_confirmations');
                    $depositTransaction->setPayInInfo(array(
                        'amount'    =>  $deposit->getAmount(),
                        'currency'  =>  $token->getCurrency(),
                        'scale' =>  Currency::$SCALE[$token->getCurrency()],
                        'address' => $token->getToken(),
                        'expires_in' => intval(1200),
                        'received' => $depositAmount,
                        'min_confirmations' => $min_confirmations,
                        'confirmations' => 0,
                        'status'    =>  Transaction::$STATUS_RECEIVED,
                        'final'     =>  false,
                        'concept'   =>  'New Deposit => '.$deposit->getId()
                    ));

                    $dm->persist($depositTransaction);
                    $dm->flush();

                    exec('curl -X POST -d "chat_id=-145386290&text=#deposit_'.$env.' '.$token->getCompany()->getName().' deposited '.($deposit->getAmount()/100000000).' '.$token->getCurrency().'" "https://api.telegram.org/bot348257911:AAG9z3cJnDi31-7MBsznurN-KZx6Ho_X4ao/sendMessage"');

                }

            }
            $output->writeln($method . ' transactions checked');
        }
        $dm->flush();

        $output->writeln('Crypto transactions finished');

    }
}