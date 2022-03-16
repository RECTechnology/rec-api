<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use App\FinancialApiBundle\Entity\Group;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DiscourseApiManager{

    private $container;

    public function __construct(ContainerInterface $container){
        $this->container = $container;
    }

    public function register(Group $account){

        $password = $this->generateRandomPassword();

        $this->checkAccountHasNeededFields($account);

        $data = array(
            "name"=> $account->getName(),
            "email"=> $account->getEmail(),
            "password"=> $password,
            "username"=> $account->getRezeroB2bUsername(),
            "active"=> true,
            "approved"=> true,
        );

        return $this->_callDiscourse('users.json', $this->getAdminCredentials(), 'POST', $data);

    }

    public function generateApiKeys(Group $account){

        $data = array(
            "key" => [
                "username" => $account->getRezeroB2bUsername(),
                "description" => "Keys generated from REC Api."
            ]
        );
        $response = $this->_callDiscourse('admin/api/keys', $this->getAdminCredentials(), 'POST', $data);

        if(isset($response->key)){
            return $response->key->key;
        }

        return 'error';
    }

    public function bridgeCall(Group $account, $endpoint, $method, $data = array(), $urlParams = []){
        $credentials = array(
            'Api-Key: '.$account->getRezeroB2bApiKey(),
            'Api-Username: '. $account->getRezeroB2bUsername()
        );

        return $this->_callDiscourse($endpoint, $credentials, $method, $data, $urlParams);

    }

    private function generateRandomPassword() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 12; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

    private function _callDiscourse($endpoint, $credentials, $method, $data = array(), $urlParams = array()){
        $base_url  = $this->container->getParameter('discourse_url');
        $curl = curl_init();

        if($urlParams){
            $encoded_params = http_build_query($urlParams);
            $endpoint .= '?' . $encoded_params;
        }
        curl_setopt_array($curl, array(
            CURLOPT_URL => $base_url.'/'.$endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $credentials,
        ));


        if($method === 'POST'){
            $encodedData = http_build_query($data);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $encodedData);
        }


        $response = curl_exec($curl);

        curl_close($curl);

        $decodedResponse = json_decode($response);

        if(isset($decodedResponse->errors)){
            throw new HttpException(400, $decodedResponse->message);
        }

        return $decodedResponse;
    }

    private function getAdminCredentials(){
        $admin_username = $this->container->getParameter("discourse_admin_username");
        $admin_api_key = $this->container->getParameter("discourse_admin_api_key");

        return array(
            'Api-Username: '.$admin_username,
            'Api-Key: '.$admin_api_key,
            'Content-Type: multipart/form-data',
        );
    }

    private function checkAccountHasNeededFields(Group $account){

        if(!$account->getRezeroB2bUsername()) throw new HttpException(400, 'Rezero Username is not set');
        if(!$account->getEmail()) throw new HttpException(400, 'Account email is not set');
        if(!$account->getName()) throw new HttpException(400, 'Account name is not set');

    }

}