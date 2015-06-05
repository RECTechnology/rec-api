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
use Telepay\FinancialApiBundle\Entity\Device;


/**
 * Class WalletController
 * @package Telepay\FinancialApiBundle\Controller\Management\User
 */
class BTCWalletController extends RestApiController{


    /**
     * obtain cypher wallet
     */
    public function read(){

        $user = $this->get('security.context')->getToken()->getUser();

        //obtener los wallets
        $wallet = $user->getBtcWallet();

        //TODO NOT return the user data

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

        $device = $user->getDevice();

        //die(print_r($device[0],true));

        return $this->restV2(200, "ok", "Devices info got successfully", $device);

    }

    public function addDevice(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();

        if(!$request->request->has('device_id')) throw new HttpException(400,'Missing parameter device_id');

        $device_id = $request->get('device_id');

        $device = new Device();
        $device->setUser($user);
        $device->setDeviceId($device_id);

        if($request->request->has('label')){
            $device->setLabel($request->get('label'));
        }else{
            $device->setLabel('');
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($device);
        $em->flush();

        return $this->restV2(204, "ok");

    }

    public function deleteDevice($id){

        $user = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $device = $em->getRepository('TelepayFinancialApiBundle:Device')->findOneBy(array(
            'id'    =>  $id,
            'user'  =>  $user->getId()
        ));

        if(!$device) throw new HttpException(404,'Device not found');

        $em->remove($device);
        $em->flush();

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