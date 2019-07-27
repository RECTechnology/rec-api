<?php
namespace App\FinancialApiBundle\Command;

use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Financial\Currency;

class InvoicingCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:invoice:generator')
            ->setDescription('Generate invoices by month')
            ->addOption(
                'start_date',
                null,
                InputOption::VALUE_REQUIRED,
                'Define the start date',
                null
            )
            ->addOption(
                'finish_date',
                null,
                InputOption::VALUE_REQUIRED,
                'Define the finish date',
                null
            )
        ;
    }

    public $start_date;
    public $finish_date;

    protected function execute(InputInterface $input, OutputInterface $output) {

        $from = $input->getOption('start_date');
        $to = $input->getOption('finish_date');

        $this->start_date = new \MongoDate(strtotime($input->getOption('start_date') .' 00:00:00'));
        $this->finish_date = new \MongoDate(strtotime($input->getOption('finish_date') .' 00:00:00'));

        $monthDate = new \DateTime($from);
        $month = $monthDate->format('F');

        $em = $this->getContainer()->get('doctrine')->getManager();
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $companies = $em->getRepository('FinancialApiBundle:Group')->findAll();

        $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction');

        //$methods = $this->getContainer()->get('net.app.method_provider')->findAll();
        //$swidtMethods = $this->getContainer()->get('net.app.swift_provider')->findAll();

        $resume = array();
        $resumeSwift = array();
        $resumeReseller = array();
        foreach($companies as $company){
            if($company->getName() != 'riit'){
                if($company->hasRole('ROLE_COMPANY')){
                    //search all success fees transactions by group
//                die(print_r($company->getName(),true));
                    $result = $qb
                        ->field('updated')->gte($this->start_date)
                        ->field('updated')->lte($this->finish_date)
                        ->field('status')->equals(Transaction::$STATUS_SUCCESS)
                        ->field('group')->equals($company->getId())
                        ->field('type')->equals(Transaction::$TYPE_FEE)
                        ->getQuery()
                        ->execute();

//                    die(print_r(count($result),true));
                    $isBotc = '';
                    if($company->getTier() == 10) $isBotc = 'BotC_';
                    if(count($result) >= 1){
                        $fees = array();
                        $feesSwift = array();
                        $feesReseller = array();
                        foreach($result->toArray() as $transaction){

                            //TODO detect if is method or swift
                            $isSwift = 0;
                            if($this->getContainer()->get('net.app.swift_provider')->isValidMethod($transaction->getMethod())) $isSwift = 1;

                            // is feeseller?
                            $isResellerFee = 0;
                            $isResta = 0;
                            if($transaction->getData()){
                                if($transaction->getData()['type'] == 'suma_amount'){
                                    $isResellerFee = 1;
                                } elseif($transaction->getData()['type'] == 'resta_amount'){
                                    $isResta = 1;
                                }
                            }
                            $fixed = $transaction->getFixedFee();
                            $variable = $transaction->getVariableFee();

                            $currency = $transaction->getCurrency();

                            $feeInfo = $transaction->getFeeInfo();

                            //TODO calculate % variable fee
                            $variableFee = round(100*($transaction->getAmount()-$fixed)/$feeInfo['previous_amount'],1);

                            $prev_amount = $feeInfo['previous_amount'];
                            //TODO exchange
                            if($currency!='EUR'){
                                $prev_amount = round(($prev_amount/pow(10,$transaction->getScale())) * $transaction->getPrice(),2);
                                $fixed = round(($fixed/pow(10,$transaction->getScale())) * $transaction->getPrice(),2);
                            }

                            if($isResellerFee){
                                $feesReseller = $this->_addRecord($transaction, $feesReseller, $fixed, $variableFee, $prev_amount);
                            }elseif($isSwift){
                                $feesSwift = $this->_addRecord($transaction, $feesSwift, $fixed, $variableFee, $prev_amount);
                            }elseif($isResta){

                            }else{
                                $fees = $this->_addRecord($transaction, $fees, $fixed, $variableFee, $prev_amount);

                            }

                        }

                        if(count($feesReseller) > 0){
                            $resumeReseller[$company->getName()] = $feesReseller;
                            $this->_saveInvoice($isBotc.'Reseller_'.$company->getName().'_'.$month, $feesReseller, $company->getName(), $from, $to);
                        }

                        if(count($feesSwift) > 0){
                            $resumeSwift[$company->getName()] = $feesSwift;
                            $this->_saveInvoice($isBotc.'Swift_'.$company->getName().'_'.$month, $feesSwift, $company->getName(), $from, $to);
                        }

                        if(count($fees) > 0){
                            $resume[$company->getName()] = $fees;
                            $this->_saveInvoice($isBotc.$company->getName().'_'.$month, $fees, $company->getName(), $from , $to);
                        }

                    }
                }

            }
        }

    }

    private function _saveInvoice($name, $fees, $company, $from, $to){

        $body = array(
            'fees'  =>  $fees,
            'company'   =>  $company,
            'from'  =>  $from,
            'to'  =>  $to
        );
        $html = $this->getContainer()->get('templating')->render('FinancialApiBundle:Email:invoice.html.twig', $body);

        $dompdf = $this->getContainer()->get('slik_dompdf');
        $dompdf->getpdf($html);
        $pdfoutput = $dompdf->output();

        $dir = $this->getContainer()->getParameter('uploads_dir');
        file_put_contents($dir.'/prod/pdf_invoices/'.$name.'.pdf', $pdfoutput);


    }

    private function _addRecord(Transaction $transaction, $fees, $fixed, $variableFee, $prev_amount){
        if(isset($fees[$transaction->getMethod()])){
            // check if fixed and variable are the same
            $exist = 0;
            for ($i = 0; $i < count($fees[$transaction->getMethod()]); $i++){

                if($fees[$transaction->getMethod()][$i]['fixed'] == $fixed && $fees[$transaction->getMethod()][$i]['variable'] == $variableFee){
                    //add information
                    $fees[$transaction->getMethod()][$i]['counter']= $fees[$transaction->getMethod()][$i]['counter'] +1;
                    $fees[$transaction->getMethod()][$i]['total']   = $fees[$transaction->getMethod()][$i]['total'] + $prev_amount;
                    $exist = 1;
                }
            }

            if($exist == 0){
                $information = array(
                    'fixed' =>  $fixed,
                    'variable' =>   $variableFee,
                    'counter'   =>  1,
                    'total' =>  $prev_amount
                );
                $fees[$transaction->getMethod()][] = $information;
            }

        }else{
            $information = array(
                'fixed' =>  $fixed,
                'variable' =>   $variableFee,
                'counter'   =>  1,
                'total' =>  $prev_amount
            );
            $fees[$transaction->getMethod()][] = $information;

        }

        return $fees;
    }
}

