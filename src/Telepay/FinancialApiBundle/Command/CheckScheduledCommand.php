<?php
namespace Telepay\FinancialApiBundle\Command;

use Doctrine\DBAL\Types\ObjectType;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Entity\Group;

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
            $today = date("j");
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
                    $output->writeln($amount . ' euros de amount deben enviarse');
                    $method = $this->getContainer()->get('net.telepay.out.'.$scheduled->getMethod().'.v1');
                    $group_fee = $this->_getFees($group, $method);
                    $amount = round(($amount * ((100 - $group_fee->getVariable())/100) - $group_fee->getFixed()),0);
                    $amount = 1000;
                    $request = Request::create();
                    $request->request->set('concept', 'test scheduled');
                    $transactionManager = $this->getContainer()->get('app.incoming_controller');
                    $response = $transactionManager->createTransaction($request, 1, 'out', $scheduled->getMethod(), -1, $group);
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