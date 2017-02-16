<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/15/14
 * Time: 1:27 PM
 */

namespace Telepay\FinancialApiBundle\Command;

use Swift_Attachment;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Financial\Currency;

class BillGeneratorCommand extends ContainerAwareCommand
{
    public static $cache = array();
    protected function configure()
    {
        $this
            ->setName('telepay:bill:generator')
            ->setDescription('Generate bills every month')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //this command should be executed on 1st of every month
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $qb = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction');

        $em = $this->getContainer()->get('doctrine')->getManager();

        $companies = $em->getRepository('TelepayFinancialApiBundle:Group')->findAll();
        $finish_date = new \DateTime();
        $second_date = new \DateTime();
        $interval = new \DateInterval('P1M');
        $start_date = $second_date->sub($interval);

        $total_bills = 0;

        $exchangeManipulator = $this->getContainer()->get('net.telepay.commons.exchange_manipulator');

        $resume = array();
        $resume_total = 0;

        foreach ($companies as $company){
            $lines = array();
            $output->writeln('Processing company '.$company->getName());
            $total = 0;
            //TODO search fee transactions in this month
            $result = $qb
                ->field('created')->gte($start_date)
                ->field('created')->lte($finish_date)
                ->field('status')->equals(Transaction::$STATUS_SUCCESS)
                ->field('group')->equals($company->getId())
                ->field('type')->equals('fee')
                ->getQuery()
                ->execute();

            if(count($result) > 1){
                foreach($result->toArray() as $transaction){

                    $total_line = $transaction->getTotal();
                    if($transaction->getCurrency() != 'EUR'){
                        if($transaction->getPrice() ){
                            $total_line = round(($transaction->getTotal()/pow(10, $transaction->getScale())) * $transaction->getPrice()/100,2);
                        }else{
                            $total_line = $exchangeManipulator->exchange($transaction->getTotal(), $transaction->getCurrency(), Currency::$EUR);
                            $total_line = $total_line/100;
                        }

                    }

                    $total = $total + $total_line;

                    $fee_info = $transaction->getFeeInfo();
                    $line = array(
                        'amount'    =>  $transaction->getTotal(),
                        'currency'  =>  $transaction->getCurrency(),
                        'method'    =>  $transaction->getMethod(),
                        'variable_fee'  =>  $transaction->getVariableFee(),
                        'fixed_fee'  =>  $transaction->getFixedFee(),
                        'previous_amount'   =>  $fee_info['previous_amount'],
                        'created'   =>  $transaction->getCreated(),
                        'total' =>  $total_line,
                        'price' =>  $transaction->getPrice()/100

                    );

                    $lines[] = $line;

                }

                $single_resume = array(
                    'company'   =>  $company,
                    'total' =>  $total
                );

                $resume_total = $resume_total + $total;

                $output->writeln($total.' total');
                $total_bills ++;
                //send Email
                $user = $company->getKycManager();
                if(!$user){
                    $email = 'noemail@robotunion.org';
                }else{
                    $email = $user->getEmail();
                }
                $this->_sendEmail($lines, $total, $email, $company);

                $resume [] = $single_resume;


            }

        }
        $this->_sendResume($resume, $resume_total);

    }

    private function _sendEmail($lines, $total, $email, $company){
        $body = array(
            'lines' =>  $lines,
            'total' =>  $total,
            'company'   =>  $company
        );
        $html = $this->getContainer()->get('templating')->render('TelepayFinancialApiBundle:Email:bill.html.twig', $body);

        $dir = $this->getContainer()->getParameter('uploads_dir');

        $file = $this->getContainer()->get('knp_snappy.pdf')->generateFromHtml(
            $html,
            $dir.'/bills/'.rand().'_file.pdf'
        );

        $no_replay = $this->getContainer()->getParameter('no_reply_email');

        $message = \Swift_Message::newInstance()
            ->setSubject('Billing')
            ->setFrom($no_replay)
            ->setTo(array(
                'pere@pasproduccions.com'
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:bill.html.twig',
                        $body
                    )
            )
            ->setContentType('text/html')
            ->attach(Swift_Attachment::newInstance($file, rand().'_polla.pdf'));

        $this->getContainer()->get('mailer')->send($message);
    }

    private function _sendResume($resume, $total){
        $body = array(
            'resumes' =>  $resume,
            'total' =>  $total
        );
        $no_replay = $this->getContainer()->getParameter('no_reply_email');

        $message = \Swift_Message::newInstance()
            ->setSubject('Billing Resume')
            ->setFrom($no_replay)
            ->setTo(array(
                'pere@pasproduccions.com'
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:bill_resume.html.twig',
                        $body
                    )
            )
            ->setContentType('text/html');

        $this->getContainer()->get('mailer')->send($message);
    }

}