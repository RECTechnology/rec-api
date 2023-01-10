<?php
namespace App\FinancialApiBundle\Command;

use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Financial\Currency;
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
        $crypto_currency = $this->getContainer()->getParameter('crypto_currency');
        $output->writeln('Init ' . $this->getName());
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository(Campaign::class);
        $campaigns = $repo->findAll();

        /** @var Campaign $campaign */
        foreach ($campaigns as $campaign){
            //check campaign threshold if needed
            $campaign_account_id = $campaign->getCampaignAccount();
            /** @var Group $campaign_account */
            $campaign_account = $em->getRepository(Group::class)->find($campaign_account_id);
            /** @var UserWallet $campaign_account_wallet */
            $campaign_account_wallet = $campaign_account->getWallet(Currency::$REC);
            if($campaign->getBonusEndingThreshold() !== null && $campaign->getEndingAlert() === false){
                if($campaign_account_wallet->getBalance() < $campaign->getBonusEndingThreshold()){
                    $campaign->setEndingAlert(true);
                    $em->flush();
                }

            }

            if($campaign->isBonusEnabled() && $campaign_account_wallet->getBalance() == 0) {
                $campaign->setBonusEnabled(false);
                $em->flush();
            }
        }

        $output->writeln('Finish ' . $this->getName());
    }
}