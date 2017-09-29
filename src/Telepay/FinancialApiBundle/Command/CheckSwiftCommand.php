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
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\LimitAdder;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Financial\Currency;

class CheckSwiftCommand extends SyncronizedContainerAwareCommand
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

    protected function executeSyncronized(InputInterface $input, OutputInterface $output)
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
                if($current_transaction->getStatus() != Transaction::$STATUS_SUCCESS && $current_transaction->getStatus() != 'send_locked'){
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
                    $actualClient = $em->getRepository('TelepayFinancialApiBundle:Client')->find($client);
                    $clientGroup = $actualClient->getGroup();

                    //get configuration(method)
                    $swift_config = $this->getContainer()->get('net.telepay.config.'.$method_in.'.'.$method_out);
                    $methodFees = $swift_config->getFees();

                    $output->writeln('FIXED FEE => '.$methodFees->getFixed());
                    $output->writeln('VARIABLE FEE => '.$methodFees->getVariable());

                    //get client fees (fixed & variable)
                    $clientFees = $em->getRepository('TelepayFinancialApiBundle:SwiftFee')->findOneBy(array(
                        'client'    =>  $client,
                        'cname' =>  $method_in.'-'.$method_out
                    ));
                    if($method_out == 'btc' || $method_out == 'fac' || $method_out == 'eth' || $method_out == 'crea'){
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
                        $price = round($final_amount/($pay_in_info['amount']/1e8),0);
                        $transaction->setPrice($price);
                        $dm->flush();
                    }

                    $amount = $transaction->getAmount();
                    $client_fee = round(($amount * ($clientFees->getVariable()/100) + $clientFees->getFixed()),0);
                    $service_fee = round(($amount * ($methodFees->getVariable()/100) + $methodFees->getFixed()),0);

                    $prevStatusIn = $pay_in_info['status'];
                    $prevConfirmationsIn = 0;
                    if(isset($pay_in_info['confirmations'])){
                        $prevConfirmationsIn = $pay_in_info['confirmations'];
                    }
                    $pay_in_info = $cashInMethod->getPayInStatus($pay_in_info);
                    if($method_out == 'halcash_es'){
                        $output->writeln('HALCASH STATUS => '.$transaction->getStatus().' currentSTATUS => '.$current_transaction->getStatus().' pay_in_STATUS => '.$pay_in_info['status'].' pay_out_STATUS => '.$pay_out_info['status']);
                    }

                    if($pay_in_info['status'] == Transaction::$STATUS_CREATED){
                        //check if hasExpired
                        if($this->hasExpired($transaction)){
                            $transaction->setStatus(Transaction::$STATUS_EXPIRED);
                            $pay_in_info['status'] = Transaction::$STATUS_EXPIRED;
                            $transaction->setPayInInfo($pay_in_info);
                            $transaction->setUpdated(new \DateTime());
                            $dm->persist($transaction);
                            $dm->flush();

                            $output->writeln('Notificate init:' . $pay_in_info['status']);
                            $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                            $output->writeln('Notificate end');

                            $clientLimitsCount = $em->getRepository('TelepayFinancialApiBundle:SwiftLimitCount')->findOneBy(array(
                                'client'    =>  $client,
                                'cname' =>  $method_in.'-'.$method_out
                            ));

                            $clientLimitsCount = (new LimitAdder())->restore($clientLimitsCount, $amount + $client_fee + $service_fee);

                            $em->persist($clientLimitsCount);
                            $em->flush();
                        }
                        $output->writeln('NEW STATUS => '.$transaction->getStatus());

                    }elseif($pay_in_info['status'] == Transaction::$STATUS_RECEIVED){
                        if($prevStatusIn != $pay_in_info['status']){
                            $transaction->setStatus(Transaction::$STATUS_RECEIVED);
                            $transaction->setDataOut($pay_in_info);
                            $transaction->setPayInInfo($pay_in_info);
                            $transaction->setUpdated(new \DateTime());
                            $output->writeln('Status received: CHANGED.');
                            $dm->persist($transaction);
                            $dm->flush();

                            $output->writeln('Notificate init:' . $pay_in_info['status']);
                            $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                            $output->writeln('Notificate end');
                        }
                        if(isset($pay_in_info['confirmations']) && $pay_in_info['confirmations']>$prevConfirmationsIn){
                            $transaction->setDataOut($pay_in_info);
                            $transaction->setPayInInfo($pay_in_info);
                            $transaction->setUpdated(new \DateTime());
                            $dm->persist($transaction);
                            $dm->flush();
                        }
                        $output->writeln('NEW STATUS => '.$transaction->getStatus());
                    }elseif($pay_in_info['status'] == Transaction::$STATUS_SUCCESS){
                        if($method_in == 'btc' || $method_in == 'fac' || $method_in == 'eth' || $method_in == 'crea'){
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

                            $output->writeln('Notificate init:' .  $transaction->getStatus());
                            $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                            $output->writeln('Notificate end');

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

                                $output->writeln('Notificate init:' .  $transaction->getStatus());
                                $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                                $output->writeln('Notificate end');
                            }
                            $transaction->setPayOutInfo($pay_out_info);
                            $dm->persist($transaction);
                            $dm->flush();


                            if($pay_out_info['status'] == Transaction::$STATUS_SENT || $pay_out_info['status'] == 'sending'){
                                $transaction->setPayOutInfo($pay_out_info);
                                if($pay_out_info['status'] == Transaction::$STATUS_SENT) $transaction->setStatus(Transaction::$STATUS_SUCCESS);
                                else $transaction->setStatus('sending');
                                $transaction->setDataIn($pay_out_info);
                                $output->writeln('Status success: CHANGED.');
                                $dm->persist($transaction);
                                $dm->flush();

                                $output->writeln('Notificate init:' .  $transaction->getStatus());
                                $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                                $output->writeln('Notificate end');

                                $current_transaction = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->find($transaction->getId());
                                $output->writeln('Status current transaction: '.$current_transaction->getStatus());

                                //send ticket
                                if($method_out == 'btc' || $method_out == 'fac' || $method_out == 'eth' || $method_out == 'crea'){
                                    if( $transaction->getEmailNotification() != ""){
                                        $currency = array(
                                            'btc' => 'BITCOIN',
                                            'fac' => 'FAIRCOIN',
                                            'eth' => 'ETHEREUM',
                                            'crea' => 'CREATIVECOIN'
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

                                $output->writeln('FEE client => '.$client_fee);
                                $output->writeln('FEE service => '.$service_fee);
                                $price = 100;
                                if($transaction->getCurrency() != Currency::$EUR){
                                    $price = $this->_getPrice($transaction->getCurrency());
                                }
                                $balancer = $this->getContainer()->get('net.telepay.commons.balance_manipulator');
                                if($client_fee != 0){
                                    //client fees goes to the user
                                    $userFee = new Transaction();
                                    if($transaction->getUser()) $transaction->setUser($transaction->getUser());
                                    $userFee->setGroup($transaction->getGroup());
                                    $userFee->setType(Transaction::$TYPE_FEE);
                                    $userFee->setCurrency($transaction->getCurrency());
                                    $userFee->setScale($transaction->getScale());
                                    $userFee->setAmount($client_fee);
                                    $userFee->setFixedFee($clientFees->getFixed());
                                    $userFee->setVariableFee($amount * ($clientFees->getVariable()/100));
                                    $userFee->setService($method_in.'-'.$method_out);
                                    $userFee->setMethod($method_in.'-'.$method_out);
                                    $userFee->setStatus(Transaction::$STATUS_SUCCESS);
                                    $userFee->setTotal($client_fee);
                                    $userFee->setDataIn(array(
                                        'previous_transaction'  =>  $transaction->getId(),
                                        'transaction_amount'    =>  $transaction->getAmount(),
                                        'total_fee' =>  $client_fee + $service_fee
                                    ));
                                    $userFeeInfo = array(
                                        'previous_transaction'  =>  $transaction->getId(),
                                        'previous_amount'   =>  $transaction->getAmount(),
                                        'amount'                =>  $client_fee,
                                        'currency'      =>  $transaction->getCurrency(),
                                        'scale'     =>  $transaction->getScale(),
                                        'concept'           =>  $method_in.'-'.$method_out.'->fee',
                                        'status'    =>  Transaction::$STATUS_SUCCESS
                                    );
                                    $userFee->setFeeInfo($userFeeInfo);
                                    $userFee->setClient($client);
                                    $userFee->setPrice($price);
                                    $dm->persist($userFee);

                                    $group = $em->getRepository('TelepayFinancialApiBundle:Group')->find($transaction->getGroup());
                                    $userWallet = $group->getWallet($userFee->getCurrency());

                                    $userWallet->setAvailable($userWallet->getAvailable() + $client_fee);
                                    $userWallet->setBalance($userWallet->getBalance() + $client_fee);

                                    $em->persist($userWallet);
                                    $em->flush();

                                    $userCompany = $em->getRepository('TelepayFinancialApiBundle:Group')->find($transaction->getGroup());
                                    $balancer->addBalance($userCompany, $client_fee, $transaction);
                                }

                                if($service_fee != 0){
                                    //service fees goes to root

                                    $rootFee = new Transaction();
                                    $rootFee->setUser($root->getId());
                                    $rootFee->setGroup($rootGroup->getId());
                                    $rootFee->setType(Transaction::$TYPE_FEE);
                                    $rootFee->setCurrency($transaction->getCurrency());
                                    $rootFee->setScale($transaction->getScale());
                                    $rootFee->setAmount($service_fee);
                                    $rootFee->setFixedFee($methodFees->getFixed());
                                    $rootFee->setVariableFee($amount * ($methodFees->getVariable()/100));
                                    $rootFee->setService($method_in.'-'.$method_out);
                                    $rootFee->setMethod($method_in.'-'.$method_out);
                                    $rootFee->setStatus(Transaction::$STATUS_SUCCESS);
                                    $rootFee->setTotal($service_fee);
                                    $rootFee->setDataIn(array(
                                        'previous_transaction'  =>  $transaction->getId(),
                                        'transaction_amount'    =>  $transaction->getAmount(),
                                        'total_fee' =>  $client_fee + $service_fee,
                                        'previous_group_id'   =>  $clientGroup->getId(),
                                        'previous_group_name'   =>  $clientGroup->getName()
                                    ));

                                    $serviceFeeInfo = array(
                                        'previous_transaction'  =>  $transaction->getId(),
                                        'previous_amount'   =>  $transaction->getAmount(),
                                        'amount'                =>  $service_fee,
                                        'currency'      =>  $transaction->getCurrency(),
                                        'scale'     =>  $transaction->getScale(),
                                        'concept'           =>  $method_in.'-'.$method_out.'->fee',
                                        'status'    =>  Transaction::$STATUS_SUCCESS,
                                        'previous_group_id'   =>  $clientGroup->getId(),
                                        'previous_group_name'   =>  $clientGroup->getName(),
                                    );
                                    $rootFee->setFeeInfo($serviceFeeInfo);
                                    $rootFee->setClient($client);
                                    $rootFee->setPrice($price);

                                    $dm->persist($rootFee);
                                    //get wallets and add fees to both, user and wallet
                                    $rootWallet = $rootGroup->getWallet($rootFee->getCurrency());

                                    $rootWallet->setAvailable($rootWallet->getAvailable() + $service_fee);
                                    $rootWallet->setBalance($rootWallet->getBalance() + $service_fee);

                                    $em->persist($rootWallet);
                                    $em->flush();

                                    $balancer->addBalance($rootGroup, $service_fee, $transaction);
                                }

                                //quitamos saldo a los nodos de faircoop con un exchange
                                if($transaction->getFaircoopNode() && $transaction->getFaircoopNode()>0){
                                    $amount_to_exchange = $transaction->getAmount();
                                    $faircoopNode = $transaction->getFaircoopNode();
                                    $faircoopNode = $em->getRepository('TelepayFinancialApiBundle:Group')->find($faircoopNode);
                                    $from_ex = $cashOutMethod->getCurrency();
                                    $to_ex = $cashInMethod->getCurrency();
                                    $user_fair_id = $this->getContainer()->getParameter('admin_user_id_fac');
                                    $user_fair = $em->getRepository('TelepayFinancialApiBundle:User')->find($user_fair_id);
                                    $exchangeManipulator = $this->getContainer()->get('net.telepay.commons.exchange_manipulator');
                                    $exchangeManipulator->doExchange($amount_to_exchange, $from_ex, $to_ex, $faircoopNode, $user_fair, true);
                                }

                                $dm->flush();

                            }else{
                                $transaction->setStatus(Transaction::$STATUS_FAILED);
                                $output->writeln('Status failed: CHANGED.');
                                $dm->persist($transaction);
                                $dm->flush();

                                $output->writeln('Notificate init:' .  $transaction->getStatus());
                                $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                                $output->writeln('Notificate end');

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

                $output->writeln('Notificate init:' .  $transaction->getStatus());
                $transaction = $this->getContainer()->get('notificator')->notificate($transaction);
                $output->writeln('Notificate end');
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
            "eth" => "Chip-Chap",
            "crea" => "Chip-Chap",
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

    private function _getPrice($currency){
        //get price for this currency
        $exchanger = $this->getContainer()->get('net.telepay.commons.exchange_manipulator');
        $price = 1;
        if($currency != Currency::$EUR){
            $price = $exchanger->exchange(pow(10, Currency::$SCALE[$currency]), $currency, 'EUR');
        }

        return $price;
    }
}