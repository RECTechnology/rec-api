<?php
namespace App\Command;

use App\Entity\Campaign;
use App\Entity\Group;
use App\Entity\UserWallet;
use App\Financial\Currency;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCampaignThresholdsCommand extends SynchronizedContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('rec:check:campaign:thresholds');
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output) {
        $crypto_currency = $this->container->getParameter('crypto_currency');
        $output->writeln('Init ' . $this->getName());
        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository(Campaign::class);
        $campaigns = $repo->findBy(array('status' => Campaign::STATUS_ACTIVE));

        /** @var Campaign $campaign */
        foreach ($campaigns as $campaign){
            //check campaign threshold if needed
            $campaign_account_id = $campaign->getCampaignAccount();
            /** @var Group $campaign_account */
            $campaign_account = $em->getRepository(Group::class)->find($campaign_account_id);
            /** @var UserWallet $campaign_account_wallet */
            $campaign_account_wallet = $campaign_account->getWallet($crypto_currency);

            if($campaign->getBonusEndingThreshold() !== null){
                if($campaign_account_wallet->getBalance() < $campaign->getBonusEndingThreshold()){
                    $campaign->setEndingAlert(true);
                }else{
                    $campaign->setEndingAlert(false);
                }
            }

            if($campaign_account_wallet->getBalance() == 0) {
                $campaign->setBonusEnabled(false);
            }else{
                $campaign->setBonusEnabled(true);
            }

            $em->flush();
        }

        $output->writeln('Finish ' . $this->getName());
    }
}