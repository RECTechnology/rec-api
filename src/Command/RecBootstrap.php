<?php

namespace App\Command;


use FOS\OAuthServerBundle\Util\Random;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class RecBootstrap extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:bootstrap')
            ->setDescription('Setup the initial configuration for a fresh installation')
            ->addOption(
                'admin-email',
                'a',
                InputOption::VALUE_REQUIRED,
                'The admin e-mail.',
                'admin@example.com'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){

        $email = $input->getOption('admin-email');

        $output->writeln("Creating database schema...");
        $this->command(
            [
                'command' => 'doctrine:schema:update',
                '--force' => true
            ],
            $output
        );
        $output->writeln("Database created successfully");

        $grants = ['password', 'client_credentials', 'refresh_token'];
        $output->writeln("Creating OAuth2 client for Admin...");
        $this->command(
            [
                'command' => 'rec:oauth2:client:create',
                '--grant-type' => $grants,
                '--name' => 'admin'
            ],
            $output
        );
        $output->writeln("OAuth2 client for Admin created successfully");

        $output->writeln("Creating OAuth2 client for Panel...");
        $this->command(
            [
                'command' => 'rec:oauth2:client:create',
                '--grant-type' => $grants,
                '--name' => 'panel'
            ],
            $output
        );
        $output->writeln("OAuth2 client for Panel created successfully");

        $output->writeln("Creating OAuth2 client for Android...");
        $this->command(
            [
                'command' => 'rec:oauth2:client:create',
                '--grant-type' => $grants,
                '--name' => 'android'
            ],
            $output
        );
        $output->writeln("OAuth2 client for Android created successfully");

        $output->writeln("Creating OAuth2 client for IOS...");
        $this->command(
            [
                'command' => 'rec:oauth2:client:create',
                '--grant-type' => $grants,
                '--name' => 'ios'
            ],
            $output
        );
        $output->writeln("OAuth2 client for IOS created successfully");

        $output->writeln("Creating admin user...");
        /** @var Random $random */
        $random = new Random();
        $password = substr($random->generateToken(), 0, 15);
        $this->command(
            [
                'command' => 'rec:user:root:create',
                'admin',
                '--email' => $email,
                '--password' => $password
            ],
            $output
        );

        $output->writeln(sprintf("Added Admin user with username: %s, password: %s", $email, $password));
        $output->writeln("Admin user created successfully");

        $output->writeln("[Done]");
    }

    /**
     * @throws \Exception
     */
    private function command(array $command, $output = null) {
        $com = $this->getApplication()->find($command['command']);
        $input = new ArrayInput($command);
        if ($output === null) $output = new NullOutput();
        $com->run($input, $output);
    }
}