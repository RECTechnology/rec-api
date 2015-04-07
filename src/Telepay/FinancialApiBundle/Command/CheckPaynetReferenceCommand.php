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

                    $creator=$group->getCreator();

                    //luego a la ruleta de admins
                    $dealer=$this->getContainer()->get('net.telepay.commons.fee_deal');
                    $dealer->deal($creator,$amount,$service_cname,$service_currency,$total_fee,$transaction_id);
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

        $status_description=$status['status_description'];

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
                    break;
                case 'Reserved':
                    break;
                case 'Revision':
                    break;
            }
        }else{
            $transaction->setStatus('failed');
        }
        return $transaction;
    }
    private function hasExpired($transaction){
        return strtotime($transaction->getDataOut()['expiration_date']) < time();
    }
}