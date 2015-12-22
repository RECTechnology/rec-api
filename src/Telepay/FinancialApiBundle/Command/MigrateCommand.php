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
            ->setName('telepay:migrate')
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
            $list_services = $this->getContainer()->get('net.telepay.service_provider')->findByRoles($user->getRoles());
            foreach ($list_services as $serv) {
                $results[] = $serv->getCname();
            }
            $user->setServicesList($results);
            $em->persist($user);
            $em->flush();
            $array_de_results[]=json_encode($results);
        }
        $output->writeln('All done');
    }
}