<?php
namespace Telepay\FinancialApiBundle\Command;

use Doctrine\DBAL\Types\ObjectType;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\ServiceFee;

class CheckScheduledCommand extends ContainerAwareCommand{
    protected function configure(){
        $this
            ->setName('telepay:scheduled:check')
            ->setDescription('Check scheduled transactions and create method out')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $em = $this->getContainer()->get('doctrine')->getManager();
        $scheduledRepo = $em->getRepository("TelepayFinancialApiBundle:Scheduled");
        $scheduleds = $scheduledRepo->findAll();

        foreach ($scheduleds as $scheduled) {

            //period 2 -> monthly
            if($scheduled->getPeriod() == 2){
                $today = date("N");
            }
            //period 1 -> weekly
            elseif($scheduled->getPeriod() == 1){
                $today = date("j");
            }
            //period 0 -> daily
            else{
                $today = "0";
            }

            if ($scheduled->getPeriod() == 0 || $today == "1") {
                $group = $em->getRepository('TelepayFinancialApiBundle:Group')->find($scheduled->getGroup());
                $groupWallets = $group->getWallets();

                $current_wallet = null;
                foreach ($groupWallets as $wallet) {
                    if ($wallet->getCurrency() == $scheduled->getWallet()) {
                        $current_wallet = $wallet;
                    }
                }
                if ($current_wallet->getAvailable() > ($scheduled->getMinimum() + $scheduled->getThreshold())) {
                    $amount = $current_wallet->getAvailable() - $scheduled->getThreshold();
                    if($scheduled->getMaximum() > 0 && $amount > $scheduled->getMaximum()){
                        $amount = $scheduled->getMaximum();
                    }
                    $output->writeln($amount . ' euros de amount deben enviarse');
                    $method = $this->getContainer()->get('net.telepay.out.'.$scheduled->getMethod().'.v1');
                    $output->writeln('get fees');
                    $group_fee = $this->_getFees($group, $method);
                    $amount = round(($amount * ((100 - $group_fee->getVariable())/100) - $group_fee->getFixed()),0);
                    if($scheduled->getMethod() == 'sepa'){
                        $data = json_decode($scheduled->getInfo(), true);
                        $request['concept'] = $data['concept'];
                        $request['amount'] = $amount;
                        $request['beneficiary'] = $data['beneficiary'];
                        $request['iban'] = $data['iban'];
                        $request['bic_swift'] = $data['swift'];
                    }
                    $output->writeln('get app');
                    $transactionManager = $this->getContainer()->get('app.incoming_controller');
                    $output->writeln('createTransaction');
                    $response = $transactionManager->createTransaction($request, 1, 'out', $scheduled->getMethod(), -1, $group, '127.0.0.1');
                    $output->writeln('post createTransaction');
                    $output->writeln($response);
                }
            }
        }
        $output->writeln('All done');
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