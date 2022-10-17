<?php

namespace App\FinancialApiBundle\Command;

use App\FinancialApiBundle\Entity\Challenge;
use App\FinancialApiBundle\Entity\NFTTransaction;
use App\FinancialApiBundle\Event\MintNftEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartCloseChallengesCommand extends SynchronizedContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('rec:challenges:manage')
            ->setDescription('Start and close challenges');
    }

    protected function executeSynchronized(InputInterface $input, OutputInterface $output)
    {

        $em = $this->getContainer()->get('doctrine')->getManager();

        $this->closeOpenChallenges($em, $output);
        $this->openScheduledChallenges($em, $output);
    }

    private  function closeOpenChallenges($em, OutputInterface $output){
        $output->writeln('Closing challenges');
        $challenges = $em->getRepository(Challenge::class)->findBy(array(
            'status' => Challenge::STATUS_OPEN
        ));
        $output->writeln('Total challenges-> '.count($challenges));
        $now = new \DateTime();
        /** @var Challenge $challenge */
        foreach ($challenges as $challenge){
            if($challenge->getFinishDate() < $now){
                //change challenge to CLOSE
                $challenge->setStatus(Challenge::STATUS_CLOSED);
                $em->flush();
            }
        }
    }
    private function openScheduledChallenges($em, OutputInterface $output){
        $output->writeln('Opening challenges');
        //get scheduled challenges
        $challenges = $em->getRepository(Challenge::class)->findBy(array(
            'status' => Challenge::STATUS_SCHEDULED
        ));
        $output->writeln('Total challenges-> '.count($challenges));
        $now = new \DateTime();
        /** @var Challenge $challenge */
        foreach ($challenges as $challenge){

            if($challenge->getStartDate() < $now){
                //change challenge to open
                $challenge->setStatus(Challenge::STATUS_OPEN);

                //mint token if needed
                if($challenge->getTokenReward()){
                    $dispatcher = $this->getContainer()->get('event_dispatcher');
                    $mintEvent = new MintNftEvent(
                        $challenge,
                        NFTTransaction::B2C_SHARABLE_CONTRACT,
                        $challenge->getOwner(),
                        $challenge->getOwner(),
                        null
                    );
                    $dispatcher->dispatch(MintNftEvent::NAME, $mintEvent);
                }

                $em->flush();
            }
        }
    }
}