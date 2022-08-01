<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use Monolog\Logger;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Web3ApiManager{

    public const ERROR_NO_FUNDS = 'no_funds';

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

            $resp = json_decode(file_get_contents(
                $this->web3_api_url."/create_wallet",
                false,
                stream_context_create($ops)
            ), true);
            $this->logger->info( 'New wallet created: '.strval($resp['wallet']['address']));
            return json_decode($resp);
        }catch (Exception $e) {
            $this->logger->info( 'Error during call: '.strval($e));
            return $e;
        }
    }

    public function getNonce($contract_address, $wallet)
    {
        $this->logger->info("WEB3_API_MANAGER - get nonce for wallet ".$wallet);
        try{
            $resp = json_decode(file_get_contents(
                $this->web3_api_url."/get_nonce?contract_address=".$contract_address."&wallet=".$wallet
            ), true);

            if($resp){
                $this->logger->info("Getting nonce", $resp);
                if(array_key_exists('message', $resp) && $resp['message'] === 'success') return $resp;
            }


            $this->logger->info( 'Error during nonce call: '.strval(json_encode($resp)));
        }catch (Exception $e) {
            $this->logger->info( 'Error during nonce call: '.strval($e));
        }
    }

    public function getBalance($contract_address, $wallet)
    {
        try{
            $resp = json_decode(file_get_contents(
                $this->web3_api_url."/get_balance?contract_address=".$contract_address."&wallet=".$wallet
            ), true);
            if(array_key_exists('message', $resp) and $resp['message'] == "success") return $resp;

            $this->logger->info( 'Error during balance call: '.strval(json_encode($resp)));
        }catch (Exception $e) {
            $this->logger->info( 'Error during balance call: '.strval($e));

        }
    }

    public function get_transaction_status($contract_address, $transaction_id)
    {
        try{
            $resp = json_decode(file_get_contents(
                $this->web3_api_url."/get_transaction_status?contract_address=".$contract_address."&transaction_id=".$transaction_id
            ), true);
            if(array_key_exists('status', $resp)) return $resp;

            $this->logger->info( 'Error during status call: '.strval(json_encode($resp)));
            throw new \Exception('Error during status call: '.strval(json_encode($resp)));
        }catch (Exception $e) {
            $this->logger->info(  'Error during status call. '.strval($e));
            throw new \Exception('Error during status call: '.strval($e));
        }
    }

    public function createNFT($contract_address, $wallet, $sender_address, $sender_pk, $nonce=null)
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
                    ],
                    "nonce" => $nonce
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

            $resp = json_decode(file_get_contents(
                $this->web3_api_url."/contract_function_call",
                false,
                stream_context_create($ops)
            ), true);
            if(array_key_exists('error', $resp) and $resp['error'] == ''){
                $this->logger->info( 'New NFT created: '.strval(json_encode($resp)));

            }else{
                $this->logger->info( 'Error during create call: '.strval(json_encode($resp)));
            }
            return $resp;
        }catch (Exception $e) {
            $this->logger->info( 'Error during create call: '.strval($e));
            throw new \Exception('Error during create call: '.strval($e));
        }
    }


    public function shareNFT($contract_address, $wallet, $nft_to_share, $sender_address, $sender_pk, $nonce=null)
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
                    ],
                    "nonce" => $nonce
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

            $resp = json_decode(file_get_contents(
                $this->web3_api_url."/contract_function_call",
                false,
                stream_context_create($ops)
            ), true);

            if(array_key_exists('error', $resp) and $resp['error'] == ''){
                $this->logger->info( 'NFT shared'.strval(json_encode($resp)));

            }else{
                $this->logger->info( 'Error during share call'.strval(json_encode($resp)));
            }
            return $resp;

        }catch (Exception $e) {
            $this->logger->info('Error during share call: '.strval($e));
            throw new \Exception('Error during share call: '.strval($e));
        }
    }

    public function likeNFT($contract_address, $nft_to_like, $sender_address, $sender_pk, $nonce=null)
    {
        $this->logger->info( 'Like NFT');
        try{
            $content = json_encode(
                [
                    "contract_address" => $contract_address,
                    "function_name" => "mint",
                    "args" => $nft_to_like,
                    "tx_args" => [
                        "sender_address" => $sender_address,
                        "sender_private_key" => $sender_pk
                    ],
                    "nonce" => $nonce
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

            $resp = json_decode(file_get_contents(
                $this->web3_api_url."/contract_function_call",
                false,
                stream_context_create($ops)
            ), true);

            if(array_key_exists('error', $resp) and $resp['error'] == ''){
                $this->logger->info( 'NFT liked');
            }else{
                $this->logger->info( 'Error during like call'.strval(json_encode($resp)));
            }
            return $resp;
        }catch (Exception $e) {
            $this->logger->info('Error during like call: '.strval($e));
            throw new \Exception('Error during like call: '.strval($e));
        }
    }

    public function transfer($contract_address, $amount, $to, $sender_address, $sender_pk, $nonce=null)
    {
        $this->logger->info( 'Transfer ETH');
        try{

            $content = json_encode(
                [
                    "contract_address" => $contract_address,
                    "amount" => $amount * 1e18,
                    "to" => $to,
                    "sender_address" => $sender_address,
                    "sender_private_key" => $sender_pk,
                    "nonce" => $nonce
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

            $resp = json_decode(file_get_contents(
                $this->web3_api_url."/transfer",
                false,
                stream_context_create($ops)
            ), true);

            if(array_key_exists('error', $resp) and $resp['error'] == ''){
                $this->logger->info( 'ETH transfered'.strval(json_encode($resp)));

            }else{
                $this->logger->info( 'Error during transfer call'.strval(json_encode($resp)));
            }
            return $resp;

        }catch (Exception $e) {
            $this->logger->info( 'Error during transfer call: '.strval($e));
            throw new \Exception('Error during transfer call: '.strval($e));
        }
    }
}