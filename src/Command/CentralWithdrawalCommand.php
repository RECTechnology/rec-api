<?php
namespace App\Command;

use App\DependencyInjection\Commons\MailerAwareTrait;
use App\Entity\TreasureWithdrawalValidation;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Exception\ShellCommandFailureException;
use Symfony\Component\Mime\Email;

class CentralWithdrawalCommand extends ContainerAwareCommand
{
    use MailerAwareTrait;
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
            $em = $this->getContainer()->get('doctrine.orm.entity_manager');

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

        $message = (new Email())
            ->subject($subject)
            ->from($no_replay)
            ->to($email)
            ->html(
                $this->getContainer()->get('templating')
                    ->render('Email/central_withdrawal.html.twig',
                        array(
                            'link' => $body
                        )
                    )
            );

        $this->mailer->send($message);
    }
}