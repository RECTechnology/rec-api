<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use App\FinancialApiBundle\Entity\Group;
use Monolog\Logger;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Web3ApiManager{

    private $container;
    private $logger;
    private $web3_api_url;

    public function __construct(ContainerInterface $container, Logger $logger){
        $this->container = $container;
        $this->logger = $logger;
        $this->web3_api_url = $this->container->getParameter("atarca_web3_api_url");
    }

    public function createWallet(){
        $this->logger->info( 'Create new wallet');
        try{
            $ops = [
                'http' => [
                    'method' => 'POST'
                ]
            ];

            $resp = file_get_contents(
                $this->web3_api_url."/create_wallet",
                false,
                stream_context_create($ops)
            );
            $this->logger->info( 'New wallet created: '.strval($resp['wallet']['address']));
            return $resp;
        }catch (Exception $e) {
            $this->logger->info( 'Error during call: '.strval($e));
            return $e;
        }
    }

    public function createNFT($contract_address, $wallet, $sender_address, $sender_pk)
    {
        $this->logger->info( 'Create NFT');
        try{
            $content = json_encode(
                [
                    "contract_address" => $contract_address,
                    "function_name" => "mint",
                    "args" => $wallet,
                    "tx_args" => [
                        "sender_address" => $sender_address,
                        "sender_private_key" => $sender_pk
                    ]
                ]
            );
            $ops = [
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($content)
                    ],
                    'content' => $content
                ]
            ];

            $resp = file_get_contents(
                $this->web3_api_url."/contract_function_call",
                false,
                stream_context_create($ops)
            );
            $this->logger->info( 'New NFT created: '.strval($resp));
            return $resp;
        }catch (Exception $e) {
            $this->logger->info( 'Error during call: '.strval($e));
            return $e;
        }

    }

    function shareNFT($contract_address, $wallet, $nft_to_share, $sender_address, $sender_pk)
    {
        $this->logger->info( 'Share NFT');
        try{
            $content = json_encode(
                [
                    "contract_address" => $contract_address,
                    "function_name" => "share",
                    "args" => [$wallet, $nft_to_share],
                    "tx_args" => [
                        "sender_address" => $sender_address,
                        "sender_private_key" => $sender_pk
                    ]
                ]
            );
            $ops = [
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($content)
                    ],
                    'content' => $content
                ]
            ];

            $resp = file_get_contents(
                $this->web3_api_url."/contract_function_call",
                false,
                stream_context_create($ops)
            );

            $this->logger->info( 'NFT hared: '.strval($resp));
            return $resp;
        }catch (Exception $e) {
            $this->logger->info( 'Error during call: '.strval($e));
            return $e;
        }

    }
}