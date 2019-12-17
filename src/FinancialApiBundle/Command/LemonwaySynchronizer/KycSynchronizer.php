<?php


namespace App\FinancialApiBundle\Command\LemonwaySynchronizer;


use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\LemonDocument;
use App\FinancialApiBundle\Financial\Driver\LemonWayInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class KycSynchronizer extends AbstractSynchronizer {

    function sync(){

        $index = $this->getWalletsIndexedByLemonId();

        $callParams = ['wallets' => []];
        foreach ($index as $wid => $account){
            $callParams['wallets'] []= ['wallet' => $wid];
        }
        $repo = $this->em->getRepository(LemonDocument::class);
        $resp = $this->lw->callService('GetWalletDetailsBatch', $callParams);

        foreach ($resp->wallets as $walletInfo){
            if($walletInfo->WALLET != null){
                $this->output->writeln("[INFO] Found LW ID {$walletInfo->WALLET->ID}");
                foreach ($walletInfo->WALLET->DOCS as $lwdoc){
                    /** @var LemonDocument $document */
                    $document = $repo->findOneBy(['lemon_reference' => $lwdoc->ID]);
                    if(in_array($lwdoc->S, LemonDocument::LW_STATUS_APPROVED))
                        $document->setStatus(LemonDocument::STATUS_APPROVED);
                    elseif (in_array($lwdoc->S, LemonDocument::LW_STATUS_DECLINED))
                        $document->setStatus(LemonDocument::STATUS_DECLINED);
                    $document->setLemonStatus($lwdoc->S);
                    $this->em->persist($document);
                }
            }
            else {
                $this->output->writeln("[WARN] LW error: {$walletInfo->E->Msg}");
            }
        }

        $this->em->flush();

    }

}