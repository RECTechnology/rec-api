<?php

namespace App\Command;


use App\Entity\CreditCard;
use App\Entity\Group;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use App\Entity\UserWallet;
use App\Financial\Currency;

class DelegatedExchangeCommand extends ContainerAwareCommand
{
    protected function configure(){
        $this
            ->setName('rec:delegated:changes')
            ->setDescription('Delegated change')
        ;
    }

    private $cvsParsingOptions = array(
        'finder_name' => 'delegated_changes.csv',
        'ignoreFirstRow' => false
    );

    protected function execute(InputInterface $input, OutputInterface $output){
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repoGroup = $em->getRepository(Group::class);
        $repoUser = $em->getRepository(User::class);
        $repoCard = $em->getRepository(CreditCard::class);
        $transactionManager = $this->getContainer()->get('app.incoming_controller');

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
            $card = $repoCard->findOneBy(array(
                    'user'=>$user->getId(),
                    'deleted'=>false,
                    'company' => $group->getId())
            );
            if(!$card){
                $output->writeln("User has not a card: " . $dni_user);
                continue;
                $request['save_card'] = 1;
            }
            else{
                $request['card_id'] = $card->getId();
                $pin = $user->getPIN();
                if(intval($pin)<1)$pin = '0000';
                $request['pin'] = $pin;
            }

            $cif_commerce = $line[1];
            $group_commerce = $repoGroup->findOneBy(array(
                'cif'=>$cif_commerce,
                'type' => 'COMPANY'
            ));
            if(!$group_commerce){
                $output->writeln("Commerce not found: " . $cif_commerce);
                continue;
            }
            $amount = intval($line[2]);
            $request['concept'] = 'Internal exchange';
            $request['amount'] = $amount;
            $request['commerce_id'] = $group_commerce->getId();

            $output->writeln('createTransaction');
            sleep(1);
            $response = $transactionManager->createTransaction($request, 1, 'in', 'lemonway', $user->getId(), $group, '127.0.0.1');
            sleep(1);
            $output->writeln($dni_user . " => " . $response);
            if (strpos($response, 'received') !== false) {
                //send money to commerce in lemonway account
                $output->writeln('lemonway -> envio euros a => '. $group_commerce->getCIF());
                $sentInfo = array(
                    'to' => $group_commerce->getCIF(),
                    'amount' => number_format($amount/100, 2)
                );
                $providerName = 'net.app.out.lemonway.v1';
                $lemonMethod = $this->getContainer()->get($providerName);
                $resultado = $lemonMethod->send($sentInfo);
                $output->writeln('lemonway -> eur resultado => '. json_encode($resultado));
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