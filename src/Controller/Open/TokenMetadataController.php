<?php

namespace App\Controller\Open;

use App\Entity\Challenge;
use App\Entity\Group;
use App\Entity\NFTTransaction;
use App\Entity\TokenReward;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Controller\RestApiController;
use Symfony\Component\HttpFoundation\Response;

class TokenMetadataController extends RestApiController {

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getTokenMetadata(Request $request, $contract, $token_id){
        $current_contract_name = null;
        foreach ($this->getContracts() as $contract_name => $value){
            if($value === $contract){
                $current_contract_name = $contract_name;
            }
        }
        if($current_contract_name){
            if($current_contract_name === NFTTransaction::B2B_SHARABLE_CONTRACT){
                $description = "Create content";
                $name = "BADGE";
                $image = "";
                $category_value = "Usefull response";

                $nftTx = $this->findNftTransaction($token_id, $current_contract_name, NFTTransaction::NFT_SHARE);

                if($nftTx){
                    $receiver = $nftTx->getTo();
                    $topicId = $nftTx->getTopicId();

                    $metadata = $this->getB2BMetadata($description, $name, $image, $category_value, $receiver, $topicId);
                    $status_code = 200;
                }else{
                    $metadata = "No info for this token";
                    $status_code = 404;
                }

            }elseif ($current_contract_name === NFTTransaction::B2B_LIKE_CONTRACT){
                $description = "Like content";
                $name = "LIKE";
                $image = "";
                $category_value = "LIKE";

                /** @var NFTTransaction $nftTx */
                $nftTx = $this->findNftTransaction($token_id, $current_contract_name, NFTTransaction::NFT_LIKE);

                if($nftTx){
                    $receiver = $nftTx->getTo();
                    $topicId = $nftTx->getTopicId();
                    $metadata = $this->getB2BMetadata($description, $name, $image, $category_value, $receiver, $topicId);
                    $status_code = 200;
                }else{
                    $metadata = "No info for this token";
                    $status_code = 404;
                }

            }elseif ($current_contract_name === NFTTransaction::B2C_SHARABLE_CONTRACT){

                /** @var NFTTransaction $nftTx */
                $nftTx = $this->findNftTransaction($token_id, $current_contract_name, NFTTransaction::NFT_SHARE);

                if($nftTx){
                    $receiver = $nftTx->getTo();
                    /** @var TokenReward $token_reward */
                    $token_reward = $nftTx->getTokenReward();
                    /** @var Challenge $challenge */
                    $challenge = $token_reward->getChallenge();

                    $metadata = $this->getB2CMetadata($challenge, $token_reward, $receiver);
                    $status_code = 200;
                }else{
                    $metadata = "No info for this token";
                    $status_code = 404;
                }
            }else{
                $metadata = "No info for this token";
                $status_code = 404;
            }
        }else{
            $metadata = "No info for this token";
            $status_code = 404;
        }

        return new JsonResponse($metadata, $status_code);
    }

    private function getContracts(){
        return [
            NFTTransaction::B2B_SHARABLE_CONTRACT => $this->getParameter('atarca_sharable_nft_contract_address'),
            NFTTransaction::B2B_LIKE_CONTRACT => $this->getParameter('atarca_like_nft_contract_address'),
            NFTTransaction::B2C_SHARABLE_CONTRACT => $this->getParameter('atarca_b2c_sharable_nft_contract_address')
        ];
    }

    private function findNftTransaction($token_id, $current_contract_name, $method){
        $em = $this->getDoctrine()->getManager();
        /** @var NFTTransaction $nftTx */
        $nftTx = $em->getRepository(NFTTransaction::class)->findOneBy(array(
            "shared_token_id" => $token_id,
            "contract_name" => $current_contract_name,
            "method" => $method
        ));
        if(!$nftTx){
            $nftTx = $em->getRepository(NFTTransaction::class)->findOneBy(array(
                "original_token_id" => $token_id,
                "shared_token_id" => null,
                "contract_name" => $current_contract_name,
                "method" => NFTTransaction::NFT_MINT
            ));
        }

        return $nftTx?? null;
    }

    public function getB2BMetadata($description, $name, $image, $category, Group $receiver, $topicId){
        $baseUrl = $this->getParameter('b2b_forum_url');
        $metadata = [
            "description" => $description,
            "name" => $name,
            "image" => $image,
            "attributes" => [
                [
                    "trait_type" => "Category",
                    "value" => $category
                ],
                [
                    "trait_type" => "Receiver",
                    "value" => $receiver->getName()
                ],
                [
                    "trait_type" => "Link to contribution",
                    "value" => $baseUrl."/#/forum/topic?id=".$topicId
                ],
            ]
        ];

        return $metadata;
    }

    public function getB2CMetadata(Challenge $challenge, TokenReward $token_reward, Group $receiver){
        $metadata = [
            "description" => $challenge->getDescription(),
            "name" => $challenge->getTitle(),
            "image" => $token_reward->getImage(),
            "attributes" => [
                [
                    "trait_type" => "Category",
                    "value" => $challenge->getAction()
                ],
                [
                    "trait_type" => "Receiver",
                    "value" => $receiver->getName()
                ],
                [
                    "trait_type" => "Author Url",
                    "value" => $token_reward->getAuthorUrl()
                ],
                [
                    "trait_type" => "Challenge Id",
                    "value" => $challenge->getId()
                ],
            ]
        ];

        return $metadata;
    }

}