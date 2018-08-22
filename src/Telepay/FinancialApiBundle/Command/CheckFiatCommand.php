<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\FeeDeal;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\LimitAdder;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Exchange;
use Telepay\FinancialApiBundle\Financial\Currency;

class CheckFiatCommand extends SyncronizedContainerAwareCommand{
    protected function configure()
    {
        $this
            ->setName('rec:fiat:check')
            ->setDescription('Check fiat transactions')
        ;
    }

    protected function executeSyncronized(InputInterface $input, OutputInterface $output){
        $method_cname = array('lemon_way');
        $type = 'in';

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repoGroup = $em->getRepository('TelepayFinancialApiBundle:Group');
        $repoUser = $em->getRepository('TelepayFinancialApiBundle:User');
        $output->writeln('CHECK FIAT');
        foreach ($method_cname as $method) {
            $output->writeln($method . ' INIT');

            $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('method')->equals($method)
                ->field('type')->equals($type)
                ->field('status')->in(array('created', 'received'))
                ->getQuery();

            foreach ($qb->toArray() as $transaction) {
                $output->writeln('CHECK CRYPTO ID: '.$transaction->getId());
                $data = $transaction->getPayInInfo();
                $output->writeln('CHECK CRYPTO concept: '.$data['concept']);
                $previous_status = $transaction->getStatus();

                $transaction = $this->check($transaction);
                $output->writeln('CHECK CRYPTO status: ' . $transaction->getStatus());
                if ($previous_status != $transaction->getStatus()) {
                    $output->writeln('Notificate init:');
                    $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                    $output->writeln('Notificate end');
                    $transaction->setUpdated(new \DateTime);
                }
                $dm->persist($transaction);
                $dm->flush();

                if ($transaction->getStatus() == Transaction::$STATUS_RECEIVED) {
                    $output->writeln('CHECK CRYPTO received');
                    $amount = $data['amount'];
                    if(isset($data['commerce_id'])) {
                        $commerce_id = $data['commerce_id'];
                        $group_commerce = $repoGroup->find($commerce_id);

                        $id_group_root = $this->getContainer()->getParameter('id_group_root');
                        $group = $repoGroup->find($id_group_root);
                        $id_user_root = $this->getContainer()->getParameter('admin_user_id');
                        $user = $repoUser->find($id_user_root);

                        $request['concept'] = 'Internal exchange';
                        $request['amount'] = $amount * 1000000;
                        $request['address'] = $group_commerce->getRecAddress();
                        $request['pin'] = $user->getPIN();
                        $request['internal_tx'] = '1';
                        $request['destionation_id'] = $transaction->getGroup();

                        $output->writeln('get app');
                        $transactionManager = $this->getContainer()->get('app.incoming_controller');
                        $output->writeln('createTransaction');
                        $response = $transactionManager->createTransaction($request, 1, 'out', 'rec', $id_user_root, $group, '127.0.0.1');
                        $output->writeln('post createTransaction');
                        $output->writeln($response);

                        $status = 'success';
                        $paymentInfo['status'] = $status;
                        $transaction->setStatus($paymentInfo['status']);
                        $transaction->setPayInInfo($paymentInfo);
                    }
                    else{
                        $output->writeln('ERROR: not commerce_id data');
                    }
                }
                elseif ($transaction->getStatus() == Transaction::$STATUS_EXPIRED) {
                    $output->writeln('TRANSACTION EXPIRED');
                    $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
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
        if($transaction->getStatus() === 'success' || $transaction->getStatus() === 'expired'){
            return $transaction;
        }
        $providerName = 'net.telepay.'.$transaction->getType().'.'.$transaction->getMethod().'.v1';
        $cryptoProvider = $this->getContainer()->get($providerName);
        $paymentInfo = $cryptoProvider->getPayInStatus($paymentInfo);
        $transaction->setStatus($paymentInfo['status']);
        $transaction->setPayInInfo($paymentInfo);
        if($transaction->getStatus() === 'created' && $this->hasExpired($transaction)){
            $transaction->setStatus('expired');
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