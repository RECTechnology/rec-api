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

class CheckHalcashCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:halcash:check')
            ->setDescription('Check halcash transactions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $service_cname='halcash_send';

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repo=$em->getRepository('TelepayFinancialApiBundle:User');

        $qb=$dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('service')->equals($service_cname)
            ->field('status')->in(array('created','received','failed','review'))
            ->getQuery();

        $resArray = [];
        foreach($qb->toArray() as $res){
            $data=$res->getDataIn();
            $resArray [] = $res;

            $check=$this->check($res);
            $dm->flush();
            if($check->getStatus()=='success'){

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

                $amount=$data['amount'];

                if(!$user->hasRole('ROLE_SUPER_ADMIN')){

                    $fixed_fee = $check->getFixedFee();
                    $variable_fee = $check->getVariableFee();
                    $total_fee = $fixed_fee + $variable_fee;
                    $total = $amount + $total_fee;

                    $current_wallet->setBalance($current_wallet->getBalance()-$total);

                    $em->persist($current_wallet);
                    $em->flush();

                }else{
                    $current_wallet->setBalance($current_wallet->getBalance()-$amount);

                    $em->persist($current_wallet);
                    $em->flush();
                }
            }

        }

        $dm->flush();

        $output->writeln('Halcash send transactions checked');
    }

    public function check(Transaction $transaction){

        $ticket = $transaction->getDataOut()['halcashticket'];

        $status=$this->getContainer()->get('net.telepay.provider.halcash')->status($ticket);

        if($status['errorcode']==0){

            switch($status['estadoticket']){
                case 'Autorizada':
                    $transaction->setStatus('created');
                    break;
                case 'Preautorizada':
                    $transaction->setStatus('created');
                    break;
                case 'Anulada':
                    $transaction->setStatus('cancelled');
                    break;
                case 'BloqueadaPorCaducidad':
                    $transaction->setStatus('expired');
                    break;
                case 'BloqueadaPorReintentos':
                    $transaction->setStatus('failed');
                    break;
                case 'Devuelta':
                    $transaction->setStatus('returned');
                    break;
                case 'Dispuesta':
                    $transaction->setStatus('success');
                    break;
                case 'EstadoDesconocido':
                    $transaction->setStatus('unknown');
                    break;
            }

        }

        return $transaction;
    }

}