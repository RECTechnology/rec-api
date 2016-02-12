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
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

class MigrateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:migrate:services')
            ->setDescription('Migrate services')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $userRepo = $em->getRepository("TelepayFinancialApiBundle:User");
        $users = $userRepo->findAll();
        $array_de_results = array();
        foreach ($users as $user) {
            $results = array();
            $services = $user->getServicesList();
            $methods = $this->_convert($services);

            $user->setMethodsList($methods);
            $em->persist($user);
            $em->flush();
            $array_de_results[]=json_encode($results);
        }
        $output->writeln('All done');
    }

    private function _convert($services){

        $methods = array();

        foreach($services as $service){
            switch ($service){
                case 'paynet_reference':
                    $methods[] = 'paynet_reference-in';
                    break;
                case 'halcash_send':
                    $methods[] = 'halcash_es-out';
                    $methods[] = 'halcash_pl-out';
                    break;
                case 'btc_pay':
                    $methods[] = 'btc-in';
                    break;
                case 'btc_send':
                    $methods[] = 'btc-out';
                    break;
                case 'sepa_in':
                    $methods[] = 'sepa-in';
                    break;
                case 'sepa_out':
                    $methods[] = 'sepa-out';
                    break;
                case 'cryptocapital':
                    $methods[] = 'cryptocapital-out';
                    break;
                case 'fac_pay':
                    $methods[] = 'fac-in';
                    break;
                case 'fac_send':
                    $methods[] = 'fac-out';
                    break;
                default:
                    break;
            }

        }

        return $methods;

    }
}