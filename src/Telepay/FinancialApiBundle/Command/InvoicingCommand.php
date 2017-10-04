<?php
namespace Telepay\FinancialApiBundle\Command;

use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Financial\Currency;

class InvoicingCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:invoice:generator')
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

        $this->start_date = new \MongoDate(strtotime($input->getOption('start_date') .' 00:00:00'));
        $this->finish_date = new \MongoDate(strtotime($input->getOption('finish_date') .' 00:00:00'));

        $monthDate = new \DateTime();
        $month = $monthDate->format('F');

        $em = $this->getContainer()->get('doctrine')->getManager();
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $companies = $em->getRepository('TelepayFinancialApiBundle:Group')->findAll();

        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');

        $resume = array();
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

                    if(count($result) > 1){
                        $fees = array();
                        foreach($result->toArray() as $transaction){
                            $fixed = $transaction->getFixedFee();
                            $variable = $transaction->getVariableFee();

                            $currency = $transaction->getCurrency();

                            $feeInfo = $transaction->getFeeInfo();

                            //TODO calculate % variable fee
                            $variableFee = round(100*$transaction->getAmount()-$fixed/$feeInfo['previous_amount'],1);

                            $amount = $transaction->getAmount();
                            //TODO exchange
                            if($currency!='EUR'){
                                $exchanger = $this->getContainer()->get('net.telepay.commons.exchange_manipulator');
                                $amount = $exchanger->exchange($transaction->getAmount(), $transaction->getCurrency(), Currency::$EUR);
                            }

                            if(isset($fees[$transaction->getMethod()])){
                                // check if fixed and variable are the same
                                $exist = 0;
                                for ($i = 0; $i < count($fees[$transaction->getMethod()]); $i++){

                                    if($fees[$transaction->getMethod()][$i]['fixed'] == $fixed && $fees[$transaction->getMethod()][$i]['variable'] == $variableFee){
                                        //add information
                                        $fees[$transaction->getMethod()][$i]['counter']= $fees[$transaction->getMethod()][$i]['counter'] +1;
                                        $fees[$transaction->getMethod()][$i]['total']   = $fees[$transaction->getMethod()][$i]['total'] + $feeInfo['previous_amount'];
                                        $exist = 1;
                                    }
                                }

                                if($exist == 0){
                                    $information = array(
                                        'fixed' =>  $fixed,
                                        'variable' =>   $variableFee,
                                        'counter'   =>  1,
                                        'total' =>  $feeInfo['previous_amount']
                                    );
                                    $fees[$transaction->getMethod()][] = $information;
                                }

                            }else{
                                $information = array(
                                    'fixed' =>  $fixed,
                                    'variable' =>   $variableFee,
                                    'counter'   =>  1,
                                    'total' =>  $feeInfo['previous_amount']
                                );
                                $fees[$transaction->getMethod()][] = $information;

                            }
//                            die(print_r($fees,true));

                        }


                        $resume[$company->getName()] = $fees;

//                        die(print_r($fees,true));
                        $this->_saveInvoice($company->getName().'_'.$month, $fees);

                    }
                }

            }
        }

    }

    private function _saveInvoice($name, $fees){

        $body = array(
            'fees'  =>  $fees
        );
        $html = $this->getContainer()->get('templating')->render('TelepayFinancialApiBundle:Email:invoice.html.twig', $body);

        $dompdf = $this->getContainer()->get('slik_dompdf');
        $dompdf->getpdf($html);
        $pdfoutput = $dompdf->output();

        $dir = $this->getContainer()->getParameter('uploads_dir');
        file_put_contents($dir.'/prod/pdf_invoices/'.$name.'.pdf', $pdfoutput);


    }
}

