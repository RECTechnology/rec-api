<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 7/15/14
 * Time: 1:27 PM
 */

namespace App\Command;


use App\DependencyInjection\Commons\Web3ApiManager;
use App\Entity\ConfigurationSetting;
use App\Entity\Group;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateNFTWalletCommand extends SynchronizedContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:create:NFTwallet')
            ->setDescription('Generate NFT wallets if not exists')
        ;
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output){
        $em = $this->container->get('doctrine.orm.entity_manager');
        $creation_enabled = $em->getRepository(ConfigurationSetting::class)->findOneBy(['name' => 'create_nft_wallet', 'value' => 'enabled']);
        if($creation_enabled){
            $accounts = $em->getRepository(Group::class)->findBy(['nft_wallet' => '', 'active' => 1]);

            $output->writeln(count($accounts).' accounts found without wallet');
            /** @var Web3ApiManager $web3Manager */
            $web3Manager = $this->container->get('net.app.commons.web3.api_manager');
            foreach ( $accounts as $account ) {

                if ($account->getKycManager() && $account->getKycManager()->isEnabled() && $account->getKycManager()->isAccountNonLocked()) {
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
        }else{
            $output->writeln('web3 is disabled, if you want to use it go to settings and enable create_nft_wallet option');
        }
    }
}