<?php

namespace Telepay\FinancialApiBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

class DelegatedExchangeCommand extends ContainerAwareCommand
{
    protected function configure(){
        $this
            ->setName('rec:delegated:exchange')
            ->setDescription('Delegated exchange')
            ->addOption(
                'dni',
                null,
                InputOption::VALUE_OPTIONAL,
                'Define transaction user.',
                null
            )
            ->addOption(
                'cif',
                null,
                InputOption::VALUE_OPTIONAL,
                'Define transaction commerce cif.',
                null
            )
            ->addOption(
                'amount',
                null,
                InputOption::VALUE_OPTIONAL,
                'Define transaction amount.',
                null
            )
        ;
    }

    private $cvsParsingOptions = array(
        'finder_name' => 'delegated_changes.csv',
        'ignoreFirstRow' => false
    );

    protected function execute(InputInterface $input, OutputInterface $output){
        $em = $this->getContainer()->get('doctrine')->getManager();
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $repoGroup = $em->getRepository('TelepayFinancialApiBundle:Group');
        $repoUser = $em->getRepository('TelepayFinancialApiBundle:User');
        $repoCard = $em->getRepository('TelepayFinancialApiBundle:CreditCard');
        $repoTx = $dm->getRepository('TelepayFinancialApiBundle:Transaction');
        $transactionManager = $this->getContainer()->get('app.incoming_controller');

        $dni_user=$input->getOption('dni');
        if(isset($dni_user)){
            $cif_commerce=$input->getOption('cif');
            $amount=$input->getOption('amount');
            if(!isset($cif_commerce)){
                $output->writeln("Param cif empty");
                exit(0);
            }
            if(!isset($amount)){
                $output->writeln("Param amount empty");
                exit(0);
            }
            $user = $repoUser->findOneBy(array('dni'=>$dni_user));
            if(!$user){
                $output->writeln("User not found: " . $dni_user);
                exit(0);
            }
            $group = $repoGroup->findOneBy(array('cif'=>$dni_user));
            if(!$group){
                $output->writeln("User is not a particular: " . $dni_user);
                exit(0);
            }
            $card = $repoCard->findOneBy(array('user'=>$user->getId(), 'company' => $group->getId()));
            if($card){
                $output->writeln("User with card saved: " . $dni_user);
                exit(0);
            }
            $group_commerce = $repoGroup->findOneBy(array('cif'=>$cif_commerce, 'type' => 'COMPANY'));
            if(!$group_commerce){
                $output->writeln("Commerce not found: " . $cif_commerce);
                exit(0);
            }
            $request = array();
            $request['concept'] = 'Internal exchange';
            $request['amount'] = intval($amount)*100;
            $request['commerce_id'] = $group_commerce->getId();
            $request['save_card'] = 1;
            $response = $transactionManager->createTransaction($request, 1, 'in', 'lemonway', $user->getId(), $group, '127.0.0.1');
            $tx_id = explode("|", $response);
            $tx_id = $tx_id[1];
            $tx = $repoTx->findOneBy(array('id'=>$tx_id));
            $data = $tx->getPayInInfo();
            $output->writeln($data['payment_url']);
            exit(0);
        }

        $csv = $this->parseCSV();
        foreach ($csv as $line) {
            $request = array();

            $dni_user = $line[0];
            $user = $repoUser->findOneBy(array('dni'=>$dni_user));
            if(!$user){
                $output->writeln("User not found: " . $dni_user);
                continue;
            }
            $group = $repoGroup->findOneBy(array('cif'=>$dni_user));
            if(!$group){
                $output->writeln("User is not a particular: " . $dni_user);
                continue;
            }
            $card = $repoCard->findOneBy(array('user'=>$user->getId(), 'company' => $group->getId()));
            if(!$card){
                $output->writeln("User has not a card: " . $dni_user);
                $request['save_card'] = 1;
            }
            else{
                $request['card_id'] = $card->getId();
                $request['pin'] = $user->getPIN();
            }

            $cif_commerce = $line[1];
            $group_commerce = $repoGroup->findOneBy(array('cif'=>$cif_commerce));
            if(!$group_commerce){
                $output->writeln("Commerce not found: " . $cif_commerce);
                continue;
            }
            $amount = intval($line[2])*100;
            $request['concept'] = 'Internal exchange';
            $request['amount'] = $amount;
            $request['commerce_id'] = $group_commerce->getId();

            $output->writeln('createTransaction');
            sleep(1);
            $response = $transactionManager->createTransaction($request, 1, 'in', 'lemonway', $user->getId(), $group, '127.0.0.1');
            sleep(1);
            $output->writeln($dni_user . " => " . $response);
            if($request['save_card']==1) {
                $output->writeln($response['pay_in_info']['payment_url'] . "," . $user->getName() . "," . $line[3] . "," . $line[4] . "," . $line[5] . "," . $line[6]);
            }
        }
        $output->writeln("DONE");
    }

    private function parseCSV(){
        $ignoreFirstRow = $this->cvsParsingOptions['ignoreFirstRow'];
        $finder = new Finder();
        $finder->files()
            ->in($this->getContainer()->getParameter('csv_dir'))
            ->name($this->cvsParsingOptions['finder_name'])
            ->files();
        foreach ($finder as $file) {
            $csv = $file;
        }
        if(empty($csv)){
            throw new \Exception("NO CSV FILE");
        }
        $rows = array();
        if (($handle = fopen($csv->getRealPath(), "r")) !== FALSE) {
            $i = 0;
            while (($data = fgetcsv($handle, null, ",")) !== FALSE) {
                $i++;
                if ($ignoreFirstRow && $i == 1) {
                    continue;
                }
                $rows[] = $data;
            }
            fclose($handle);
        }
        return $rows;
    }

}