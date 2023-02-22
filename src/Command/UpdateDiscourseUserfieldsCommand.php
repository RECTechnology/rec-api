<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/15/14
 * Time: 1:27 PM
 */

namespace App\Command;

use App\DependencyInjection\Commons\DiscourseApiManager;
use App\Entity\Group;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UpdateDiscourseUserfieldsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:update:discourse:userfields')
            ->setDescription('This command is for fixing discourse userfields which where not save properly. Execute only one time')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var DiscourseApiManager $discourseManager */
        $discourseManager = $this->getContainer()->get('net.app.commons.discourse.api_manager');

        $accounts = $em->getRepository(Group::class)->findBy(array("rezero_b2b_access" => Group::ACCESS_STATE_GRANTED));
        $output->writeln([count($accounts).' accounts found to update', '============================','']);

        $io = new SymfonyStyle($input, $output);
        $io->progressStart(count($accounts));
        $output->writeln("");

        foreach ($accounts as $account){
            $output->writeln("Updating ".$account->getName()." account");
            $data = array('user_fields' => [
                1 => $account->getName(),
                2 => $account->getKycManager()->getName(),
                3 => $account->getId(),
            ]);

            try{
                $response = $discourseManager->bridgeCall($account, 'u/'.$account->getRezeroB2bUsername().'.json', 'PUT', $data, []);
                $output->writeln("Updated");
            }catch (HttpException $e){
                $output->writeln($e->getMessage());
            }
            $io->progressAdvance();
            $output->writeln("");
        }

        $io->success("Command finished successfully");

    }
}