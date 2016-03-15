<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 4:38 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Entity\BTCAddresses;
use Telepay\FinancialApiBundle\Entity\BTCWallet;
use Telepay\FinancialApiBundle\Entity\Device;
use Telepay\FinancialApiBundle\Entity\User;


/**
 * Class WalletController
 * @package Telepay\FinancialApiBundle\Controller\Management\User
 */
class BTCWalletController extends RestApiController{

    /**
     * obtain cypher wallet
     */
    public function createBTCWallet(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();

        if(!$request->request->has('cypher_wallet')) throw new HttpException(400,'Missing parameter cypher_wallet');

        $cypher_wallet = $request->request->get('cypher_wallet');
        if($request->request->has('hd_accounts')){
            $hd_accounts = $request->request->get('hd_accounts');
        }else{
            $hd_accounts = 0;
        }

        $wallet = new BTCWallet();
        $wallet->setUser($user);
        $wallet->setCypherData($cypher_wallet);
        $wallet->setHdAccounts($hd_accounts);

        $em = $this->getDoctrine()->getManager();
        $em->persist($wallet);
        $em->flush();

        return $this->restV2(201, "ok", "BTCWallet created", $wallet);

    }

    /**
     * obtain cypher wallet
     */
    public function read(){

        $user = $this->get('security.context')->getToken()->getUser();

        //obtener los wallets
        $wallet = $user->getBtcWallet();

        return $this->restV2(200, "ok", "Wallet info got successfully", $wallet);

    }

    /**
     * update cypher wallet
     */
    public function updateBTCWallet(Request $request){
        $user = $this->get('security.context')->getToken()->getUser();

        //obtener los wallets
        $wallet = $user->getBtcWallet();

        if($request->request->has('cypher_wallet')){
            $wallet->setCypherData($request->request->get('cypher_wallet'));
        }
        if($request->request->has('hd_accounts')){
            $wallet->setHdAccounts($request->request->get('hd_accounts'));
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($wallet);
        $em->flush();

        return $this->restV2(200, "ok", "Wallet info got successfully", $wallet);

    }

    public function indexAction(){

        $user = $this->get('security.context')->getToken()->getUser();

        //obtener los wallets
        $addresses = $user->getBtcAddresses();

        //TODO NOT return the user data
        $addresses_array  = array();
        foreach($addresses as $address){
            $array = array();
            $array ['id'] = $address->getId();
            $array['address'] =$address->getAddress();
            if($address->getLabel() == ''){
                $array['label'] = 'Unlabeled';
            }else{
                $array['label'] = $address->getLabel();
            }

            $addresses_array[] = $array;
        }

        return $this->restV2(200, "ok", "Wallet adresses info got successfully", $addresses_array);

    }

    public function addAddress(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        if(!$request->request->has('address')) throw new HttpException(400,'Missing parameter address');
        $received_address = $request->get('address');

        $cryptoProvider = $this->get('net.telepay.provider.btc');

        $isValid = $cryptoProvider->validateaddress($received_address);

        if($isValid['isvalid'] != true) throw new HttpException(400,'BTC address not valid');

        $address = new BTCAddresses();
        $address->setUser($user);
        $address->setAddress($received_address);
        $address->setArchived(false);

        if($request->request->has('label')){
            $label = $request->get('label');
            $address->setLabel($label);
        }else{
            $address->setLabel('');
        }

        $em->persist($address);
        $em->flush();

        return $this->restV2(204, "ok");

    }

    public function deleteAddress($id){

        //TODO delete : if an address has btc, can be cancelled??

    }

    public function updateAddress(Request $request, $id){

        $user = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $address = $em->getRepository('TelepayFinancialApiBundle:BTCAddresses')
            ->findOneBy(array(
                'id'    =>  $id,
                'user'  =>  $user->getId()
        ));

        if($request->request->has('label')){
            $label = $request->get('label');
            $address->setLabel($label);
        }

        if($request->request->has('archived')){
            $archived = $request->get('archived');
            $address->setArchived($archived);
        }

        $em->persist($address);
        $em->flush();

        return $this->restV2(204, "ok");
    }

    public function indexDevices(){

        $user = $this->get('security.context')->getToken()->getUser();

        $device = $user->getDevices();

        //die(print_r($device[0],true));

        return $this->restV2(200, "ok", "Devices info got successfully", $device);

    }

    public function addDevice(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();

        if(!$request->request->has('gcm_token')) throw new HttpException(400,'Missing parameter gcm_token');

        $gcm_token = $request->get('gcm_token');

        $device = new Device();
        $device->setUser($user);
        $device->setGcmToken($gcm_token);

        if($request->request->has('label')){
            $device->setLabel($request->get('label'));
        }else{
            $device->setLabel('');
        }

        $em = $this->getDoctrine()->getManager();

        if($user->getGcmGroupKey() == ''){
            try{
                $notification_key = $this->_gcmCreateGroup($user, $gcm_token);
                $user->setGcmGroupKey($notification_key);
                $em->persist($user);
            }catch (HttpException $e){
                throw new HttpException(400,$e->getMessage());
            }

        }else{
            try{
                $this->_gcmManageDevice($user, $gcm_token, 'add');
            }catch (HttpException $e){
                throw new HttpException(400,$e->getMessage());
            }

        }

        $em->persist($device);
        $em->flush();

        $this->_sendGcmNotification($user, 'New device added succesfully');

        return $this->restV2(204, "ok");

    }

    private function _gcmCreateGroup(User $user, $gcm_token){

        $notification_key_name = $user->getId();
        $registration_ids = $gcm_token;

        $params = array(
            'operation'             =>  'create',
            'notification_key_name' =>  'Telepay'.$notification_key_name,
            'registration_ids'      =>  array($registration_ids)
        );

        $params = json_encode($params);

        $header = array(
            'Authorization: key='.$this->container->getParameter('gcm_authorization_key'),
            'Content-Type: application/json',
            'project_id: '.$this->container->getParameter('gcm_project_id')
        );

        // create curl resource
        $ch = curl_init();
        // set url
        curl_setopt($ch, CURLOPT_URL, 'https://android.googleapis.com/gcm/notification');
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$params);
        curl_setopt($ch,CURLOPT_HTTPHEADER, $header);
        // $output contains the output string
        $output = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);

        $response = json_decode($output);

        if(isset($response->error)) throw new HttpException(400,$response->error);

        return $response->notification_key;

    }

    private function _gcmManageDevice(User $user, $gcm_token, $action){

        $notification_key = $user->getGcmGroupKey();
        $registration_ids = $gcm_token;

        $params = array(
            'operation'             =>  $action,
            'notification_key_name' =>  'Telepay'.$user->getId(),
            'notification_key'      =>  $notification_key,
            'registration_ids'      =>  array($registration_ids)
        );

        $params = json_encode($params);

        $header = array(
            'Authorization: key='.$this->container->getParameter('gcm_authorization_key'),
            'Content-Type: application/json',
            'project_id: '.$this->container->getParameter('gcm_project_id')
        );

        // create curl resource
        $ch = curl_init();
        // set url
        curl_setopt($ch, CURLOPT_URL, 'https://android.googleapis.com/gcm/notification');
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$params);
        curl_setopt($ch,CURLOPT_HTTPHEADER, $header);
        // $output contains the output string
        $output = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);

        $response = json_decode($output);

        if(isset($response->error)) throw new HttpException(400,$response->error);

        return true;

    }

    private function _sendGcmNotification(User $user, $message){

        $notification_key = $user->getGcmGroupKey();
        $data = array(
            'message'   =>  $message
        );

        $params = array(
            'to'    =>  $notification_key,
            'data'  =>  $data
        );

        $params = json_encode($params);

        $header = array(
            'Authorization: key='.$this->container->getParameter('gcm_authorization_key'),
            'Content-Type: application/json'
        );

        // create curl resource
        $ch = curl_init();
        // set url
        curl_setopt($ch, CURLOPT_URL, 'https://gcm-http.googleapis.com/gcm/send');
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$params);
        curl_setopt($ch,CURLOPT_HTTPHEADER, $header);
        // $output contains the output string
        $output = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);

        $response = json_decode($output);

        if(isset($response->error)) throw new HttpException(400,$response->error);

        return true;

    }

    public function deleteDevice($id){

        $user = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $device = $em->getRepository('TelepayFinancialApiBundle:Device')->findOneBy(array(
            'id'    =>  $id,
            'user'  =>  $user->getId()
        ));

        if(!$device) throw new HttpException(404,'Device not found');

        $devices = $em->getRepository('TelepayFinancialApiBundle:Device')->findBy(array(
            'user'  =>  $user->getId()
        ));

        try{
            $this->_gcmManageDevice($user,$device->getGcmToken(),'remove');
        }catch (HttpException $e){
            throw new HttpException(400,'Delete device error');
        }

        if(count($devices) == 1){
            $user->setGcmGroupKey('');
            $em->persist($user);
        }

        $em->remove($device);
        $em->flush();

        $this->_sendGcmNotification($user, 'Device removed succesfully');

        return $this->restV2(204, "ok");
    }

    /**
     * sends money to another user
     */
    public function send(){

    }

    /**
     * pays to a commerce integrated with telepay
     */
    public function pay(){

    }

    /**
     * receives money from other users
     */
    public function receive(){

    }

    /**
     * recharges the wallet with any integrated payment method
     */
    public function cashIn(){

    }

    /**
     * sends cash from the wallet to outside
     */
    public function cashOut(){

    }


}