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

        $service_cname='paynet_reference';

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repo=$em->getRepository('TelepayFinancialApiBundle:User');

        $qb=$dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('service')->equals($service_cname)
            ->field('status')->in(array('created','received'))
            ->getQuery();

        $resArray = [];
        foreach($qb->toArray() as $res){
            $data=$res->getDataIn();
            $transaction_id=$res->getId();
            $resArray []= $res;

            $check=$this->check($res);
            $dm->flush();
            if($check->getStatus()=='success'){
                //hacemos el reparto
                //primero al user
                $id=$check->getUser();

                $user=$repo->find($id);

                $wallets=$user->getWallets();
                $service_currency = $check->getCurrency();
                $current_wallet=null;
                foreach ( $wallets as $wallet){
                    if ($wallet->getCurrency()==$service_currency){
                        $current_wallet=$wallet;
                    }
                }
                $group=$user->getGroups()[0];

                $amount=$data['amount'];

                if(!$user->hasRole('ROLE_SUPER_ADMIN')){

                    $fixed_fee=$check->getFixedFee();
                    $variable_fee=$check->getVariableFee()*$amount;
                    $total_fee=$fixed_fee+$variable_fee;
                    $total=$amount-$total_fee;

                    $current_wallet->setAvailable($current_wallet->getAvailable()+$total);
                    $current_wallet->setBalance($current_wallet->getBalance()+$total);

                    $em->persist($current_wallet);
                    $em->flush();

                    if($total_fee != 0){
                        // restar las comisiones
                        $feeTransaction=new Transaction();
                        $feeTransaction->setStatus('success');
                        $feeTransaction->setScale($check->getScale());
                        $feeTransaction->setAmount($total_fee);
                        $feeTransaction->setUser($user);
                        $feeTransaction->setCreated(new \MongoDate());
                        $feeTransaction->setTimeOut(new \MongoDate());
                        $feeTransaction->setTimeIn(new \MongoDate());
                        $feeTransaction->setUpdated(new \MongoDate());
                        $feeTransaction->setIp($check->getIp());
                        $feeTransaction->setFixedFee($fixed_fee);
                        $feeTransaction->setVariableFee($variable_fee);
                        $feeTransaction->setVersion($check->getVersion());
                        $feeTransaction->setDataIn(array(
                            'previous_transaction'  =>  $check->getId()
                        ));
                        $feeTransaction->setDebugData(array(
                            'previous_balance'  =>  $current_wallet->getBalance(),
                            'previous_transaction'  =>  $check->getId()
                        ));
                        $feeTransaction->setTotal($total_fee*-1);
                        $feeTransaction->setCurrency($check->getCurrency());
                        $feeTransaction->setService($service_cname);

                        $dm->persist($feeTransaction);
                        $dm->flush();

                        $creator=$group->getCreator();

                        //luego a la ruleta de admins
                        $dealer=$this->getContainer()->get('net.telepay.commons.fee_deal');
                        $dealer->deal($creator,$amount,$service_cname,$service_currency,$total_fee,$transaction_id,$check->getVersion());
                    }

                }else{
                    $current_wallet->setAvailable($current_wallet->getAvailable()+$amount);
                    $current_wallet->setBalance($current_wallet->getBalance()+$amount);

                    $em->persist($current_wallet);
                    $em->flush();
                }
            }

        }

        $dm->flush();

        $output->writeln('Paynet Reference transactions checked');
    }

    public function check(Transaction $transaction){

        if($transaction->getStatus() === 'created' && $this->hasExpired($transaction))
            $transaction->setStatus('expired');

        if($transaction->getStatus() === 'success' || $transaction->getStatus() === 'expired')
            return $transaction;

        $data=$transaction->getData();
        $client_reference=$data['id_paynet'];

        $status=$this->getContainer()->get('net.telepay.provider.paynet_reference')->status($client_reference);

        if(isset($status['status_description'])){
            $status_description = $status['status_description'];
        }else{
            $status_description = 'Cancelled';
        }


        if($status['error_code']==0){
            switch($status_description){
                case 'Authorized':
                    $transaction->setStatus('success');
                    break;
                case 'Cancelled':
                    $transaction->setStatus('cancelled');
                    break;
                case 'Pending':
                    break;
                case 'Printed':
                    break;
                case 'Reversed':
                    $transaction->setStatus('returned');
                    break;
                case 'Reserved':
                    $transaction->setStatus('locked');
                    break;
                case 'Revision':
                    $transaction->setStatus('locked');
                    break;
            }
        }else{
            $transaction->setStatus('failed');
        }
        return $transaction;
    }
    private function hasExpired($transaction){
        if(isset($transaction->getDataOut()['expiration_date'])){
            return strtotime($transaction->getDataOut()['expiration_date']) < time();
        }else{
            return true;
        }

    }
}