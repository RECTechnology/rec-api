<?php

namespace App\Command;

use App\Entity\Campaign;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ManageCampaignsStatusCommand extends SynchronizedContainerAwareCommand
{

    protected function configure(){
        $this->setName('rec:manage:campaigns')
            ->setDescription('manage campaigns status. Open and close v2 campaigns automatically');
    }
    protected function executeSynchronized(InputInterface $input, OutputInterface $output)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $campaigns_v2 = $em->getRepository(Campaign::class)->findAll();
        $now = new \DateTime();
        $output->writeln(count($campaigns_v2).' campaigns found to manage');
        $status_campaign='';
        /** @var Campaign $campaign */
        foreach ($campaigns_v2 as $campaign){
            if($campaign->getStatus() === Campaign::STATUS_CREATED && $campaign->getInitDate() <= $now){
                $campaign->setStatus(Campaign::STATUS_ACTIVE);
                $em->flush();
                $output->writeln('Campaign '.$campaign->getName().' is active now');
            }elseif ($campaign->getStatus() === Campaign::STATUS_ACTIVE && $campaign->getEndDate() < $now){
                if($campaign->getInitDate() > $now){
                    $status_campaign=' is created now';
                    $campaign->setStatus(Campaign::STATUS_CREATED);
                }
                else{
                    $status_campaign=' is finished now';
                    $campaign->setStatus(Campaign::STATUS_FINISHED);
                }
                $em->flush();
                $output->writeln('Campaign '.$campaign->getName().$status_campaign);
            }elseif ($campaign->getStatus() === Campaign::STATUS_FINISHED && $campaign->getEndDate() > $now){
                if($campaign->getInitDate() > $now){
                    $status_campaign=' is created now';
                    $campaign->setStatus(Campaign::STATUS_CREATED);
                }
                else{
                    $status_campaign=' is reactivated now';
                    $campaign->setStatus(Campaign::STATUS_ACTIVE);
                }
                $em->flush();
                $output->writeln('Campaign '.$campaign->getName().$status_campaign);
            }
        }
    }
}