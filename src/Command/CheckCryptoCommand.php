<?php
namespace App\Command;

use App\Entity\Group;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stubs\DocumentManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Document\Transaction;

class CheckCryptoCommand extends SynchronizedContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:crypto:check')
            ->setDescription('Check crypto transactions')
        ;
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output){
        $crypto_currency = $this->container->getParameter('crypto_currency');
        $method_cname = [strtolower($crypto_currency)];
        $type = 'in';

        /** @var DocumentManager $dm */
        $dm = $this->container->get('doctrine_mongodb.odm.document_manager');

        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');
        $repoGroup = $em->getRepository(Group::class);
        $repoUser = $em->getRepository(User::class);

        $output->writeln('CHECK CRYPTO');
        foreach ($method_cname as $method) {
            $output->writeln($method . ' INIT');

            $qb = $dm->createQueryBuilder(Transaction::class)
                ->field('method')->equals($method)
                ->field('type')->equals($type)
                ->field('status')->in(['created', 'received'])
                ->getQuery();

            $resArray = [];

            /** @var Transaction $transaction */
            foreach ($qb->toArray() as $transaction) {
                $output->writeln('CHECK CRYPTO ID: '.$transaction->getId());
                $data = $transaction->getPayInInfo();
                $output->writeln('CHECK CRYPTO concept: '.$data['concept']);
                $resArray [] = $transaction;
                $previous_status = $transaction->getStatus();

                $transaction = $this->check($transaction);
                $output->writeln('CHECK CRYPTO status: ' . $transaction->getStatus());
                if ($previous_status != $transaction->getStatus()) {
                    $output->writeln('Notificate init:');
                    $transaction = $this->container->get('messenger')->notificate($transaction);
                    $output->writeln('Notificate end');
                    $transaction->setUpdated(new \DateTime);
                }
                $dm->persist($transaction);
                $dm->flush();

                $groupId = $transaction->getGroup();
                $group = $repoGroup->find($groupId);
                $fixed_fee = $transaction->getFixedFee();
                $variable_fee = $transaction->getVariableFee();
                $total_fee = $fixed_fee + $variable_fee;
                $amount = $data['amount'];
                $total = $amount - $total_fee;

                if ($transaction->getStatus() === Transaction::$STATUS_SUCCESS) {
                    $output->writeln('CHECK CRYPTO success');
                    $service_currency = $transaction->getCurrency();
                    $wallet = $group->getWallet($service_currency);
                    $balancer = $this->container->get('net.app.commons.balance_manipulator');
                    $balancer->addBalance($group, $amount, $transaction, "check crypto command");
                    $wallet->setAvailable($wallet->getAvailable() + $amount);
                    $wallet->setBalance($wallet->getBalance() + $amount);
                    $em->flush();

                    //Enviar recs si es una internal
                    if($transaction->getInternal() && isset($data['destionation_id'])){
                        $commerce_id = $data['destionation_id'];
                        $group_receiver = $repoGroup->find($commerce_id);

                        $id_group_intermediary = $transaction->getGroup();
                        $group = $repoGroup->find($id_group_intermediary);
                        $id_user_intermediary = $transaction->getUser();
                        $user = $repoUser->find($id_user_intermediary);

                        $request = array();
                        $request['concept'] = $data['concept'];
                        $request['amount'] = $amount;
                        $request['address'] = $group_receiver->getRecAddress();
                        $request['pin'] = $user->getPIN();
                        $request['internal_out'] = '1';

                        $output->writeln('get app');
                        $transactionManager = $this->container->get('app.incoming_controller');
                        $output->writeln('createTransaction');
                        $response = $transactionManager->createTransaction($request, 1, 'out', strtolower($crypto_currency), $id_user_intermediary, $group, '127.0.0.1');
                        $output->writeln('post createTransaction');
                        $output->writeln($response);
                    }
                }
                elseif ($transaction->getStatus() === Transaction::$STATUS_EXPIRED) {
                    $output->writeln('TRANSACTION EXPIRED');
                    $output->writeln('NOTIFYING EXPIRED');
                    $transaction = $this->container->get('messenger')->notificate($transaction);
                    $output->writeln('Notificate end');
                    //if delete_on_expire==true delete transaction
                    $paymentInfo = $transaction->getPayInInfo();
                    if ($paymentInfo['received'] === 0 && $transaction->getDeleteOnExpire() === true) {
                        $transaction->setStatus('deleted');
                        $em->flush();
                        $output->writeln('NOTIFYING DELETE ON EXPIRE');
                        $transaction = $this->container->get('messenger')->notificate($transaction);
                        $output->writeln('Notificate end');
                        $output->writeln('DELETE ON EXPIRE');
                    }
                }
                $em->flush();
                $dm->persist($transaction);
                $dm->flush();
            }
            $output->writeln($method . ' transactions checked');
        }
        $output->writeln('Crypto transactions finished');
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