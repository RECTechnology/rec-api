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

class CheckScheduledCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:scheduled:check')
            ->setDescription('Check scheduled transactions and create method out')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $em = $this->getContainer()->get('doctrine')->getManager();
        $scheduledRepo = $em->getRepository("TelepayFinancialApiBundle:Scheduled");
        $scheduleds = $scheduledRepo->findAll();

        foreach($scheduleds as $scheduled){
            $today = date("j");
            if($scheduled->getPeriod() == 0 || $today == "1"){
                $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($scheduled->getUser());
                $userWallets = $user->getWallets();

                $current_wallet = null;
                foreach ( $userWallets as $wallet){
                    if ($wallet->getCurrency() == $scheduled->getWallet()){
                        $current_wallet = $wallet;
                    }
                }
                if($current_wallet->getAvailable() > ($scheduled->getMinimum() + $scheduled->getThreshold())){
                    $amount = $current_wallet->getAvailable() - $scheduled->getThreshold();

                    $method = $this->get('net.telepay.out.' . $scheduled->getMethod() . '.v1');

                    $dm = $this->get('doctrine_mongodb')->getManager();
                    $em = $this->getDoctrine()->getManager();

                    $user = $scheduled->getUser();
                    $transaction = new Transaction();
                    $transaction->setIp('127.0.0.1');
                    $transaction->setStatus(Transaction::$STATUS_CREATED);
                    $transaction->setNotificationTries(0);
                    $transaction->setMaxNotificationTries(3);
                    $transaction->setNotified(false);
                    $transaction->setService("sepa");
                    $transaction->setMethod("sepa");
                    $transaction->setUser($user->getId());
                    $transaction->setVersion(1);
                    $transaction->setType('out');
                    $dm->persist($transaction);

                    $info = json_decode($scheduled->getInfo());
                    $concept = $info['concept'] . date("d.m.y");
                    $url_notification = '';
                    $request = array(
                        'beneficiary' => $info['beneficiary'],
                        'iban' => $info['iban'],
                        'amount' => $amount,
                        'bic_swift' => $info['swift']
                    );

                    $payment_info = $method->getPayOutInfo($request);
                    $transaction->setPayOutInfo($payment_info);
                    $dataIn = array(
                        'amount'    =>  $amount,
                        'concept'   =>  $concept,
                        'url_notification'  =>  $url_notification
                    );

                    $transaction->setDataIn($dataIn);

                    $user = $this->getUser();
                    $group = $user->getGroups()[0];
                    $group_commission = $this->_getFees($group, $method);
                    $transaction->setAmount($amount);

                    //add commissions to check
                    $fixed_fee = $group_commission->getFixed();
                    $variable_fee = round(($group_commission->getVariable()/100) * $amount, 0);
                    $total_fee = $fixed_fee + $variable_fee;

                    //add fee to transaction
                    $transaction->setVariableFee($variable_fee);
                    $transaction->setFixedFee($fixed_fee);
                    $dm->persist($transaction);

                    //le cambiamos el signo para guardarla i marcarla como salida en el wallet
                    $transaction->setTotal(-$amount);
                    $total = $amount + $variable_fee + $fixed_fee;

                    //obtain user limits
                    $user_limit = $this->_getLimitCount($user, $method);
                    //obtain group limit
                    $group_limit = $this->_getLimits($group, $method);
                    $new_user_limit = (new LimitAdder())->add( $user_limit, $total);
                    $checker = new LimitChecker();
                    if(!$checker->leq($new_user_limit, $group_limit)) ;

                    //obtain wallet and check founds for cash_out services
                    $wallets = $user->getWallets();
                    $current_wallet = null;
                    $transaction->setCurrency($method->getCurrency());

                    //******    CHECK IF THE TRANSACTION IS CASH-OUT     ********
                    foreach ( $wallets as $wallet){
                        if ($wallet->getCurrency() == $method->getCurrency()){
                            //Bloqueamos la pasta en el wallet
                            $actual_available = $wallet->getAvailable();
                            $new_available = $actual_available - $total;
                            $wallet->setAvailable($new_available);
                            $em->persist($wallet);
                            $em->flush();
                            $current_wallet = $wallet;
                        }
                    }

                    $scale = $current_wallet->getScale();
                    $transaction->setScale($scale);
                    $dm->persist($transaction);
                    $dm->flush();

                    $payment_info = $method->send($payment_info);

                    $transaction->setPayOutInfo($payment_info);
                    $dm->persist($transaction);
                    $dm->flush();

                    //pay fees and dealer always and set new balance
                    if( $payment_info['status'] == 'sent' || $payment_info['status'] == 'sending'){

                        if($payment_info['status'] == 'sent') $transaction->setStatus(Transaction::$STATUS_SUCCESS);
                        else $transaction->setStatus('sending');

                        $dm->persist($transaction);
                        $dm->flush();

                        //restar al usuario el amount + comisiones
                        $current_wallet->setBalance($current_wallet->getBalance() - $total);

                        //insert new line in the balance
                        $balancer = $this->get('net.telepay.commons.balance_manipulator');
                        $balancer->addBalance($user, -$amount, $transaction);

                        $em->persist($current_wallet);
                        $em->flush();

                        $method->sendMail($transaction->getId(), $transaction->getType(), $payment_info);
                        if( $total_fee != 0){
                            //nueva transaccion restando la comision al user
                            try{
                                $this->_dealer($transaction, $current_wallet);
                            }catch (HttpException $e){
                                throw $e;
                            }
                        }
                    }else{
                        $transaction->setStatus($payment_info['status']);
                        //desbloqueamos la pasta del wallet
                        $current_wallet->setAvailable($current_wallet->getAvailable() + $total);
                        $em->persist($current_wallet);
                        $em->flush();
                        $dm->persist($transaction);
                        $dm->flush();
                    }
                }
            }
        }
        $output->writeln('Scheduled transactions checked');
    }

    private function _dealer(Transaction $transaction, UserWallet $current_wallet){

        $amount = $transaction->getAmount();
        $currency = $transaction->getCurrency();
        $method_cname = $transaction->getMethod();

        $em = $this->getDoctrine()->getManager();

        $total_fee = $transaction->getFixedFee() + $transaction->getVariableFee();

        $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($transaction->getUser());

        $group = $user->getGroups()[0];
        $creator = $group->getCreator();

        $feeTransaction = Transaction::createFromTransaction($transaction);
        $feeTransaction->setAmount($total_fee);
        $feeTransaction->setDataIn(array(
            'previous_transaction'  =>  $transaction->getId(),
            'amount'                =>  -$total_fee,
            'description'           =>  $method_cname.'->fee',
            'admin'                 =>  $creator->getUsername()
        ));
        $feeTransaction->setData(array(
            'previous_transaction'  =>  $transaction->getId(),
            'amount'                =>  -$total_fee,
            'type'                  =>  'resta_fee'
        ));
        $feeTransaction->setDebugData(array(
            'previous_balance'  =>  $current_wallet->getBalance(),
            'previous_transaction'  =>  $transaction->getId()
        ));

        $feeTransaction->setPayOutInfo(array(
            'previous_transaction'  =>  $transaction->getId(),
            'amount'                =>  -$total_fee,
            'description'           =>  $method_cname.'->fee',
            'admin'                 =>  $creator->getUsername()
        ));

        $feeTransaction->setType('fee');

        $feeTransaction->setTotal(-$total_fee);

        $mongo = $this->get('doctrine_mongodb')->getManager();
        $mongo->persist($feeTransaction);
        $mongo->flush();

        $balancer = $this->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($user, -$total_fee, $feeTransaction );

        //empezamos el reparto


        if(!$creator) throw new HttpException(404,'Creator not found');

        $transaction_id = $transaction->getId();
        $dealer = $this->get('net.telepay.commons.fee_deal');
        $dealer->deal(
            $creator,
            $amount,
            $method_cname,
            $transaction->getType(),
            $currency,
            $total_fee,
            $transaction_id,
            $transaction->getVersion()
        );

    }
}