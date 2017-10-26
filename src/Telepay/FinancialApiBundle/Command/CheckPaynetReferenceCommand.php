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

class CheckPaynetReferenceCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:paynet_ref:check')
            ->setDescription('Check paynet reference transactions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $method_cname = 'paynet_reference';
        $type = 'in';

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository('TelepayFinancialApiBundle:Group');

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('method')->equals($method_cname)
            ->field('status')->in(array(Transaction::$STATUS_CREATED,Transaction::$STATUS_RECEIVED))
            ->getQuery();

        $output->writeln('Total:' . count($qb->toArray()));
        foreach($qb->toArray() as $transaction){

            $transaction_id = $transaction->getId();
            $output->writeln('ID:' . $transaction_id);

            $previous_status = $transaction->getStatus();
            $transaction = $this->check($transaction);

            if($previous_status != $transaction->getStatus()){
                $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                $transaction->setUpdated(new \MongoDate());
            }

            $dm->persist($transaction);
            $dm->flush();

            if($transaction->getStatus()=='success'){
                //hacemos el reparto
                //primero al user
                $id = $transaction->getGroup();

                $group = $repo->find($id);

                $wallets = $group->getWallets();
                $service_currency = $transaction->getCurrency();
                $current_wallet = null;
                foreach ( $wallets as $wallet){
                    if ($wallet->getCurrency() == $service_currency){
                        $current_wallet = $wallet;
                    }
                }

                $amount = $transaction->getAmount();

                if(!$group->hasRole('ROLE_SUPER_ADMIN')){

                    $fixed_fee = $transaction->getFixedFee();
                    $variable_fee = $transaction->getVariableFee();
                    $total_fee = $fixed_fee + $variable_fee;
                    $total = $amount - $total_fee;

                    //insert new line in the balance fro this group
                    $balancer = $this->getContainer()->get('net.telepay.commons.balance_manipulator');
                    $balancer->addBalance($group, $amount, $transaction, "paynet command user");

                    $current_wallet->setAvailable($current_wallet->getAvailable() + $amount);
                    $current_wallet->setBalance($current_wallet->getBalance() + $amount);

                    $em->persist($current_wallet);
                    $em->flush();

                    //luego a la ruleta de admins
                    $dealer = $this->getContainer()->get('net.telepay.commons.fee_deal');

                    $dealer->createFees2($transaction, $current_wallet);

                    //exchange if needed
                    $dataIn = $transaction->getDataIn();
                    if(isset($dataIn['request_currency_out']) && $dataIn['request_currency_out'] != strtoupper($service_currency)){
                        $cur_in = strtoupper($transaction->getCurrency());
                        $cur_out = strtoupper($dataIn['request_currency_out']);
                        //THIS is the service for get the limits
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
                }
                else{
                    //insert new line in the balance fro this group
                    $balancer = $this->getContainer()->get('net.telepay.commons.balance_manipulator');
                    $balancer->addBalance($group, $amount, $transaction, "paynet command super");

                    $current_wallet->setAvailable($current_wallet->getAvailable() + $amount);
                    $current_wallet->setBalance($current_wallet->getBalance() + $amount);

                    $em->persist($current_wallet);
                    $em->flush();
                }
            }
        }
        $dm->flush();
        $output->writeln('Paynet Reference transactions checked');
    }

    public function check(Transaction $transaction){

        $payment_info = $transaction->getPayInInfo();
        if($transaction->getStatus() === Transaction::$STATUS_CREATED && $this->hasExpired($transaction)){
            $transaction->setStatus(Transaction::$STATUS_EXPIRED);
            $payment_info['status'] = Transaction::$STATUS_EXPIRED;
            $transaction->setPayInInfo($payment_info);
        }

        if($transaction->getStatus() === Transaction::$STATUS_SUCCESS || $transaction->getStatus() === Transaction::$STATUS_EXPIRED) {
            return $transaction;
        }

        $payment_info = $this->getContainer()
            ->get('net.telepay.'.$transaction->getType().'.'.$transaction->getMethod().'.v1')
            ->getPayInStatus($payment_info);

        $transaction->setStatus($payment_info['status']);
        $transaction->setPayInInfo($payment_info);

        return $transaction;
    }

    private function hasExpired($transaction){
        if(isset($transaction->getPayInInfo()['expires_in'])){
            return strtotime($transaction->getPayInInfo()['expires_in']) < time();
        }else{
            return true;
        }

    }
}