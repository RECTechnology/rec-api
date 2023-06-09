<?php
namespace App\Command;

use App\Controller\Transactions\IncomingController2;
use App\Document\Transaction;
use App\Entity\Group;
use App\Entity\User;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckFiatCommand extends SynchronizedContainerAwareCommand{
    protected function configure()
    {
        $this
            ->setName('rec:fiat:check')
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

        $output->writeln('get app');

        /** @var IncomingController2 $transactionManager */
        $transactionManager = $this->container->get('app.incoming_controller');

        $output->writeln('CHECK FIAT');
        foreach ($method_cname as $method) {
            $output->writeln($method . ' INIT');

            $qb = $dm->createQueryBuilder(Transaction::class)
                ->field('method')->equals($method)
                ->field('type')->equals($type)
                ->field('version')->equals(1)
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

                if ($transaction->getStatus() == Transaction::$STATUS_RECEIVED) {
                    $output->writeln('CHECK FIAT received');
                    $amount = $data['amount'];
                    if(isset($data['commerce_id'])) {
                        $commerce_id = $data['commerce_id'];
                        $group_commerce = $repoGroup->find($commerce_id);

                        $id_group_root = $this->container->getParameter('id_group_root');
                        $group = $repoGroup->findOneBy(array("id" =>$id_group_root));
                        $id_user_root = $group->getKycManager()->getId();
                        $user = $repoUser->findOneBy(array("id"=>$id_user_root));

                        $request = array();
                        $request['concept'] = 'Internal exchange';
                        $request['amount'] = $amount * 1000000;
                        $request['address'] = $group_commerce->getRecAddress();
                        $request['pin'] = $user->getPIN();
                        $request['internal_tx'] = '1';
                        $request['destionation_id'] = $transaction->getGroup();

                        $output->writeln('createTransaction');
                        sleep(1);
                        $crypto_currency = $this->container->getParameter('crypto_currency');
                        $transactionManager->createTransaction($request, 1, 'out', strtolower($crypto_currency), $id_user_root, $group, '127.0.0.1');
                        $tx_group = $repoGroup->findOneBy(array("id" =>$transaction->getGroup()));
                        $output->writeln('post createTransaction');
                        sleep(1);
                        $transaction->setStatus(Transaction::$STATUS_SUCCESS);
                        $dm->persist($transaction);
                        $dm->flush();
                        $output->writeln('CHECK FIAT saved in success status');
                        $transactionManager->checkCampaign($em, $transaction->getMethod(), $transaction->getAmount(), $transaction->getUser(), $tx_group);
                        $transactionManager->checkRewardCultureCampaign($data, $tx_group, $output);
                    }
                    else{
                        $output->writeln('ERROR: not commerce_id data');
                    }
                }
                elseif ($transaction->getStatus() == Transaction::$STATUS_EXPIRED) {
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