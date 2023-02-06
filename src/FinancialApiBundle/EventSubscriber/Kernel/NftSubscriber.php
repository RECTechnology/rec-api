<?php

namespace App\FinancialApiBundle\EventSubscriber\Kernel;

use App\FinancialApiBundle\Entity\AccountChallenge;
use App\FinancialApiBundle\Entity\NFTTransaction;
use App\FinancialApiBundle\Event\MintNftEvent;
use App\FinancialApiBundle\Event\ShareNftEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NftSubscriber implements EventSubscriberInterface
{

    private $em;

    public function __construct(EntityManagerInterface $em){
        $this->em = $em;
    }
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            ShareNftEvent::NAME => 'onShareNft',
            MintNftEvent::NAME => 'onMintNft'
        ];
    }

    public function onShareNft(ShareNftEvent $event){
        $this->createTransaction($event, NFTTransaction::NFT_SHARE);
    }

    public function onMintNft(MintNftEvent $event){
        $this->createTransaction($event, NFTTransaction::NFT_MINT);
    }

    private function createTransaction($event, $method){
        //check if account owns already this token
        $tx = $this->em->getRepository(NFTTransaction::class)->findOneBy(array(
            'original_token_id' => $event->getOriginalToken(),
            'to' => $event->getReceiver(),
            'method' => $method,
            'contract_name' => $event->getContractName(),
            'token_reward' => $event->getTokenReward()
        ));

        if(!$tx){
            $nft_transaction = new NFTTransaction();
            $nft_transaction->setStatus(NFTTransaction::STATUS_CREATED);
            $nft_transaction->setOriginalTokenId($event->getOriginalToken());
            $nft_transaction->setMethod($method);
            $nft_transaction->setFrom($event->getSender());
            $nft_transaction->setTo($event->getReceiver());
            $nft_transaction->setContractName($event->getContractName());
            $nft_transaction->setTopicId($event->getTopicId());
            $nft_transaction->setTokenReward($event->getTokenReward());

            if($method !== NFTTransaction::NFT_MINT && $event->getContractName() === NFTTransaction::B2C_SHARABLE_CONTRACT){
                //account challenge only has to be generated for this smart contract and if is not mint(because mint is for admin)
                $accountChallenge = new AccountChallenge();
                $accountChallenge->setAccount($event->getReceiver());
                $accountChallenge->setChallenge($event->getChallenge());
                $accountChallenge->setTotalAmount($event->getTotalAmount());
                $accountChallenge->setTotalTransactions($event->getTotalTransactions());

                $this->em->persist($accountChallenge);
            }


            $this->em->persist($nft_transaction);
            $this->em->flush();
        }
    }
}