<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
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

class CheckScheduledCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:scheduled:check')
            ->setDescription('Check scheduled transactions and create method out')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $em = $this->getContainer()->get('doctrine')->getManager();
        $scheduledRepo = $em->getRepository("TelepayFinancialApiBundle:Scheduled");
        $scheduleds = $scheduledRepo->findAll();

        foreach ($scheduleds as $scheduled) {
            $today = date("j");
            if ($scheduled->getPeriod() == 0 || $today == "1") {
                $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($scheduled->getUser());
                $userWallets = $user->getWallets();

                $current_wallet = null;
                foreach ($userWallets as $wallet) {
                    if ($wallet->getCurrency() == $scheduled->getWallet()) {
                        $current_wallet = $wallet;
                    }
                }
                if ($current_wallet->getAvailable() > ($scheduled->getMinimum() + $scheduled->getThreshold())) {
                    $amount = $current_wallet->getAvailable() - $scheduled->getThreshold();
                    $output->writeln($amount . ' euros de amount');
                    $amount = 10;

                    $request = array();

                    $response = $this->forward('Telepay\FinancialApiBundle\Controller\Transactions\IncomingController2::make', array(
                        'request'  => $request,
                        'version_number' => '1',
                        'type'   =>  'in',
                        'method'  => $scheduled->getMethod()
                    ));

                    $array_response = json_decode($response->getContent(), true);
                    if($response->getStatusCode() == 200){
                        $output->writeln('Good');
                        //status, ticcket_id, id, address,amount, pin
                        /*
                        $customResponse = array();
                        $customResponse['status'] = 'ok';
                        $customResponse['ticket_id'] = $array_response['pay_out_info']['find_token'];
                        $customResponse['id'] = $array_response['id'];
                        $customResponse['address'] = $array_response['pay_in_info']['address'];
                        $customResponse['amount'] = $array_response['pay_in_info']['amount'];
                        $customResponse['pin'] = $array_response['pay_out_info']['pin'];
                        */

                    }else{
                        $output->writeln('Fail');
                        $output->writeln($array_response);
                    }
                }
            }
        }
    }
}