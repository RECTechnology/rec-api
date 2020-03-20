<?php

namespace App\FinancialApiBundle\Command\LemonwaySynchronizer;

use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\LemonDocument;
use App\FinancialApiBundle\Entity\LemonDocumentKind;

class KycSynchronizer extends AbstractSynchronizer {

    function sync(){

        $index = $this->getWalletsIndexedByLemonId();

        $callParams = ['wallets' => []];
        foreach ($index as $wid => $account){
            $callParams['wallets'] []= ['wallet' => $wid];
        }
        $repo = $this->em->getRepository(LemonDocument::class);
        $doctypeRepo = $this->em->getRepository(LemonDocumentKind::class);
        $resp = $this->lw->callService('GetWalletDetailsBatch', $callParams);

        foreach ($resp->wallets as $walletInfo){
            if($walletInfo->WALLET != null){
                $this->output->writeln("[INFO] Found LW ID {$walletInfo->WALLET->ID}");
                foreach ($walletInfo->WALLET->DOCS as $lwdoc){
                    /** @var LemonDocument $document */
                    $document = $repo->findOneBy(['external_reference' => $lwdoc->ID]);

                    /** @var LemonDocumentKind $kind */
                    $kind = $doctypeRepo->findOneBy(['lemon_doctype' => $lwdoc->TYPE]);
                    if(!$kind) {
                        $kind = new LemonDocumentKind();
                        $kind->setLemonDoctype($lwdoc->TYPE);
                        $kind->setName("Lemonway auto-fetched doctype {$lwdoc->TYPE}");
                        $this->em->persist($kind);
                        $this->em->flush();  // needed to update doctypeRepo and avoid create duplicates
                    }

                    // if document is in lemonway but not in our API, create it with null doctype
                    if(!$document) {
                        $document = new LemonDocument();
                        $document->setKind($kind);
                        $document->setExternalInfo($lwdoc);
                        $document->setLemonReference($lwdoc->ID);
                        $document->setStatus(LemonDocument::STATUS_AUTO_FETCHED);
                        $document->setName("Lemonway auto-fetched document " . $lwdoc->ID);
                        $accRepo = $this->em->getRepository(Group::class);
                        $account = $accRepo->findOneBy(['cif' => strtolower($walletInfo->WALLET->ID)]);
                        if(!$account) $account = $accRepo->findOneBy(['cif' => strtoupper($walletInfo->WALLET->ID)]);
                        if(!$account)
                            $this->output->writeln("[WARN] LW wallet not found in database, ignoring document, lw_wallet: " . json_encode($walletInfo));
                        else {
                            $document->setAccount($account);
                            $this->em->persist($document);
                            $this->em->persist($kind);
                            $this->em->persist($account);
                        }
                    }
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