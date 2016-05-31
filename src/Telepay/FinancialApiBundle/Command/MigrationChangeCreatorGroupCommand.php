<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/15/14
 * Time: 1:27 PM
 */

namespace Telepay\FinancialApiBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Entity\User;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

class MigrationChangeCreatorGroupCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:migration:change-creator-group')
            ->setDescription('Change creator foreach group')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        $groupsRepo = $em->getRepository('TelepayFinancialApiBundle:Group');
        $groups = $groupsRepo->findAll();

        $output->writeln('INIT ODISEA MAXIMA');
        $output->writeln('Migrating users');

        $changedGroups = 0;
        foreach($groups as $group){
            $creator = $group->getCreator();
            $groupCreator = $creator->getGroups()[0];
            $group->setGroupCreator($groupCreator);
            $em->persist($group);
            $em->flush();
            $changedGroups++;
        }

        $output->writeln($changedGroups.' Groups changed');

        $output->writeln('All done');
    }


}