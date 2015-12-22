<?php
namespace Telepay\FinancialApiBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UpdateGroupsFeesLimitsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:groups:fees-limits:update')
            ->setDescription('Update fees and limits by actives services.')
            ->addOption(
                'superadmin',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Set username superadmin.',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $em = $this->getContainer()->get('doctrine')->getManager();

        //select all the groups
        $groupsRepo = $em->getRepository('TelepayFinancialApiBundle:Group');
        $groups = $groupsRepo->findAll();

        $superadmin = $input->getOption('superadmin');

        $superuser = $em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
            'username'  =>  $superadmin
        ));

        $countSuperCreator = 0;
        $countDeletedLimits = 0;
        $countDeletedFees = 0;
        $countDeletedServices = 0;

        //foreach group
        foreach($groups as $group){
            //select creator
            $creator = $group->getCreator();
            if(!$creator){
                //if creator doesn't exist set superadmin like creator.
                $group->setCreator($superuser);
                $em->persist($group);
                $em->flush();
                $countSuperCreator ++;
                $creator = $group->getCreator();

            }
            //select active services from creator
            $groupLimits = $group->getLimits();
            $groupFees = $group->getCommissions();

            $services = $creator->getServicesList();
            //limits of services not allowed must be deleted
            foreach($groupLimits as $limit){
                if(!in_array($limit->getCname(),$services)){
                    $em->remove($limit);
                    $em->flush();
                    $countDeletedLimits ++;
                }
            }
            //fees of services not allowed must be deleted
            foreach($groupFees as $fee){
                if(!in_array($fee->getServiceName(),$services)){
                    $em->remove($fee);
                    $em->flush();
                    $countDeletedFees ++;
                }
            }

            //check all users of this groups has the appropiate services actives
            $groupUsers = $group->getUsers();
            if(count($groupUsers) > 0){
                foreach($groupUsers as $groupUser){
                    foreach($groupUser->getServicesList() as $list){
                        if(!in_array($list, $services)){
                            $groupUser->removeService($list);
                            $em->persist($groupUser);
                            $em->flush();
                            $countDeletedServices ++;
                        }
                    }
                }
            }

        }

        $output->writeln('Limits deleted: '.$countDeletedLimits);
        $output->writeln('Fees deleted: '.$countDeletedFees);
        $output->writeln('Services deleted: '.$countDeletedServices);
        $output->writeln('Groups to superadmin: '.$countSuperCreator);

    }


}