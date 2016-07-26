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
use Telepay\FinancialApiBundle\Entity\UserGroup;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

class MigrationCreateGroupByUserCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:migration:create-groups-by-user')
            ->setDescription('Create new group for each user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $userRepo = $em->getRepository("TelepayFinancialApiBundle:User");
        $users = $userRepo->findAll();

        $id_group_default = $this->getContainer()->getParameter('id_group_default');
        $id_group_level_0 = $this->getContainer()->getParameter('id_group_level_0');
        $id_group_root = $this->getContainer()->getParameter('id_group_root');

        $admin_user_id = $this->getContainer()->getParameter('admin_user_id');

        $groupsRepo = $em->getRepository('TelepayFinancialApiBundle:Group');
        $group_root = $groupsRepo->find($id_group_root);
        $groups = $groupsRepo->findAll();

        $output->writeln('INIT ODISEA MAXIMA');
        $output->writeln('Migrating users');

        $counterGroups = 0;
        $counterFees = 0;
        $counterLimits = 0;
        $counterWallets = 0;
        $counterLimitCounts = 0;
        $counterBalances = 0;
        $counterClients = 0;
        $counterPOS = 0;

        // start and displays the progress bar
        $totalUsers = count($users);
        // create a new progress bar (50 units)
        $progress = new ProgressBar($output, $totalUsers);
        $progress->start();

        foreach ($users as $user) {

            $output->writeln('New User');
            $group = $user->getGroups()[0];

            if($group){
                if($user->getId() != $admin_user_id){
                    $output->writeln('New Group');
                    //creamos un grupo por usuario
                    //TODO los grupos aun cuelgan del user, tienen que colgar del grupo
                    $newGroup = new Group();
                    $creator = $group->getCreator();
//                    $groupCreator = $creator->getGroups()[0];
//                    if($groupCreator == null){
//                        $groupCreator = $group_root;
//                    }
                    $newGroup->setCreator($creator);
//                    $newGroup->setGroupCreator($groupCreator);
                    $newGroup->setName($user->getUsername().' Group');
                    $newGroup->setRoles(array('ROLE_COMPANY'));
                    //set la misma access_key i secret que el user actual
                    //este es un punto critico donde puede petar porque en el constructor ya se generan estos dos parametros
                    //pero nosotros queremos plancharlos con los del user
                    $newGroup->setAccessKey($user->getAccessKey());
                    $newGroup->setAccessSecret($user->getAccessSecret());
                    //copiamos la default currency del user
                    $newGroup->setDefaultCurrency($user->getDefaultCurrency());
                    //añadimos los mismos methods list que el usuario-> dejamos fuera el services_list
                    $newGroup->setMethodsList($user->getMethodsList());
                    $newGroup->setActive(true);

                    $em->persist($newGroup);
                    $em->flush();
                    $counterGroups++;
                    $output->writeln('UserRoles');
                    //Add role_admin to all users to control his company
                    if(!$user->hasRole('ROLE_ADMIN')){
                        $user->addRole('ROLE_ADMIN');
                        $em->persist($user);
                        $em->flush();
                    }

                    //copiamos los fees y limites del grupo original al nuevo grupo
                    //para ello tenemos que conseguir todos los limites i fees asignados a este grupo i clonarlos con el nuevo grupo

                    $fees = $group->getCommissions();
                    $limits = $group->getLimits();
                    $output->writeln('New Fees');
                    foreach($fees as $fee){
                        $newFees = new ServiceFee();
                        $newFees->setCurrency($fee->getCurrency());
                        $newFees->setFixed($fee->getFixed());
                        $newFees->setServiceName($fee->getServiceName());
                        $newFees->setVariable($fee->getVariable());
                        $newFees->setGroup($newGroup);

                        $em->persist($newFees);
                        $em->flush();
                        $counterFees++;
                    }

                    $output->writeln('New limit');
                    foreach($limits as $limit){
                        $newLimit = new LimitDefinition();
                        $newLimit->setCurrency($limit->getCurrency());
                        $newLimit->setCname($limit->getCname());
                        $newLimit->setSingle($limit->getSingle());
                        $newLimit->setDay($limit->getDay());
                        $newLimit->setWeek($limit->getWeek());
                        $newLimit->setMonth($limit->getMonth());
                        $newLimit->setYear($limit->getYear());
                        $newLimit->setTotal($limit->getTotal());
                        $newLimit->setGroup($newGroup);

                        $em->persist($newLimit);
                        $em->flush();

                        $counterLimits++;
                    }

                    //cambiaos al user de grupo
//                    $user->removeGroup($group);
//                    $user->addGroup($newGroup);
                    $output->writeln('User to group');
                    $userGroup = new UserGroup();
                    $userGroup->setUser($user);
                    $userGroup->setGroup($newGroup);
                    $userGroup->setRoles(array('ROLE_ADMIN'));

                    $em->persist($userGroup);
                    $em->flush();
                    $output->writeln('Wallets');
                    //ponemos todos los wallets en el grupo
                    $wallets = $user->getWallets();
                    foreach($wallets as $wallet){
                        $wallet->setGroup($newGroup);
                        $em->persist($wallet);
                        $em->flush();
                        $counterWallets++;
                    }

                    $output->writeln('New counts');
                    //añadimos el grupo a cada limitCounts
                    $limitCounts = $user->getLimitCount();
                    foreach($limitCounts as $limitCount){
                        $limitCount->setGroup($newGroup);
                        $em->persist($limitCount);
                        $em->flush();
                        $counterLimitCounts++;
                    }
                    $output->writeln('New balanec');
//                    //cambiamos los balances
//                    $balances = $user->getBalance();
//                    foreach($balances as $balance){
//                        $balance->setGroup($newGroup);
//                        $em->persist($balance);
//                        $em->flush();
//                        $counterBalances++;
//                    }

                    $output->writeln('New client');
                    //cambiamos los clients
                    $clients = $user->getClients();
                    foreach($clients as $client){
                        $client->setGroup($newGroup);
                        $em->persist($client);
                        $em->flush();
                        $counterClients++;
                    }

                    //cambiamos las tpv
                    $all = $em->getRepository('TelepayFinancialApiBundle:POS')->findBy(array(
                        'user'  =>  $user
                    ));
                    $output->writeln('New tpv');
                    foreach($all as $tpv){
                        $tpv->setGroup($newGroup);
                        $em->persist($tpv);
                        $em->flush();
                        $counterPOS++;
                    }

                }else{
                    $output->writeln('New root');
                    $group_root->setAccessKey($user->getAccessKey());
                    $group_root->setAccessSecret($user->getAccessSecret());
                    //copiamos la default currency del user
                    $group_root->setDefaultCurrency($user->getDefaultCurrency());
                    //añadimos los mismos methods list que el usuario-> dejamos fuera el services_list
                    $group_root->setMethodsList($user->getMethodsList());
                    $group_root->setGroupCreator($group_root);

                    $em->persist($group_root);
                    $em->flush();
                    $output->writeln('New wallets');
                    //ponemos todos los wallets en el grupo
                    $wallets = $user->getWallets();
                    foreach($wallets as $wallet){
                        $wallet->setGroup($group_root);
                        $em->persist($wallet);
                        $em->flush();
                        $counterWallets++;
                    }
                    $output->writeln('New count');
                    //añadimos el grupo a cada limitCounts
                    $limitCounts = $user->getLimitCount();
                    foreach($limitCounts as $limitCount){
                        $limitCount->setGroup($group_root);
                        $em->persist($limitCount);
                        $em->flush();
                        $counterLimitCounts++;
                    }
                    $output->writeln('New balance');
//                    //cambiamos los balances
//                    $balances = $user->getBalance();
//                    foreach($balances as $balance){
//                        $balance->setGroup($group_root);
//                        $em->persist($balance);
//                        $em->flush();
//                        $counterBalances++;
//                    }
                    $output->writeln('New clients');
                    //cambiamos los clients
                    $clients = $user->getClients();
                    foreach($clients as $client){
                        $client->setGroup($group_root);
                        $em->persist($client);
                        $em->flush();
                        $counterClients++;
                    }
                    $output->writeln('New pos');
                    //cambiamos las tpv
                    $all = $em->getRepository('TelepayFinancialApiBundle:POS')->findBy(array(
                        'user'  =>  $user
                    ));

                    foreach($all as $tpv){
                        $tpv->setGroup($group_root);
                        $em->persist($tpv);
                        $em->flush();
                        $counterPOS++;
                    }
                }
            }

            // advance the progress bar 1 unit
            $progress->advance();
        }

        // ensure that the progress bar is at 100%
        $progress->finish();

        $output->writeln('');
        $output->writeln($counterGroups.' Groups created');
        $output->writeln($counterLimits.' Limits created');
        $output->writeln($counterFees.' Fees created');
        $output->writeln($counterWallets.' Wallets changed');
        $output->writeln($counterLimitCounts.' Limit counts changed');
        $output->writeln($counterBalances.' Balances changed');
        $output->writeln($counterClients.' Clients changed');
        $output->writeln($counterPOS.' POS changed');
        $output->writeln(' -------------------------- ');

        $changedGroups = 0;
        foreach($groups as $group){
            $creator = $group->getCreator();
            $groupCreator = $creator->getGroups()[0];
            $group->setGroupCreator($groupCreator);
            $em->persist($group);
            $em->flush();
            $changedGroups++;
        }

        $output->writeln($counterGroups.' Groups changed');

        $output->writeln('All done');
    }


}