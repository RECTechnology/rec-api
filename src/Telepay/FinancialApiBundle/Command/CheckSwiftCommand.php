<?php
namespace Telepay\FinancialApiBundle\Command;

use DateTime;
use Swift_Attachment;
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
            ->addOption(
                'transaction-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Define transaction id.',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $trans_id = $input->getOption('transaction-id');

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository('TelepayFinancialApiBundle:User');

        if(isset($trans_id)){
            $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('type')->equals('swift')
                ->field('id')->equals($trans_id)
                ->getQuery();
        }else{
            $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
                ->field('type')->equals('swift')
                ->field('status')->in(array('created','received'))
                ->getQuery();
        }
        $now = new \DateTime();
        $output->writeln('START QUERY: '.$now->format('d-m-Y:H:i:s'));

        $root_id = $this->getContainer()->getParameter('admin_user_id');
        $root_group_id = $this->getContainer()->getParameter('id_group_root');
        $root = $em->getRepository('TelepayFinancialApiBundle:User')->find($root_id);
        $rootGroup = $em->getRepository('TelepayFinancialApiBundle:Group')->find($root_group_id);

        foreach($qb->toArray() as $transaction){
            if($transaction->getMethodIn() != ''){

                $current_transaction = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->find($transaction->getId());
                if($current_transaction->getStatus() != 'success' && $current_transaction->getStatus() != 'send_locked'){
                    $method_in = $transaction->getMethodIn();
                    $method_out = $transaction->getMethodOut();

                    $now2 = new \DateTime();
                    $output->writeln('is_sent : '.$now2->format('d-m-Y:H:i:s'));
                    $output->writeln('INIT '.$method_in.'-'.$method_out.' -> '.$transaction->getId().' time: '.$now2->format('d-m-Y:H:i:s'));

                    //GET METHODS
                    $cashInMethod = $this->getContainer()->get('net.telepay.in.'.$method_in.'.v1');
                    $cashOutMethod = $this->getContainer()->get('net.telepay.out.'.$method_out.'.v1');

                    $pay_in_info = $transaction->getPayInInfo();
                    $pay_out_info = $transaction->getPayOutInfo();
                    $client = $transaction->getClient();
                    $clientGroup = $this->getContainer()->get('TelepayFinancialApiBundle:Group')->find($client);

                    //get configuration(method)
                    $swift_config = $this->getContainer()->get('net.telepay.config.'.$method_in.'.'.$method_out);
                    $methodFees = $swift_config->getFees();

                    //get client fees (fixed & variable)
                    $clientFees = $em->getRepository('TelepayFinancialApiBundle:SwiftFee')->findOneBy(array(
                        'client'    =>  $client,
                        'cname' =>  $method_in.'-'.$method_out
                    ));
                    if($method_out == 'btc' || $method_out == 'fac'){
                        //Hay que volver a calcular el amount en btc que vamos a enviar y ponerlo en el pay_out_info
                        if($method_in == 'safetypay'){
                            $amount = round($this->_exchange($pay_in_info['amount'], $pay_in_info['currency'], $cashOutMethod->getCurrency()),0);
                        }else{
                            $amount = round($this->_exchange($pay_in_info['amount'], $cashInMethod->getCurrency(), $cashOutMethod->getCurrency()),0);
                        }
                        $client_fee = round(($amount * ($clientFees->getVariable()/100) + $clientFees->getFixed()),0);
                        $service_fee = round(($amount * ($methodFees->getVariable()/100) + $methodFees->getFixed()),0);
                        $final_amount = $amount - $service_fee - $client_fee;
                        $pay_out_info['amount'] = $final_amount;
                        $transaction->setPayOutInfo($pay_out_info);
                        $transaction->setAmount($final_amount);
                        $transaction->setTotal($final_amount);
                        $dm->persist($transaction);
                        $dm->flush();
                    }

                    $amount = $transaction->getAmount();
                    $client_fee = round(($amount * ($clientFees->getVariable()/100) + $clientFees->getFixed()),0);
                    $service_fee = round(($amount * ($methodFees->getVariable()/100) + $methodFees->getFixed()),0);

                    $prevStatusIn = $pay_in_info['status'];
                    $pay_in_info = $cashInMethod->getPayInStatus($pay_in_info);
                    if($method_out == 'halcash_es'){
                        $output->writeln('HALCASH STATUS => '.$transaction->getStatus().' currentSTATUS => '.$current_transaction->getStatus().' pay_in_STATUS => '.$pay_in_info['status'].' pay_out_STATUS => '.$pay_out_info['status']);
                    }

                    if($pay_in_info['status'] == 'created'){
                        //check if hasExpired
                        if($this->hasExpired($transaction)){
                            $transaction->setStatus(Transaction::$STATUS_EXPIRED);
                            $pay_in_info['status'] = Transaction::$STATUS_EXPIRED;
                            $transaction->setPayInInfo($pay_in_info);
                            $transaction->setUpdated(new \DateTime());
                            $dm->persist($transaction);
                            $dm->flush();

                            $clientLimitsCount = $em->getRepository('TelepayFinancialApiBundle:SwiftLimitCount')->findOneBy(array(
                                'client'    =>  $client,
                                'cname' =>  $method_in.'-'.$method_out
                            ));

                            $clientLimitsCount = (new LimitAdder())->restore($clientLimitsCount, $amount + $client_fee + $service_fee);

                            $em->persist($clientLimitsCount);
                            $em->flush();
                        }
                        $output->writeln('NEW STATUS => '.$transaction->getStatus());

                    }elseif($pay_in_info['status'] == 'received'){
                        if($prevStatusIn != $pay_in_info['status']){
                            $transaction->setStatus('received');
                            $transaction->setDataOut($pay_in_info);
                            $transaction->setPayInInfo($pay_in_info);
                            $transaction->setUpdated(new \DateTime());
                            $output->writeln('Status received: CHANGED.');
                            $dm->persist($transaction);
                            $dm->flush();
                        }
                        $output->writeln('NEW STATUS => '.$transaction->getStatus());
                    }elseif($pay_in_info['status'] == 'success'){
                        if($method_in != 'btc' && $method_in != 'fac' ){
                            //sumar balance to statusMethod only when cash_in is distinct to btc or fac
                            $statusMethod = $em->getRepository('TelepayFinancialApiBundle:StatusMethod')->findOneBy(array(
                                'method'    =>  $method_in,
                                'type'  =>  'in'
                            ));

                            if($statusMethod){
                                $balance = $statusMethod->getBalance() + $pay_in_info['amount'];
                                $statusMethod->setBalance($balance);
                                $em->persist($statusMethod);
                                $em->flush();
                            }

                        }
                        $transaction->setPayInInfo($pay_in_info);
                        $transaction->setDataOut($pay_in_info);
                        $transaction->setUpdated(new \DateTime());
                        $dm->persist($transaction);
                        $dm->flush();

                        $current_transaction = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->find($transaction->getId());

                        if($current_transaction->getStatus() != 'success' && $current_transaction->getStatus() != 'send_locked'){

                            $transaction->setStatus('send_locked');
                            $output->writeln('Status send_locked: CHANGED. => '.$transaction->getStatus());
                            $dm->persist($transaction);
                            $dm->flush();

                            try{
                                $pay_out_info = $cashOutMethod->send($pay_out_info);
                                $now3 = new \DateTime();
                                $output->writeln('sending : '.$now3->format('d-m-Y:H:i:s'));
                            }catch (Exception $e){
                                $pay_out_info['status'] = Transaction::$STATUS_FAILED;
                                $pay_out_info['final'] = false;
                                $error = $e->getMessage();
                                $transaction->setPayOutInfo($pay_out_info);
                                $transaction->setStatus('failed');
                            }
                            $transaction->setPayOutInfo($pay_out_info);
                            $dm->persist($transaction);
                            $dm->flush();


                            if($pay_out_info['status'] == 'sent' || $pay_out_info['status'] == 'sending'){
                                $transaction->setPayOutInfo($pay_out_info);
                                if($pay_out_info['status'] == 'sent') $transaction->setStatus('success');
                                else $transaction->setStatus('sending');
                                $transaction->setDataIn($pay_out_info);
                                $output->writeln('Status success: CHANGED.');
                                $dm->persist($transaction);
                                $dm->flush();
                                $current_transaction = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->find($transaction->getId());
                                $output->writeln('Status current transaction: '.$current_transaction->getStatus());

                                //send ticket
                                if($method_out == 'btc' || $method_out == 'fac'){
                                    if( $transaction->getEmailNotification() != ""){
                                        $currency = array(
                                            'btc' => 'BITCOIN',
                                            'fac' => 'FAIRCOIN'
                                        );
                                        $email = $transaction->getEmailNotification();
                                        $ticket = $transaction->getPayInInfo()['reference'];
                                        $ticket = str_replace('BUY BITCOIN ', '', $ticket);
                                        $ticket = str_replace('BUY FAIRCOIN ', '', $ticket);
                                        $body = array(
                                            'reference' =>  $ticket,
                                            'created'   =>  $transaction->getCreated()->format('Y-m-d H:i:s'),
                                            'concept'   =>  'BUY ' . $currency[$method_out] . " " . $ticket,
                                            'amount'    =>  $transaction->getPayInInfo()['amount']/100,
                                            'crypto_amount' => $transaction->getPayOutInfo()['amount']/1e8,
                                            'tx_id'        =>  $transaction->getPayOutInfo()['txid'],
                                            'id'        =>  $ticket,
                                            'address'   =>  $transaction->getPayOutInfo()['address']
                                        );

                                        $this->_sendTicket($body, $email, $ticket, $method_out);
                                    }
                                }else{
                                    //restar al balance correspondiente (halcash, sepa , cryptocapital)
                                    $statusMethod = $em->getRepository('TelepayFinancialApiBundle:StatusMethod')->findOneBy(array(
                                        'method'    =>  $method_in,
                                        'type'  =>  'out'
                                    ));

                                    if($statusMethod){
                                        $balance = $statusMethod->getBalance() - $pay_out_info['amount'] - $service_fee;
                                        if($balance < 0) $balance = 0;
                                        $statusMethod->setBalance($balance);
                                        $em->persist($statusMethod);
                                        $em->flush();
                                    }

                                }
                                //Generate fee transactions. One for the user and one for the root
                                if($pay_out_info['status'] == 'sending'){
                                    //send email in sepa_out
                                    $cashOutMethod->sendMail($transaction->getId(), $transaction->getType(), $pay_out_info);
                                }

                                if($client_fee != 0){
                                    //client fees goes to the user
                                    $userFee = new Transaction();
                                    if($transaction->getUser()) $transaction->setUser($transaction->getUser());
                                    $userFee->setGroup($transaction->getGroup());
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

                                    $group = $em->getRepository('TelepayFinancialApiBundle:Group')->find($transaction->getGroup());
                                    $userWallets = $group->getWallets();
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
                                    //service fees goes to root

                                    $rootFee = new Transaction();
                                    $rootFee->setUser($root->getId());
                                    $rootFee->setGroup($rootGroup->getId());
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
                                        'total_fee' =>  $client_fee + $service_fee,
                                        'previous_group_id'   =>  $clientGroup->getId(),
                                        'previous_group_name'   =>  $clientGroup->getName()
                                    ));
                                    $rootFee->setClient($client);

                                    $dm->persist($rootFee);
                                    //get wallets and add fees to both, user and wallet
                                    $rootWallets = $rootGroup->getWallets();
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
                                $transaction->setStatus(Transaction::$STATUS_FAILED);
                                $output->writeln('Status failed: CHANGED.');
                                $dm->persist($transaction);
                                $dm->flush();
                                //send mail informig the error
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

                    //se ha quitado esto para intentar eviar el double sent de halcash
                    $dm->persist($transaction);
                    $dm->flush();

                }

            }else{
                $transaction->setStatus('error');
                $transaction->setUpdated(new \DateTime());
                $dm->persist($transaction);
                $dm->flush();
            }

        }

        if(isset($trans_id)){
            $output->writeln($trans_id);
        }else{
            $output->writeln(count($qb).' Swift Transactions checked');
        }
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

        $no_replay = $this->getContainer()->getParameter('no_reply_email');

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($no_replay)
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

    private function _sendTicket($body, $email, $ref, $method_out){
        $html = $this->getContainer()->get('templating')->render('TelepayFinancialApiBundle:Email:ticket' . $method_out . '.html.twig', $body);

        $marca = array(
            "btc" => "Chip-Chap",
            "fac" => "Fairtoearth"
        );
        $dompdf = $this->getContainer()->get('slik_dompdf');
        $dompdf->getpdf($html);
        $pdfoutput = $dompdf->output();

        $no_replay = $this->getContainer()->getParameter('no_reply_email');

        $message = \Swift_Message::newInstance()
            ->setSubject($marca[$method_out] . 'Ticket ref: '.$ref)
            ->setFrom($no_replay)
            ->setTo(array(
                $email
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:ticket' . $method_out . '.html.twig',
                        $body
                    )
            )
            ->setContentType('text/html')
            ->attach(Swift_Attachment::newInstance($pdfoutput, $ref.'-'.$body["id"].'.pdf'));

        $this->getContainer()->get('mailer')->send($message);
    }
}