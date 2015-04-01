<?php
namespace Telepay\FinancialApiBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\FeeDeal;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Exchange;
use Telepay\FinancialApiBundle\Entity\Group;

class AddGroupCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('telepay:add:group')
            ->setDescription('Add users to group default')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $em = $this->getContainer()->get('doctrine')->getManager();
        $repo=$em->getRepository('TelepayFinancialApiBundle:User');
        $repoGroup=$em->getRepository('TelepayFinancialApiBundle:Group');
        $users=$repo->findAll();

        $group=$repoGroup->findOneBy(array(
            'name'  =>  'Default'
        ));

        $creator=0;

        if(!$group){
            $users=$repo->findAll();

            foreach ( $users as $user ){
                if($user->hasRole('ROLE_SUPER_ADMIN')){
                    $creator=$user;
                }
            }

            $group=new Group();
            $group->setName('Default');
            $group->setRoles(array('ROLE_USER'));
            $group->setCreator($creator);
            $em->persist($group);
            $em->flush();

        }

        $contador=0;

        foreach ( $users as $user ){
            $grup=$user->getGroups()[0];
            if(!$grup){
                $user->addGroup($group);
                $em->persist($user);
                $em->flush();
                $contador++;
            }

        }

        $output->writeln($contador.' Users Added');
    }


}