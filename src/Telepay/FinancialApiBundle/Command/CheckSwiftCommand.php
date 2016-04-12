<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
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
                $output->writeln('Checking swift transaction...id=> '. $transaction->getId() . ":" . $transaction->getStatus());
                $output->writeln($transaction->getMethodIn().' - '.$transaction->getMethodOut());
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
                    'cname' =>  $method_in.'-'.$method_out
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
                            'cname' =>  $method_in.'-'.$method_out
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
                    $dm->persist($transaction);
                    $dm->flush();
                }elseif($pay_in_info['status'] == 'success'){
                    $transaction->setPayInInfo($pay_in_info);
                    $transaction->setDataOut($pay_in_info);
                    $transaction->setUpdated(new \DateTime());
                    $dm->persist($transaction);
                    $dm->flush();

                    $current_trasaction = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->find($transaction->getId());

                    if($current_trasaction->getStatus() != 'success' && $current_trasaction->getStatus() != 'send_locked'){

                        $transaction->setStatus('send_locked');
                        $dm->persist($transaction);
                        $dm->flush();
                        $output->writeln('Before send');

                        //if method_out es igual a btc o fac hay que volver a calcular el amount de btc
                        if($method_out == 'btc' || $method_out == 'fac'){
                            $output->writeln('Recalculate price');
                            //Hay que volver a calcular el amount en btc que vamos a enviar y ponerlo en el pay_out_info
                            $crypto_amount = $this->_exchange($pay_in_info['amount'], $cashInMethod->getCurrency(), $cashOutMethod->getCurrency());

                            $client_fee = ($crypto_amount * ($clientFees->getVariable()/100) + $clientFees->getFixed());
                            $service_fee = ($crypto_amount * ($methodFees->getVariable()/100) + $methodFees->getFixed());

                            $final_amount = $crypto_amount - $service_fee - $client_fee;
                            $pay_out_info['amount'] = $final_amount;
                            $transaction->setPayOutInfo($pay_out_info);
                            $transaction->setAmount($final_amount);
                            $transaction->setTotal($final_amount);
                            $dm->persist($transaction);
                            $dm->flush();
                        }
                        $output->writeln('SENDING ...');
                        try{
                            $pay_out_info = $cashOutMethod->send($pay_out_info);
                        }catch (Exception $e){
                            $output->writeln('SENDING ERROR');
                            $output->writeln('catch');
                            $output->writeln($e->getMessage());
                            $pay_out_info['status'] = Transaction::$STATUS_FAILED;
                            $pay_out_info['final'] = false;
                            $error = $e->getMessage();
                            $transaction->setPayOutInfo($pay_out_info);
                            $transaction->setStatus('failed');
                            $output->writeln('Status failed'.$e->getMessage());
                        }
                        $dm->persist($transaction);
                        $dm->flush();

                        $output->writeln($pay_out_info['status']);

                        if($pay_out_info['status'] == 'sent' || $pay_out_info['status'] == 'sending'){
                            $transaction->setPayOutInfo($pay_out_info);
                            if($pay_out_info['status'] == 'sent') $transaction->setStatus('success');
                            else $transaction->setStatus('sending');
                            $transaction->setDataIn($pay_out_info);
                            $output->writeln('Status '.$transaction->getStatus());

                            $dm->persist($transaction);
                            $dm->flush();
                            //Generate fee transactions. One for the user and one for the root
                            $output->writeln('Generating userFee for: '.$transaction->getId());
                            if($pay_out_info['status'] == 'sending'){
                                $output->writeln('Sending email');
                                //send email in sepa_out
                                $this->_sendSepaMail($pay_out_info, $transaction->getId(), $transaction->getType());
                            }

                            if($client_fee != 0){
                                //client fees goes to the user
                                $userFee = new Transaction();
                                $userFee->setUser($transaction->getUser());
                                $userFee->setType('fee');
                                $userFee->setCurrency($transaction->getCurrency());
                                $userFee->setScale($transaction->getScale());
                                $userFee->setAmount($client_fee);
                                $userFee->setFixedFee($clientFees->getFixed());
                                $userFee->setVariableFee($amount * ($clientFees->getVariable()/100));
                                $userFee->setService($method_in.'-'.$method_out);
                                $userFee->setMethod($method_in.'-'.$method_out);
                                $userFee->setStatus('success');
                                $userFee->setTotal($client_fee);
                                $userFee->setDataIn(array(
                                    'previous_transaction'  =>  $transaction->getId(),
                                    'transaction_amount'    =>  $transaction->getAmount(),
                                    'total_fee' =>  $client_fee + $service_fee
                                ));
                                $userFee->setClient($client);
                                $dm->persist($userFee);

                                $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($transaction->getUser());
                                $userWallets = $user->getWallets();
                                $current_wallet = null;

                                foreach ( $userWallets as $wallet){
                                    if ($wallet->getCurrency() == $userFee->getCurrency()){
                                        $current_wallet = $wallet;
                                    }
                                }

                                $current_wallet->setAvailable($current_wallet->getAvailable() + $client_fee);
                                $current_wallet->setBalance($current_wallet->getBalance() + $client_fee);

                                $em->persist($current_wallet);
                                $em->flush();

                            }

                            if($service_fee != 0){
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
                                $rootFee->setService($method_in.'-'.$method_out);
                                $rootFee->setMethod($method_in.'-'.$method_out);
                                $rootFee->setStatus('success');
                                $rootFee->setTotal($service_fee);
                                $rootFee->setDataIn(array(
                                    'previous_transaction'  =>  $transaction->getId(),
                                    'transaction_amount'    =>  $transaction->getAmount(),
                                    'total_fee' =>  $client_fee + $service_fee
                                ));
                                $rootFee->setClient($client);

                                $dm->persist($rootFee);
                                //get wallets and add fees to both, user and wallet
                                $rootWallets = $root->getWallets();
                                $current_wallet = null;

                                foreach ( $rootWallets as $wallet){
                                    if ($wallet->getCurrency() == $rootFee->getCurrency()){
                                        $current_wallet = $wallet;
                                    }
                                }

                                $current_wallet->setAvailable($current_wallet->getAvailable() + $service_fee);
                                $current_wallet->setBalance($current_wallet->getBalance() + $service_fee);

                                $em->persist($current_wallet);
                                $em->flush();
                            }

                            $dm->flush();


                        }else{
                            //TODO send mail informig the error
                            $error = array(
                                'transaction_id'    =>  $transaction->getId(),
                                'type'    =>    $transaction->getType(),
                                'method'    =>  $transaction->getMethodIn().' -> '.$transaction->getMethodOut(),
                                'status'    =>  $transaction->getStatus(),
                                'status_in' =>  $pay_in_info['status'],
                                'status_out'    =>  $pay_out_info['status'],
                                'amount'    =>  $transaction->getAmount(),
                                'error' =>  $error
                            );

                            $this->_sendErrorEmail('Swift error mail', $error);
                        }

                        $dm->flush();

                    }

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

        if($transaction->getMethodIn() == 'paynet_reference'){
            $expires_in = strtotime($transaction->getPayInInfo()['expires_in']);
            $response = $expires_in < time();
        }else{
            $response = $transaction->getCreated()->getTimestamp() + $transaction->getPayInInfo()['expires_in'] < time();
        }
        return $response;

    }

    private function _sendErrorEmail($subject, $body){

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom('no-reply@chip-chap.com')
            ->setTo(array(
                'pere@chip-chap.com',
                'cto@chip-chap.com'
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:error.html.twig',
                        array(
                            'message'        =>  $body
                        )
                    )
            );

        $this->getContainer()->get('mailer')->send($message);
    }

    private function _sendSepaMail($paymentInfo, $id, $type){

        $message = \Swift_Message::newInstance()
            ->setSubject('Sepa_out ALERT')
            ->setFrom('no-reply@chip-chap.com')
            ->setTo(array(
                'cto@chip-chap.com',
                'pere@chip-chap.com'
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:sepa_out_alert.html.twig',array(
                        'id'    =>  $id,
                        'type'  =>  $type,
                        'beneficiary'   =>  $paymentInfo['beneficiary'],
                        'iban'  =>  $paymentInfo['iban'],
                        'amount'    =>  $paymentInfo['amount'],
                        'bic_swift' =>  $paymentInfo['bic_swift'],
                        'concept'   =>  $paymentInfo['concept'],
                        'currency'  =>  $paymentInfo['currency'],
                        'scale'     =>  $paymentInfo['scale'],
                        'final'     =>  $paymentInfo['final'],
                        'status'    =>  $paymentInfo['status']
                    ))
            );

        $this->getContainer()->get('mailer')->send($message);
    }

    private function _exchange($amount,$curr_in,$curr_out){

        $dm = $this->getContainer()->get('doctrine')->getManager();
        $exchangeRepo = $dm->getRepository('TelepayFinancialApiBundle:Exchange');
        $exchange = $exchangeRepo->findOneBy(
            array('src'=>$curr_in,'dst'=>$curr_out),
            array('id'=>'DESC')
        );

        if(!$exchange) throw new HttpException(404,'Exchange not found -> '.$curr_in.' TO '.$curr_out);

        $price = $exchange->getPrice();
        $total = round($amount * $price,0);

        return $total;

    }
}