<?php

namespace App\FinancialApiBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use FOS\OAuthServerBundle\Util\Random;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\KYC;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Entity\UserGroup;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Financial\Currency;

class RecCreateRoot extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:user:root:create')
            ->setDescription('Setup the initial root User and Account')
            ->addOption(
                'email',
                null,
                InputOption::VALUE_REQUIRED,
                'The admin e-mail.',
                null
            )
            ->addOption(
                'password',
                null,
                InputOption::VALUE_REQUIRED,
                'The admin password.',
                null
            )
        ;
    }
    private function getEmptyWallets(Group $account){
        $wallets = [];
        foreach(Currency::$ALL_COMPLETED as $currency){
            $wallet = new UserWallet();
            $wallet->setGroup($account);
            $wallet->setBalance(0);
            $wallet->setAvailable(0);
            $wallet->setCurrency(strtoupper($currency));
            $wallets []= $wallet;
        }
        return $wallets;
    }

    protected function execute(InputInterface $input, OutputInterface $output){

        $email = $input->getOption('email');
        $password = $input->getOption('password');

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');


        $user = new User();
        $user->setUsername('admin');
        $user->setEmail($email);
        $user->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN']);
        $user->setPlainPassword($password);

        $user->setName('System Admin');
        $user->setDNI('12345678A');
        $user->setPrefix('34');
        $user->setPhone('123456789');

        $em->persist($user);
        $account = new Group();
        $account->setName('System Admin');
        $account->setRecAddress('CHANGE_ME');
        $account->setMethodsList([]);
        $account->setCif('12345678A');
        $account->setActive(true);

        $em->persist($account);



        $user_group = new UserGroup();
        $user_group->setUser($user);
        $user_group->setGroup($account);

        $kyc = new KYC();
        $kyc->setUser($user);
        $kyc->setName($user->getName());
        $kyc->setEmail($user->getEmail());

        $em->persist($kyc);

        $em->flush();
    }

}