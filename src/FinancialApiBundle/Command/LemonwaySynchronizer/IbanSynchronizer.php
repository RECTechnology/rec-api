<?php


namespace App\FinancialApiBundle\Command\LemonwaySynchronizer;


use App\FinancialApiBundle\Entity\Iban;

class IbanSynchronizer extends AbstractSynchronizer {

    function sync(){

        $index = $this->getWalletsIndexedByLemonId();

        $callParams = ['wallets' => []];
        foreach ($index as $wid => $account){
            $callParams['wallets'] []= ['wallet' => $wid];
        }
        $repo = $this->em->getRepository(Iban::class);
        $resp = $this->lw->callService('GetWalletDetailsBatch', $callParams);

        foreach ($resp->wallets as $walletInfo){
            if($walletInfo->WALLET != null){
                $this->output->writeln("[INFO] Found LW ID {$walletInfo->WALLET->ID}");
                foreach ($walletInfo->WALLET->IBANS as $lwiban){
                    /** @var Iban $iban */
                    $iban = $repo->findOneBy(['external_reference' => $lwiban->ID]);
                    if($iban) {
                        if(in_array($lwiban->S, Iban::LW_STATUS_APPROVED))
                            $iban->setStatus(Iban::STATUS_APPROVED);
                        elseif (in_array($lwiban->S, Iban::LW_STATUS_DECLINED))
                            $iban->setStatus(Iban::STATUS_DECLINED);
                        $iban->setLemonStatus($lwiban->S);
                        $this->em->persist($iban);
                    }
                }
            }
            else {
                $this->output->writeln("[WARN] LW error: {$walletInfo->E->Msg}");
            }
        }

        $this->em->flush();

    }

}