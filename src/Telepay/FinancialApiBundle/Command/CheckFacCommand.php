<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Exchange;

class CheckFacCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:fac:check')
            ->setDescription('Check fac transactions')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $service_cname='fac_pay';

        //$em= $this->getContainer()->get('doctrine')->getManager();
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();


        //$qb = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->findBy(array('service'=>'paynet_reference'));
        $qb=$dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('service')->equals($service_cname)
            ->field('status')->in(array('created','pending'))
            ->getQuery();

        $resArray = [];
        foreach($qb->toArray() as $res){
            $resArray []= $res;
            $check=$this->check($res);

        }

        die(print_r($resArray,true));

        $dm->flush();

        $output->writeln('Fac transactions checked');
    }

    public function check(Transaction $transaction){

        $currentData = $transaction->getData();

        if($transaction->getStatus() === 'created' && $this->hasExpired($transaction))
            $transaction->setStatus('expired');

        if($transaction->getStatus() === 'success' || $transaction->getStatus() === 'expired')
            return $transaction;

        $address = $currentData['address'];
        $amount = $currentData['amount'];
        $allReceived = $this->getContainer()->get('net.telepay.provider.fac')->listreceivedbyaddress(0, true);
        foreach($allReceived as $cryptoData){
            if($cryptoData['address'] == $address && doubleval($cryptoData['amount'])*1e8 >= $amount){
                $currentData['received'] = doubleval($cryptoData['amount'])*1e8;
                $currentData['confirmations'] = $cryptoData['confirmations'];
                if($currentData['confirmations'] >= $currentData['min_confirmations'])
                    $transaction->setStatus("success");
                else
                    $transaction->setStatus("received");
                $transaction->setData($currentData);
                return $transaction;
            }
        }
        return $transaction;
    }
    private function hasExpired($transaction){
        return $transaction->getTimeIn()->getTimestamp()+$transaction->getData()['expires_in'] < time();
    }
}