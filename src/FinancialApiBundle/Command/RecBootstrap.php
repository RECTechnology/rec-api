<?php

namespace App\FinancialApiBundle\Command;


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

class RecBootstrap extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:bootstrap')
            ->setDescription('Setup the initial configuration for a fresh installation')
            ->addOption(
                'admin-email',
                null,
                InputOption::VALUE_REQUIRED,
                'The admin e-mail.',
                null
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){

        $email = $input->getArgument('admin-email');

        /** @var KernelInterface $kernel */
        $kernel = $this->getContainer()->get('kernel');

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $output->writeln("Creating database schema...");
        $this->createDB($application,$output);
        $output->writeln("Database created successfully");

        $output->writeln("Creating OAuth2 client for Admin...");
        $this->createOAuth2Client("admin", $application, $output);
        $output->writeln("OAuth2 client for Admin created successfully");

        $output->writeln("Creating OAuth2 client for Panel...");
        $this->createOAuth2Client("panel", $application, $output);
        $output->writeln("OAuth2 client for Panel created successfully");

        $output->writeln("Creating OAuth2 client for Android...");
        $this->createOAuth2Client('android', $application, $output);
        $output->writeln("OAuth2 client for Android created successfully");

        $output->writeln("Creating OAuth2 client for IOS...");
        $this->createOAuth2Client('ios', $application, $output);
        $output->writeln("OAuth2 client for IOS created successfully");

        $output->writeln("Creating admin user...");
        $this->createAdminUser($email, $application, $output);
        $output->writeln("Admin user created successfully");

        $output->writeln("[Done]");
    }

    private function createDB(Application $application, OutputInterface $output){
        $commandIn = new ArrayInput([
            'command' => 'doctrine:schema:update',
            '--force'
        ]);
        $application->run($commandIn, $output);
    }

    private function createAdminUser($email, Application $application, OutputInterface $output){
        /** @var Random $random */
        $random = new Random();
        $password = substr($random->generateToken(), 0, 15);
        $commandIn = new ArrayInput([
            'command' => 'rec:user:root:create',
            'admin',
            $email,
            $password,
            '--super-admin'
        ]);
        $application->run($commandIn, $output);
        $output->writeln("Generated password for user admin is " . $password);
    }

    private function createOAuth2Client($name, Application $application, OutputInterface $output){
        $commandIn = new ArrayInput([
            'command' => 'rec:oauth2:client:create',
            '--grant-type' => ['password','client_credentials','refresh_token'],
            '--name' => $name
        ]);
        $application->run($commandIn, $output);
    }
}