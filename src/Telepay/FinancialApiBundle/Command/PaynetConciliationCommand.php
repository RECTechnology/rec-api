<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/15/14
 * Time: 1:27 PM
 */

namespace Telepay\FinancialApiBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class PaynetConciliationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('paynet:conciliation')
            ->setDescription('Create Paynet Conciliation file')
            ->addArgument('name', InputArgument::OPTIONAL, 'What day do you want consult?')
            ->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start_time = new \MongoDate(time()-1*24*3600);
        $end_time = new \MongoDate(); // now
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $result=$dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('service')->equals(2)
            ->field('mode')->equals(false)
            ->field('completed')->equals(true)
            ->field('timeIn')->gt($start_time)
            ->field('timeIn')->lt($end_time)
            ->getQuery();


        $resArray = [];
        foreach($result->toArray() as $res){
            $resArray []= $res;
        }

        $numTrans=count($resArray);

        $file='/home/pere/sindicato/pagos/integracion-paynet/paynet/doc/conciliacion/MU_TE2'.date('dmY').'.txt';

        $cabecera='HDR|'.date('Ymd');
        $pie='TRL|';
        $reg='REG|';
        $contador=0;
        $total=0;

        $text=$cabecera."\n";

        //TENGO QUE SACAR LA FECHA LA HORA EL CODIGO DE PRODUCTO Y EL NUMERO DE LA AUTORIZACION DE CADA TRANSACCION EXITOSA

        for($i=0;$i<$numTrans;$i++){

            $sent_data=$resArray[$i]->getSentData();
            $sent_data=json_decode($sent_data);
            $date=$sent_data->date;
            $hour=$sent_data->hour;
            $refer=$sent_data->reference;

            $received_data=$resArray[$i]->getReceivedData();
            $received_data=json_decode($received_data);

            $autorization_number=$received_data->autorizacion;
            $referencia=$received_data->referencia[0];
            $amount=$received_data->monto;
            $comision=$sent_data->fee;
            $terminal="0007000010000100001";
            $trans=$sent_data->transaction_id;
            $contador++;

            $total=$total+$amount;

            $text=$text.$reg.$contador."|".$date."|".$hour."|".$refer."|".$autorization_number."|".$referencia."|".$amount."|".$comision."|".$terminal."|".$trans."\n";

        }

        $text=$text.$pie.$contador."|".$total;


        file_put_contents($file,$text);

        $name = $input->getArgument('name');
        if ($name) {
            $resposta = 'Hello '.$name;
        } else {
            $text = 'Hello';
        }

        if ($input->getOption('yell')) {
            $text = strtoupper($text);
        }

        $output->writeln($text);
    }
}