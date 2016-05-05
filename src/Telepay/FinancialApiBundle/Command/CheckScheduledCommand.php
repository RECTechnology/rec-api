<?php
namespace Telepay\FinancialApiBundle\Command;

use Doctrine\DBAL\Types\ObjectType;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\FeeDeal;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\LimitAdder;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Exchange;
use Telepay\FinancialApiBundle\Entity\Group;
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

        foreach ($scheduleds as $scheduled) {
            $today = date("j");
            if ($scheduled->getPeriod() == 0 || $today == "1") {
                $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($scheduled->getUser());
                $userWallets = $user->getWallets();

                $current_wallet = null;
                foreach ($userWallets as $wallet) {
                    if ($wallet->getCurrency() == $scheduled->getWallet()) {
                        $current_wallet = $wallet;
                    }
                }
                if ($current_wallet->getAvailable() > ($scheduled->getMinimum() + $scheduled->getThreshold())) {
                    $amount = $current_wallet->getAvailable() - $scheduled->getThreshold();
                    $output->writeln($amount . ' euros de amount');
                    $amount = 10;

                    $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
                    $em = $this->getContainer()->get('doctrine')->getManager();

                    $user = $scheduled->getUser();
                    $transaction = new Transaction();
                    $transaction->setIp('127.0.0.1');
                    $transaction->setStatus(Transaction::$STATUS_CREATED);
                    $transaction->setNotificationTries(0);
                    $transaction->setMaxNotificationTries(3);
                    $transaction->setNotified(false);
                    $transaction->setAmount($amount);
                    $transaction->setCurrency($scheduled->getWallet());
                    $transaction->setService("sepa");
                    $transaction->setMethod("sepa");
                    $transaction->setUser($user->getId());
                    $transaction->setVersion(1);
                    $transaction->setType('out');
                    $dm->persist($transaction);

                    $info = json_decode($scheduled->getInfo(), true);
                    $concept = $info['concept'] . date("d.m.y");
                    $url_notification = '';
                    $request = new Request();
                    $request->request->add(array(
                        'beneficiary' => $info['beneficiary'],
                        'iban' => $info['iban'],
                        'amount' => $amount,
                        'bic_swift' => $info['swift']
                    ));

                    $method = $this->getContainer()->get('net.telepay.out.'.$scheduled->getMethod().'.v1');
                    $payment_info = $method->getPayOutInfo($request);
                    $transaction->setPayOutInfo($payment_info);
                    $dataIn = array(
                        'amount'    =>  $amount,
                        'concept'   =>  $concept,
                        'url_notification'  =>  $url_notification
                    );

                    $transaction->setDataIn($dataIn);
                    $pay_out_info = $transaction->getPayOutInfo();


                    //obtener group
                    $group = $user->getGroups()[0];
                    $group_fee = $this->_getFees($group, $method);
                    $group_fees = round(($amount * ($group_fee->getVariable()/100) + $group_fee->getFixed()),0);

                    try{
                        $pay_out_info = $method->send($pay_out_info);
                    }catch (Exception $e){
                        $pay_out_info['status'] = Transaction::$STATUS_FAILED;
                        $pay_out_info['final'] = false;
                        $transaction->setPayOutInfo($pay_out_info);
                        $transaction->setStatus('failed');
                    }
                    $transaction->setPayOutInfo($pay_out_info);
                    $dm->persist($transaction);
                    $dm->flush();
                    $transaction->setDataIn($pay_out_info);

                    $dm->persist($transaction);
                    $dm->flush();

                    if($group_fees != 0){
                        //client fees goes to the user
                        $userFee = new Transaction();
                        $userFee->setUser($transaction->getUser());
                        $userFee->setType('fee');
                        $userFee->setCurrency($transaction->getCurrency());
                        $userFee->setScale($transaction->getScale());
                        $userFee->setAmount($group_fees);
                        $userFee->setFixedFee($group_fee->getFixed());
                        $userFee->setVariableFee($amount * ($group_fee->getVariable()/100));
                        $userFee->setStatus('success');
                        $userFee->setTotal($group_fees);
                        $userFee->setDataIn(array(
                            'previous_transaction'  =>  $transaction->getId(),
                            'transaction_amount'    =>  $transaction->getAmount(),
                            'total_fee' =>  $group_fees
                        ));
                        $dm->persist($userFee);

                        $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($transaction->getUser());
                        $userWallets = $user->getWallets();
                        $current_wallet = null;

                        foreach ( $userWallets as $wallet){
                            if ($wallet->getCurrency() == $userFee->getCurrency()){
                                $current_wallet = $wallet;
                            }
                        }

                        $current_wallet->setAvailable($current_wallet->getAvailable() - $group_fees);
                        $current_wallet->setBalance($current_wallet->getBalance() - $group_fees);

                        $em->persist($current_wallet);
                        $em->flush();

                    }

                    $transaction->setTotal(-$amount);
                    $total = $amount + $group_fees;

                    //restar al usuario el amount + comisiones
                    $current_wallet->setBalance($current_wallet->getBalance() - $total);

                    //insert new line in the balance
                    $balancer = $this->get('net.telepay.commons.balance_manipulator');
                    $balancer->addBalance($user, -$amount, $transaction);

                    $em->persist($current_wallet);
                    $em->flush();


                    if( $group_fees != 0){
                        //nueva transaccion restando la comision al user
                        try{
                            $this->_dealer($transaction, $current_wallet);
                        }catch (Exception $e){
                            throw $e;
                        }
                    }

                    $dm->flush();
                }
            }
        }
        $output->writeln('All done');
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


        if(!$creator) throw new Exception('Creator not found');

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

    private function _getFees(Group $group, $method){
        $em = $this->getContainer()->get('doctrine')->getManager();

        $group_commissions = $group->getCommissions();
        $group_commission = false;

        foreach ( $group_commissions as $commission ){
            if ( $commission->getServiceName() == $method->getCname().'-'.$method->getType() ){
                $group_commission = $commission;
            }
        }

        //if group commission not exists we create it
        if(!$group_commission){
            $group_commission = ServiceFee::createFromController($method->getCname().'-'.$method->getType(), $group);
            $group_commission->setCurrency($method->getCurrency());
            $em->persist($group_commission);
            $em->flush();
        }

        return $group_commission;
    }

}