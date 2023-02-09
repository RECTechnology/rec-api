<?php

namespace App\FinancialApiBundle\Command;

use App\FinancialApiBundle\Entity\Campaign;
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
        // TODO: Implement executeSynchronized() method.
        $em = $this->getContainer()->get('doctrine')->getManager();
        $campaigns_v2 = $em->getRepository(Campaign::class)->findBy(array('version' => 2));
        $now = new \DateTime();
        $output->writeln(count($campaigns_v2).' campaigns found to manage');
        /** @var Campaign $campaign */
        foreach ($campaigns_v2 as $campaign){
            if($campaign->getStatus() === Campaign::STATUS_CREATED && $campaign->getInitDate() <= $now){
                $campaign->setStatus(Campaign::STATUS_ACTIVE);
                $em->flush();
                $output->writeln('Campaign '.$campaign->getName().' is active now');
            }elseif ($campaign->getStatus() === Campaign::STATUS_ACTIVE && $campaign->getEndDate() < $now){
                $campaign->setStatus(Campaign::STATUS_FINISHED);
                $em->flush();
                $output->writeln('Campaign '.$campaign->getName().' is finished now');
            }elseif ($campaign->getStatus() === Campaign::STATUS_FINISHED && $campaign->getEndDate() > $now){
                $campaign->setStatus(Campaign::STATUS_ACTIVE);
                $em->flush();
                $output->writeln('Campaign '.$campaign->getName().' is reactivated now');
            }
        }
    }
}