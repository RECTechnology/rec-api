<?php
namespace App\Command;

use App\Document\Transaction;
use App\Entity\Group;
use App\Entity\User;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckFiatCommand3 extends SynchronizedContainerAwareCommand{
    protected function configure()
    {
        $this
            ->setName('rec:fiatV3:check')
            ->setDescription('Check fiat transactions')
        ;
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output){
        $method_cname = array('lemonway');
        $type = 'in';

        $dm = $this->container->get('doctrine_mongodb.odm.document_manager');
        $em = $this->container->get('doctrine.orm.entity_manager');
        $repoGroup = $em->getRepository(Group::class);
        $repoUser = $em->getRepository(User::class);


        $output->writeln('CHECK FIAT V3');
        foreach ($method_cname as $method) {
            $output->writeln($method . ' INIT');

            $qb = $dm->createQueryBuilder(Transaction::class)
                ->field('method')->equals($method)
                ->field('type')->equals($type)
                ->field('version')->equals(3)
                ->field('status')->in(array('created', 'received'))
                ->limit(6)
                ->getQuery();

            foreach ($qb->toArray() as $transaction) {
                $output->writeln('CHECK FIAT ID: '.$transaction->getId());
                $data = $transaction->getPayInInfo();
                $output->writeln('CHECK FIAT concept: '.$data['concept']);
                $previous_status = $transaction->getStatus();

                $transaction = $this->check($transaction);
                $output->writeln('CHECK FIAT status: ' . $transaction->getStatus());
                if ($previous_status != $transaction->getStatus()) {
                    $output->writeln('Notificate init:');
                    $transaction = $this->container->get('messenger')->notificate($transaction);
                    $output->writeln('Notificate end');
                    $transaction->setUpdated(new \DateTime);
                }
                $dm->persist($transaction);
                $dm->flush();

                if ($transaction->getStatus() === Transaction::$STATUS_RECEIVED) {
                    $output->writeln('CHECK FIAT received');
                    $amount = $data['amount'];
                    if(isset($data['commerce_id'])) {
                        $commerce_id = $data['commerce_id'];
                        $group_commerce = $repoGroup->find($commerce_id);

                        $id_group_root = $this->container->getParameter('id_group_root');
                        $group = $repoGroup->findOneBy(array("id" =>$id_group_root));
                        $id_user_root = $group->getKycManager()->getId();

                        $userAccount = $repoGroup->findOneBy(array("id"=>$transaction->getGroup()));

                        $output->writeln('createTransaction');
                        sleep(1);
                        $txFlowHandler = $this->container->get('net.app.commons.transaction_flow_handler');
                        try{

                            $txFlowHandler->sendRecsWithIntermediary($group, $group_commerce, $userAccount, $amount * 1000000);
                            //$transactionManager->createTransaction($request, 1, 'out', 'rec', $id_user_root, $group, '127.0.0.1');
                            $output->writeln('post createTransaction');
                            sleep(1);
                            $transaction->setStatus(Transaction::$STATUS_SUCCESS);
                            $dm->persist($transaction);
                            $dm->flush();
                            $output->writeln('CHECK FIAT saved in success status');
                            //use $txBonusHandler
                            $txBonusHandler = $this->container->get('net.app.commons.bonus_handler');
                            $txBonusHandler->bonificateTx($transaction);
                        }catch (HttpException $e){
                            $output->writeln($group->getName().' '.$e->getMessage());
                        }
                    }
                    else{
                        $output->writeln('ERROR: not commerce_id data');
                    }
                }
                elseif ($transaction->getStatus() === Transaction::$STATUS_EXPIRED) {
                    $output->writeln('TRANSACTION EXPIRED');
                    $transaction = $this->container->get('messenger')->notificate($transaction);
                }
                $em->flush();
                $dm->persist($transaction);
                $dm->flush();
            }
            $output->writeln($method . ' transactions checked');
        }
        $output->writeln('Fiat transactions finished');
    }

    public function check(Transaction $transaction){
        $paymentInfo = $transaction->getPayInInfo();
        if($transaction->getStatus() === Transaction::$STATUS_SUCCESS || $transaction->getStatus() === Transaction::$STATUS_EXPIRED){
            return $transaction;
        }
        $providerName = 'net.app.'.$transaction->getType().'.'.$transaction->getMethod().'.v1';
        $cryptoProvider = $this->container->get($providerName);
        $paymentInfo = $cryptoProvider->getPayInStatus($paymentInfo);
        $transaction->setStatus($paymentInfo['status']);
        $transaction->setPayInInfo($paymentInfo);
        if($transaction->getStatus() === Transaction::$STATUS_CREATED && $this->hasExpired($transaction)){
            $transaction->setStatus(Transaction::$STATUS_EXPIRED);
        }
        return $transaction;
    }

    private function hasExpired($transaction){
        if (isset($transaction->getPayInInfo()['expires_in'])) {
            return $transaction->getCreated()->getTimestamp() + $transaction->getPayInInfo()['expires_in'] < time();
        }
        return false;
    }
}