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

class CheckCryptoCommand extends SyncronizedContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:crypto:check')
            ->setDescription('Check crypto transactions')
        ;
    }

    protected function executeSyncronized(InputInterface $input, OutputInterface $output){
        $method_cname = array('rec');
        $type = 'in';

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repoGroup = $em->getRepository('TelepayFinancialApiBundle:Group');
        $output->writeln('CHECK CRYPTO');
        foreach ($method_cname as $method) {
            $output->writeln($method . ' INIT');

            $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('method')->equals($method)
                ->field('type')->equals($type)
                ->field('status')->in(array('created', 'received'))
                ->getQuery();

            $resArray = [];
            foreach ($qb->toArray() as $transaction) {
                $output->writeln('CHECK CRYPTO ID: '.$transaction->getId());
                $data = $transaction->getPayInInfo();
                $output->writeln('CHECK CRYPTO concept: '.$data['concept']);
                if (isset($data['expires_in'])) {

                    $resArray [] = $transaction;
                    $previous_status = $transaction->getStatus();

                    $transaction = $this->check($transaction);
                    $output->writeln('CHECK CRYPTO status: '.$transaction->getStatus());
                    if ($previous_status != $transaction->getStatus()) {
                        $output->writeln('Notificate init:');
                        $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                        $output->writeln('Notificate end');
                        $transaction->setUpdated(new \DateTime);
                    }

                    $dm->flush();

                    $groupId = $transaction->getGroup();
                    $group = $repoGroup->find($groupId);

                    $fixed_fee = $transaction->getFixedFee();
                    $variable_fee = $transaction->getVariableFee();
                    $total_fee = $fixed_fee + $variable_fee;
                    $amount = $data['amount'];
                    $total = $amount - $total_fee;

                    if ($transaction->getStatus() == Transaction::$STATUS_SUCCESS) {
                        $output->writeln('CHECK CRYPTO success');
                        $service_currency = $transaction->getCurrency();
                        $wallet = $group->getWallet($service_currency);
                        $balancer = $this->getContainer()->get('net.telepay.commons.balance_manipulator');
                        $balancer->addBalance($group, $amount, $transaction, "check crypto command");
                        $wallet->setAvailable($wallet->getAvailable() + $amount);
                        $wallet->setBalance($wallet->getBalance() + $amount);
                        $em->flush();
                    }
                    elseif ($transaction->getStatus() == Transaction::$STATUS_EXPIRED) {
                        $output->writeln('TRANSACTION EXPIRED');

                        $output->writeln('NOTIFYING EXPIRED');
                        $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                        $output->writeln('Notificate end');
                        //if delete_on_expire==true delete transaction
                        $paymentInfo = $transaction->getPayInInfo();
                        if ($transaction->getDeleteOnExpire() == true && $paymentInfo['received'] == 0) {
                            $transaction->setStatus('deleted');
                            $em->flush();
                            $output->writeln('NOTIFYING DELETE ON EXPIRE');
                            $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                            $output->writeln('Notificate end');
                            $output->writeln('DELETE ON EXPIRE');
                            $dm->flush();
                        }
                    }
                }
            }
            $output->writeln($method . ' transactions checked');
        }
        $dm->flush();
        $output->writeln('Crypto transactions finished');
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

        return $transaction->getCreated()->getTimestamp() + $transaction->getPayInInfo()['expires_in'] < time();

    }

    private function sendEmail($subject, $body){

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom('no-reply@chip-chap.com')
            ->setTo(array(
                'pere@chip-chap.com'
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:support.html.twig',
                        array(
                            'message'        =>  $body
                        )
                    )
            );

        $this->getContainer()->get('mailer')->send($message);
    }
}