<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/15/14
 * Time: 1:27 PM
 */

namespace App\FinancialApiBundle\Command;


use App\FinancialApiBundle\DependencyInjection\App\Commons\Web3ApiManager;
use App\FinancialApiBundle\Entity\ConfigurationSetting;
use App\FinancialApiBundle\Entity\Group;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class CreateNFTWalletCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:create:NFTwallet')
            ->setDescription('Generate NFT wallets if not exists')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $em = $this->getContainer()->get('doctrine')->getManager();
        $creation_enabled = $em->getRepository(ConfigurationSetting::class)->findOneBy(['name' => 'create_nft_wallet', 'value' => 'enabled']);
        if($creation_enabled){


            $accounts = $em->getRepository(Group::class)->findBy(['nft_wallet' => '', 'active' => 1], [], 5);

            /** @var Web3ApiManager $web3Manager */
            $web3Manager = $this->getContainer()->get('net.app.commons.web3.api_manager');
            foreach ( $accounts as $account ) {

                if ($account->getKycManager()->isEnabled() && $account->getKycManager()->isAccountNonLocked()) {
                    try{
                        $output->writeln("Creating NFT wallet for the account " . $account->getId());
                        $response = $web3Manager->createWallet();
                        $account->setNftWallet($response["wallet"]["address"]);
                        $account->setNftWalletPk($response["wallet"]["private_key"]);
                        $em->persist($account);
                        $em->flush();
                        $output->writeln("Created NFT wallet for the account " . $account->getId());
                    }catch (Exception $e) {
                        $output->writeln( 'Error during call: '.strval($e->getMessage()));
                    }

                }

            }
        }
    }
}