<?php
namespace App\FinancialApiBundle\Command;

use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\FinancialApiBundle\Document\Transaction;

class TransactionReportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:transaction:report')
            ->setDescription('Print all transactions details')
            ->addOption(
                'start_date',
                null,
                InputOption::VALUE_REQUIRED,
                'Define the start date to print the transactions (Yesterday by default) mm/dd/yyyy.',
                null
            )
            ->addOption(
                'finish_date',
                null,
                InputOption::VALUE_REQUIRED,
                'Define the finish date to print the transactions (Yesterday by default) mm/dd/yyyy.',
                null
            )
        ;
    }

    public $start_date;
    public $finish_date;

    protected function execute(InputInterface $input, OutputInterface $output) {
        if($input->getOption('start_date')){
            $this->start_date = new \MongoDate(strtotime($input->getOption('start_date') .' 00:00:00'));
        }
        else{
            $this->start_date = new \MongoDate(strtotime('-1 day 00:00:00'));
        }

        if($input->getOption('finish_date')){
            $this->finish_date = new \MongoDate(strtotime($input->getOption('finish_date') .' 23:59:59'));
        }
        else{
            $this->finish_date = new \MongoDate(strtotime('-1 day 23:59:59'));
        }

        $list_groups = array("0" => "NO group");
        $em = $this->getContainer()->get('doctrine')->getManager();
        $companies = $em->getRepository('FinancialApiBundle:Group')->findAll();
        foreach($companies as $company){
            $list_groups[$company->getId()] = $company->getName();
        }

        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $qb = $dm->createQueryBuilder('FinancialApiBundle:Transaction');

        $result = $qb
            ->field('updated')->gte($this->start_date)
            ->field('updated')->lte($this->finish_date)
            ->field('status')->equals(Transaction::$STATUS_SUCCESS)
            ->getQuery()
            ->execute();

        if(count($result) > 1){
            $output->writeln("Id;Group_id;Group_name;Type;Method;Status;IN_amount;IN_currency;IN_info;IN_subinfo;OUT_amount;OUT_currency;OUT_info;OUT_subinfo;FEE_amount;FEE_currency;FEE_fixed;FEE_variable;Price;Created;Updated");
            foreach($result->toArray() as $transaction){
                $method = $transaction->getMethod()!=""?$transaction->getMethod():"#";
                if ($method == "#") $method = $transaction->getService()!=""?$transaction->getService():"#";
                //$output->writeln($transaction->getId() . " --- " . $transaction->getType() . " --- " . $method);
                //falten els wallet_to_wallet
                if($method=="wallet_to_wallet" || $method=="POS-SABADELL" || $method=="POS-BTC-virtual" || $method == "POS" || $method == "POS-FAC-virtual"){
                    //todo
                }
                else{
                    $id = $transaction->getId()!=""?$transaction->getId():"#";
                    $type = $transaction->getType()!=""?$transaction->getType():"#";
                    $status = $transaction->getStatus()!=""?$transaction->getStatus():"#";
                    $group_id = $transaction->getGroup()!=""?$transaction->getGroup():"0";
                    $group_name = $list_groups[$group_id];
                    $in_scale = isset($transaction->getPayInInfo()['scale'])?$transaction->getPayInInfo()['scale']:0;
                    $in_amount = isset($transaction->getPayInInfo()['amount'])?$transaction->getPayInInfo()['amount']/(pow(10, $in_scale)):"#";
                    $in_currency = isset($transaction->getPayInInfo()['currency'])?$transaction->getPayInInfo()['currency']:"#";

                    $in_info = "#";
                    $in_subinfo = "#";
                    $out_info = "#";
                    $out_subinfo = "#";

                    if($type == "fee") {
                        $in_info = $transaction->getFeeInfo()['previous_transaction'];
                    }
                    elseif($method == "btc-halcash_es" || $method == "fac-halcash_es" || $method == "btc-halcash_pl" || $method == "fac-halcash_pl"){
                        $in_info = $transaction->getPayInInfo()['address'];
                        //$in_subinfo = $transaction->getPayInInfo()['txid'];
                        $out_info = "+" . $transaction->getPayOutInfo()['prefix'] . " " .$transaction->getPayOutInfo()['phone'];
                        $out_subinfo = $transaction->getPayOutInfo()['halcashticket'];
                    }
                    elseif($method == "sepa-btc"){
                        $in_info = $transaction->getPayInInfo()['iban'];
                        $out_info = $transaction->getPayOutInfo()['address'];
                        $out_subinfo = $transaction->getPayOutInfo()['txid'];
                    }
                    elseif($method == "btc-sepa"){
                        $in_info = $transaction->getPayInInfo()['address'];
                        //$in_subinfo = $transaction->getPayInInfo()['txid'];
                        $out_info = $transaction->getPayOutInfo()['iban'];
                    }
                    elseif($method == "easypay-btc"){
                        $in_info = $transaction->getPayInInfo()['account'];
                        $in_subinfo = $transaction->getPayInInfo()['reference'];
                        $out_info = $transaction->getPayOutInfo()['address'];
                        $out_subinfo = isset($transaction->getPayOutInfo()['txid'])?$transaction->getPayOutInfo()['txid']:"#";
                    }
                    elseif($method == "btc-cryptocapital"){
                        $in_info = $transaction->getPayInInfo()['address'];
                        //$in_subinfo = $transaction->getPayInInfo()['txid'];
                        $out_info = $transaction->getPayOutInfo()['email'];
                    }
                    elseif($method == "teleingreso-btc" || $method == "teleingreso_usa-btc"){
                        $in_info = $transaction->getPayInInfo()['teleingreso_id'];
                        $in_subinfo = $transaction->getPayInInfo()['charge_id'];
                        $out_info = $transaction->getPayOutInfo()['address'];
                        $out_subinfo = $transaction->getPayOutInfo()['txid'];
                    }
                    elseif($method == "paynet_reference-btc"){
                        $in_info = $transaction->getPayInInfo()['paynet_id'];
                        $in_subinfo = $transaction->getPayInInfo()['barcode'];
                        $out_info = $transaction->getPayOutInfo()['address'];
                        $out_subinfo = $transaction->getPayOutInfo()['txid'];
                    }
                    elseif(($method == "halcash_es" || $method == "halcash_pl") && $type == "out") {
                        $out_info = "+" . $transaction->getPayOutInfo()['prefix'] . " " .$transaction->getPayOutInfo()['phone'];
                        $out_subinfo = $transaction->getPayOutInfo()['halcashticket'];
                    }
                    elseif($method == "sepa" && $type == "out") {
                        $out_info = $transaction->getPayOutInfo()['iban'];
                        $out_subinfo = $transaction->getPayOutInfo()['bic_swift'];
                    }
                    elseif($method == "sepa" && $type == "in") {
                        $in_info = $transaction->getPayInInfo()['iban'];
                        //TODO: millor el hash
                        $in_subinfo = $transaction->getPayInInfo()['reference'];
                    }
                    elseif($method == "easypay" && $type == "in") {
                        $in_info = $transaction->getPayInInfo()['account'];
                        //TODO: millor el hash
                        $in_subinfo = $transaction->getPayInInfo()['reference'];
                    }
                    elseif(($method == "btc" || $method == "fac") && $type == "out") {
                        $out_info = $transaction->getPayOutInfo()['address'];
                        $out_subinfo = $transaction->getPayOutInfo()['txid'];
                    }
                    elseif(($method == "btc" || $method == "fac") && $type == "in") {
                        $in_info = $transaction->getPayInInfo()['address'];
                        $in_subinfo = isset($transaction->getPayInInfo()['txid'])?$transaction->getPayInInfo()['txid']:"#";
                    }
                    elseif (strpos($method, 'exchange_') !== false) {
                        $in_info = $transaction->getPayInInfo()['currency'];
                        $out_info = $transaction->getPayOutInfo()['currency'];
                    }
                    else{
                        $output->writeln($type . " --- " .$method);
                        exit(-1);
                    }

                    $out_scale = isset($transaction->getPayOutInfo()['scale'])?$transaction->getPayOutInfo()['scale']:0;
                    $out_amount = isset($transaction->getPayOutInfo()['amount'])?$transaction->getPayOutInfo()['amount']/(pow(10, $out_scale)):"#";
                    $out_currency = isset($transaction->getPayOutInfo()['currency'])?$transaction->getPayOutInfo()['currency']:"#";

                    if(!empty($transaction->getFeeInfo())){
                        $fee_scale = $transaction->getScale()!=""?$transaction->getScale():0;
                        $fee_fixed = $transaction->getFixedFee()!=""?$transaction->getFixedFee()/pow(10, $fee_scale):"0";
                        $fee_variable = $transaction->getVariableFee()!=""?$transaction->getVariableFee()/pow(10, $fee_scale):"0";
                        $fee_amount = $transaction->getAmount()!=""?$transaction->getAmount()/pow(10, $fee_scale):"#";
                        $fee_currency = $transaction->getCurrency()!=""?$transaction->getCurrency():"#";
                    }
                    else{
                        $fee_fixed = $transaction->getFixedFee()!=""?$transaction->getFixedFee():"0";
                        $fee_variable = $transaction->getVariableFee()!=""?$transaction->getVariableFee():"0";
                        $fee_amount = 0;
                        $fee_currency = $out_currency;
                    }

                    $price = $transaction->getPrice()!=""?$transaction->getPrice()/(pow(10, $out_scale)):"#";
                    if($out_currency == 'BTC' && $price>0){
                        $price = pow(10, $in_scale+4)/$price;
                    }
                    $created = $transaction->getCreated()!=""?$transaction->getCreated():"#";
                    $updated = $transaction->getUpdated()!=""?$transaction->getUpdated():"#";


                    $output->writeln(
                        $id . ";" .
                        $group_id . ";" .
                        $group_name . ";" .
                        $type . ";" .
                        $method . ";" .
                        $status . ";" .
                        $in_amount . ";" .
                        $in_currency . ";" .
                        $in_info . ";" .
                        $in_subinfo . ";" .
                        $out_amount . ";" .
                        $out_currency . ";" .
                        $out_info . ";" .
                        $out_subinfo . ";" .
                        $fee_amount . ";" .
                        $fee_currency . ";" .
                        $fee_fixed . ";" .
                        $fee_variable . ";" .
                        $price . ";" .
                        $created->format('Y-m-d H:i:s') . ";" .
                        $updated->format('Y-m-d H:i:s')
                    );
                }
            }
        }
    }
}

