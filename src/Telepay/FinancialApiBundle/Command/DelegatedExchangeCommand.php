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
    protected function configure()
    {
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
        $output->writeln('get app');
        $transactionManager = $this->getContainer()->get('app.incoming_controller');

        $csv = $this->parseCSV();
        foreach ($csv as $line) {
            $dni_user = $line[0];
            $cif_commerce = $line[1];
            $amount = intval($line[2])*100;
            /*
                        $request = array();
                        $request['concept'] = 'Internal exchange';
                        $request['amount'] = $amount * 1000000;
                        $request['address'] = $group_commerce->getRecAddress();
                        $request['pin'] = $user->getPIN();
                        $request['internal_tx'] = '1';
                        $request['destionation_id'] = $transaction->getGroup();

                        $output->writeln('createTransaction');
                        sleep(1);
                        $transactionManager->createTransaction($request, 1, 'out', 'rec', $id_user_root, $group, '127.0.0.1');
                        sleep(1);
            */
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