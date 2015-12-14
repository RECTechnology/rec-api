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

class CheckSwiftCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:swift:check')
            ->setDescription('Check swift transactions and send method out')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository('TelepayFinancialApiBundle:User');

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('type')->equals('swift')
            ->field('status')->in(array('created','received'))
            ->getQuery();

        $output->writeln(count($qb->toArray()).'... transactions to check');

        $root_id = $this->getContainer()->getParameter('admin_user_id');
        $root = $em->getRepository('TelepayFinancialApiBundle:User')->find($root_id);

        foreach($qb->toArray() as $transaction){
            $output->writeln('nueva transaccion');
            if($transaction->getMethodIn() != ''){
                $output->writeln('Checking swift transaction...');
                $method_in = $transaction->getMethodIn();
                $method_out = $transaction->getMethodOut();

                //GET METHODS
                $cashInMethod = $this->getContainer()->get('net.telepay.in.'.$method_in.'.v1');
                $cashOutMethod = $this->getContainer()->get('net.telepay.out.'.$method_out.'.v1');

                $pay_in_info = $transaction->getPayInInfo();
                $pay_out_info = $transaction->getPayOutInfo();
                $amount = $transaction->getAmount();
                $client = $transaction->getClient();

                $pay_in_info = $cashInMethod->getPayInStatus($pay_in_info);

                //get configuration(method)
                $swift_config = $this->getContainer()->get('net.telepay.config.'.$method_out);
                $methodFees = $swift_config->getFees();

                //get client fees (fixed & variable)
                $clientFees = $em->getRepository('TelepayFinancialApiBundle:SwiftFee')->findOneBy(array(
                    'client'    =>  $client,
                    'cname' =>  $method_in.'_'.$method_out
                ));

                $client_fee = ($amount * ($clientFees->getVariable()/100) + $clientFees->getFixed());
                $service_fee = ($amount * ($methodFees->getVariable()/100) + $methodFees->getFixed());

                if($pay_in_info['status'] == 'created'){
                    //check if hasExpired
                    if($this->hasExpired($transaction)){
                        $transaction->setStatus('expired');
                        $pay_in_info['status'] = 'expired';
                        $transaction->setPayInInfo($pay_in_info);
                        $transaction->setUpdated(new \DateTime());
                        $dm->persist($transaction);
                        $dm->flush();
                        $output->writeln('Status expired');

                        $clientLimitsCount = $em->getRepository('TelepayFinancialApiBundle:SwiftLimitCount')->findOneBy(array(
                            'client'    =>  $client,
                            'cname' =>  $method_in.'_'.$method_out
                        ));

                        $clientLimitsCount = (new LimitAdder())->restore($clientLimitsCount, $amount + $client_fee + $service_fee);

                        $em->persist($clientLimitsCount);
                        $em->flush();
                        $output->writeln('Fees returned');
                    }
                    $output->writeln('Status created: NOT CHANGED.');

                }elseif($pay_in_info['status'] == 'received'){
                    $transaction->setStatus('received');
                    $transaction->setDataOut($pay_in_info);
                    $transaction->setPayInInfo($pay_in_info);
                    $transaction->setUpdated(new \DateTime());
                    $output->writeln('Status '.$pay_in_info['status']);
                }elseif($pay_in_info['status'] == 'success'){
                    $transaction->setPayInInfo($pay_in_info);
                    $transaction->setDataOut($pay_in_info);
                    $transaction->setUpdated(new \DateTime());
                    try{
                        $pay_out_info = $cashOutMethod->send($pay_out_info);
                    }catch (HttpException $e){
                        $transaction->setPayOutInfo($pay_out_info);
                        $transaction->setStatus('error');
                        $output->writeln('Status failed');
                    }

                    if($pay_out_info['status'] == 'sent'){
                        $transaction->setPayOutInfo($pay_out_info);
                        $transaction->setStatus('success');
                        $transaction->setDataIn($pay_out_info);
                        $output->writeln('Status success');

                        //Generate fee transactions. One for the user and one for the root
                        $output->writeln('Generating userFee for: '.$transaction->getId());

                        //client fees goes to the user
                        $userFee = new Transaction();
                        $userFee->setUser($transaction->getUser());
                        $userFee->setType('fee');
                        $userFee->setCurrency($transaction->getCurrency());
                        $userFee->setScale($transaction->getScale());
                        $userFee->setAmount($client_fee);
                        $userFee->setFixedFee($clientFees->getFixed());
                        $userFee->setVariableFee($amount * ($clientFees->getVariable()/100));
                        $userFee->setService($method_in.'_'.$method_out);
                        $userFee->setStatus('success');
                        $userFee->setTotal($client_fee);
                        $userFee->setDataIn(array(
                            'previous_transaction'  =>  $transaction->getId(),
                            'transaction_amount'    =>  $transaction->getAmount(),
                            'total_fee' =>  $client_fee + $service_fee
                        ));
                        $userFee->setClient($client);

                        $output->writeln('Generating rootFee for: '.$transaction->getId());
                        //service fees goes to root
                        $rootFee = new Transaction();
                        $rootFee->setUser($root->getId());
                        $rootFee->setType('fee');
                        $rootFee->setCurrency($transaction->getCurrency());
                        $rootFee->setScale($transaction->getScale());
                        $rootFee->setAmount($service_fee);
                        $rootFee->setFixedFee($methodFees->getFixed());
                        $rootFee->setVariableFee($amount * ($methodFees->getVariable()/100));
                        $rootFee->setService($method_in.'_'.$method_out);
                        $rootFee->setStatus('success');
                        $rootFee->setTotal($service_fee);
                        $rootFee->setDataIn(array(
                            'previous_transaction'  =>  $transaction->getId(),
                            'transaction_amount'    =>  $transaction->getAmount(),
                            'total_fee' =>  $client_fee + $service_fee
                        ));
                        $rootFee->setClient($client);
                        $dm->persist($userFee);
                        $dm->persist($rootFee);

                        //if status out == pending we have to send the transaction manually
                    }elseif($pay_out_info['status'] == 'pending'){
                        $transaction->setPayOutInfo($pay_out_info);
                        $transaction->setStatus('pending_send');
                        $transaction->setDataIn($pay_out_info);
                        $output->writeln('Status pending_send');
                    }

                    $dm->flush();

                }

                $dm->persist($transaction);
                $dm->flush();

            }else{
                $transaction->setStatus('error');
                $transaction->setUpdated(new \DateTime());
                $dm->persist($transaction);
                $dm->flush();
                $output->writeln('Bad values in transaction '.$transaction->getId());
            }

        }

        $output->writeln('Swift transactions checked');
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