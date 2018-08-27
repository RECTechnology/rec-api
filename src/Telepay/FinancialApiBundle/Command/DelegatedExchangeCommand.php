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
        ;
    }

    private $cvsParsingOptions = array(
        'finder_name' => 'delegated_changes.csv',
        'ignoreFirstRow' => false
    );

    protected function execute(InputInterface $input, OutputInterface $output){
        $em = $this->getContainer()->get('doctrine')->getManager();
        $repoGroup = $em->getRepository('TelepayFinancialApiBundle:Group');
        $repoUser = $em->getRepository('TelepayFinancialApiBundle:User');
        $repoCard = $em->getRepository('TelepayFinancialApiBundle:CreditCard');
        $transactionManager = $this->getContainer()->get('app.incoming_controller');

        $csv = $this->parseCSV();
        foreach ($csv as $line) {
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
                $output->writeln("User has not a card: " . $card);
                continue;
            }

            $cif_commerce = $line[1];
            $group_commerce = $repoGroup->findOneBy(array('cif'=>$cif_commerce));
            if(!$group_commerce){
                $output->writeln("Commerce not found: " . $cif_commerce);
                continue;
            }
            $amount = intval($line[2])*100;
            $request = array();
            $request['concept'] = 'Internal exchange';
            $request['amount'] = $amount;
            $request['commerce_id'] = $group_commerce->getRecAddress();
            $request['card_id'] = $card->getId();
            $request['pin'] = $user->getPIN();

            $output->writeln('createTransaction');
            sleep(1);
            //$transactionManager->createTransaction($request, 1, 'in', 'lemonway', $user->getId(), $group, '127.0.0.1');
            sleep(1);
            $output->writeln($dni_user . " Sent");
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