<?php
namespace Telepay\FinancialApiBundle\Command;

use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Exception\ShellCommandFailureException;
use Telepay\FinancialApiBundle\Entity\TreasureWithdrawalValidation;
use Telepay\FinancialApiBundle\Financial\Currency;

class CentralWithdrawalCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:central:withdrawal')
            ->setDescription('Send recs from central withdrawal')
            ->addOption(
                'amount',
                null,
                InputOption::VALUE_REQUIRED,
                'REC amount to send',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        if($input->getOption('amount')){
            $amount = $input->getOption('amount');
            $amount = intval($amount);
            if($amount>50000){
                $output->writeln("Too big amount");
                exit(0);
            }
            $em = $this->getContainer()->get('doctrine')->getManager();

            $api_url = $this->getContainer()->getParameter('withdrawal_url');
            $list_emails = json_decode($this->getContainer()->getParameter('list_emails'));
            $id = bin2hex(random_bytes(5));
            foreach($list_emails as $email){
                $withdrawal = new TreasureWithdrawalValidation();
                $token = bin2hex(random_bytes(20));
                $withdrawal->setAmount($amount);
                $withdrawal->setToken($token);
                $withdrawal->setTransaction($id);
                $em->persist($withdrawal);
                $em->flush();
                $output->writeln("Withdrawal saved.");
                $link = $api_url . $token;
                $this->sendEmail($email, 'Central account withdrawal', $link);
                $output->writeln("Withdrawal email sent.");
            }
            $output->writeln("All done");
        }
    }

    private function sendEmail($email, $subject, $body){

        $no_replay = $this->getContainer()->getParameter('no_reply_email');

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($no_replay)
            ->setTo(array($email))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:central_withdrawal.html.twig',
                        array(
                            'link' => $body
                        )
                    )
            )
            ->setContentType('text/html');

        $this->getContainer()->get('mailer')->send($message);
    }
}