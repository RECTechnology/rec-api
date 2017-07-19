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

class Faircon2NotificationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:faircoin2:notification')
            ->setDescription('Notification for Faircoin 2.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository('TelepayFinancialApiBundle:User');

        $users = $repo->findAll();

        foreach ($users as $user){
            $this->_sendEmail($user->getEmail());
        }

    }

    private function _sendEmail($email){

        $no_replay = $this->getContainer()->getParameter('no_reply_email');

        $message = \Swift_Message::newInstance()
            ->setSubject('Chip Chap Announce Faircoin 2')
            ->setFrom($no_replay)
            ->setTo(array(
                $email
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:fac_fork.html.twig',
                        array()
                    )
            );

        $this->getContainer()->get('mailer')->send($message);
    }

}