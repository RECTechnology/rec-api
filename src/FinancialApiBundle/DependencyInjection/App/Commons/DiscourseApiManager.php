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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DiscourseApiManager{

    private $container;
    private $logger;

    public function __construct(ContainerInterface $container, Logger $logger){
        $this->container = $container;
        $this->logger = $logger;
    }

    public function register(Group $account){

        $this->logger->info("Registering account ".$account->getName());
        $password = $this->generateRandomPassword();

        $this->checkAccountHasNeededFields($account);

        $email = $account->getEmail();
        if(!$email){
            $this->logger->info("Setting fake email");
            $email = $account->getRezeroB2bUsername()."@atarca-b2b.es";
        }
        $data = array(
            "name"=> $account->getName(),
            "email"=> $email,
            "password"=> $password,
            "username"=> $account->getRezeroB2bUsername(),
            "active"=> true,
            "approved"=> true,
            "user_fields" => array('field 0',$account->getName(), $account->getKycManager()->getName(), $account->getId())
        );
        //en userfields se envia field 0 al principio porque el 0 no se guarda, no se por que. Empieza a guardar desde el 1

        return $this->_callDiscourseAdmin('users.json', $this->getAdminCredentials(), 'POST', $data);

    }

    public function generateApiKeys(Group $account){
        $this->logger->info("Generating Api Keys for ".$account->getName());
        $data = array(
            "key" => [
                "username" => $account->getRezeroB2bUsername(),
                "description" => "Generated by REC Api:".$account->getName()
            ]
        );
        $response = $this->_callDiscourseAdmin('admin/api/keys', $this->getAdminCredentials(), 'POST', $data);
        $this->logger->info(json_encode($response));
        if(isset($response['key'])){
            $this->logger->info("Api Keys Generated successfully for ".$account->getName());
            return $response['key']['key'];
        }
        $this->logger->error("Something went wrong Generating Api Keys for ".$account->getName());

        return 'error';
    }

    public function subscribeToNewsCategory(Group $account){
        $this->logger->info("Subscribing ".$account->getName()." to news category");
        $news_category_id = $this->container->getParameter("discourse_news_category_id");
        $data = array(
            "notification_level" => 4
        );
        return $this->bridgeCall($account, 'category/'.$news_category_id.'/notifications', 'POST', $data);
    }

    public function updateName(Group $account, $name){
        $this->logger->info("Changing name for ".$account->getName()." to ".$name);
        $data = array(
            "name" => $name
        );
        return $this->bridgeCall($account, 'users/'.$account->getRezeroB2bUsername().'.json', 'PUT', $data);

    }

    public function updateUsername(Group $account, $old_username, $new_username){
        $this->logger->info("Changing username for ".$account->getName()." to ".$new_username);
        $data = array(
            "new_username" => $new_username
        );
        $credentials = array(
            'Api-Key: '.$account->getRezeroB2bApiKey(),
            'Api-Username: '. $old_username
        );
        return $this->_callDiscourse('u/'.$old_username.'/preferences/username.json', $credentials, 'PUT', $data);

    }

    public function updateCompanyImage(Group $account, $filename){
        //this filename arrives like /something.jpg
        $this->logger->info("Synchronizing profile image for ".$account->getName());
        $this->logger->info("Synchronizing file ".$filename);
        $files_path = $this->container->getParameter("files_path");
        $name = str_replace($files_path.'/',"", $filename);
        $fileData = array(
            'name' => $name
        );

        $data = array(
            'type' => 'avatar',
            'synchronous' => true,
            'user_id' => $account->getRezeroB2bUserId()
        );

        $uploadResponse = $this->bridgeCall($account, 'uploads.json', "POST", $data, [], $fileData);

        $this->logger->info("File uploaded file ".$filename);
        //set profile image on discourse
        $pickEndpoint = '/u/'.$account->getRezeroB2bUsername().'/preferences/avatar/pick.json';
        $avatarData = array(
            "upload_id" => $uploadResponse["id"],
            "type" => "custom"
        );
        $setImageResponse = $this->bridgeCall($account, $pickEndpoint, "PUT", $avatarData, [], null);

        if(!isset($setImageResponse["success"]) || $setImageResponse['success'] !== "OK"){
            throw new HttpException(400, "Something went wrong synchronizing image with discourse. Please try again");
        }

        $this->logger->info("Synchronized file ".$filename);

    }

    public function bridgeCall(Group $account, $endpoint, $method, $data = [], $urlParams = [], $fileData = null){
        $this->logger->info("Starting Bridge call for ".$account->getName());
        $credentials = array(
            'Api-Key: '.$account->getRezeroB2bApiKey(),
            'Api-Username: '. $account->getRezeroB2bUsername()
        );

        return $this->_callDiscourse($endpoint, $credentials, $method, $data, $urlParams, $fileData);

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

    private function _callDiscourse($endpoint, $credentials, $method, $data = array(), $urlParams = array(), $fileData = null){
        $this->logger->info("Calling discourse...");
        $base_url  = $this->container->getParameter('discourse_url');
        $curl = curl_init();

        $isPostMethod = false;
        if($method === 'POST') $isPostMethod = true;

        if($urlParams){
            $encoded_params = http_build_query($urlParams);
            $endpoint .= '?' . $encoded_params;
        }

        if($fileData){
            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url.'/'.$endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POST => $isPostMethod,
                CURLOPT_HTTPHEADER => $credentials
            ));

            $filepath = realpath($this->container->getParameter('uploads_dir').'/'.$fileData['name']);
            $data['file'] = new \CURLFile($filepath);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);


        }elseif ($method === 'PUT'){

            //TODO needs more investigation
            //ese curl parece igual que el ultimno pero si no pongo asi los postfields en el put falla
            $credentials[] = 'Content-Type: application/json';
            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url.'/'.$endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POST => $isPostMethod,
                CURLOPT_HTTPHEADER => $credentials
            ));

            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));


        }elseif ($method === 'DELETE'){
            //TODO needs more invetigation
            //este curl parece iwal que el del else , solo cambia el orden de CURLOPT_POST y CURLOPT_POSTFIELDS
            //por algun motivo que no entiendo si cambio el orden peta
            $credentials[] = 'Content-Type: application/json';
            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url.'/'.$endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POST => $isPostMethod,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => $credentials
            ));

        }else{

            $credentials[] = 'Content-Type: application/json';
            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url.'/'.$endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_POST => $isPostMethod,
                CURLOPT_HTTPHEADER => $credentials
            ));
        }

        $response = curl_exec($curl);

        curl_close($curl);

        $decodedResponse = json_decode($response, true);

        $this->manageResponse($decodedResponse);

        $this->logger->info("Call discourse went well");
        return $decodedResponse;
    }

    private function _callDiscourseAdmin($endpoint, $credentials, $method, $data = array(), $urlParams = array(), $fileData = null){
        $this->logger->info("Calling discourse Admin...");
        $base_url  = $this->container->getParameter('discourse_url');
        $curl = curl_init();

        $isPostMethod = false;
        if($method === 'POST') $isPostMethod = true;

        if($urlParams){
            $encoded_params = http_build_query($urlParams);
            $endpoint .= '?' . $encoded_params;
        }

        if($method === 'PUT'){
            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url.'/'.$endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POST => $isPostMethod,
                CURLOPT_HTTPHEADER => $credentials
            ));

            $encodedData = http_build_query($data);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $encodedData);
        }else{

            curl_setopt_array($curl, array(
                CURLOPT_URL => $base_url.'/'.$endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POST => $isPostMethod,
                CURLOPT_HTTPHEADER => $credentials
            ));

            $encodedData = http_build_query($data);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $encodedData);
        }

        $response = curl_exec($curl);

        curl_close($curl);

        $decodedResponse = json_decode($response, true);

        $this->manageResponse($decodedResponse);

        $this->logger->info("Call discourse went well");
        return $decodedResponse;
    }

    private function getAdminCredentials(){
        $admin_username = $this->container->getParameter("discourse_admin_username");
        $admin_api_key = $this->container->getParameter("discourse_admin_api_key");

        return array(
            'Api-Username: '.$admin_username,
            'Api-Key: '.$admin_api_key,
            'Content-Type: multipart/form-data'
        );
    }

    private function checkAccountHasNeededFields(Group $account){

        if(!$account->getRezeroB2bUsername()) throw new HttpException(400, 'Rezero Username is not set');
        if(!$account->getName()) throw new HttpException(400, 'Account name is not set');

    }

    private function manageResponse($decodedResponse){
        if(isset($decodedResponse['status']) && $decodedResponse['status'] == 500) throw new HttpException(400, "Discourse Internal Server error");
        if(isset($decodedResponse['failed'])) throw new HttpException(400, "Failed");
        if(isset($decodedResponse['errors'])){
            $this->logger->error("Something went wrong in DiscourseApiManager CallDiscourseAdmin");
            if(isset($decodedResponse['message'])){
                $this->logger->error($decodedResponse['message']);
                throw new HttpException(400, $decodedResponse['message']);
            }else{
                $this->logger->error($decodedResponse['errors'][0]);
                throw new HttpException(400, $decodedResponse['errors'][0]);
            }
        }
    }

}