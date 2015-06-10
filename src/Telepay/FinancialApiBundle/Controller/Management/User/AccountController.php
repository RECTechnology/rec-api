<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\User;

use Symfony\Component\Security\Core\Util\SecureRandom;
use Telepay\FinancialApiBundle\Entity\BTCWallet;
use Telepay\FinancialApiBundle\Entity\Device;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Entity\User;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

class AccountController extends BaseApiController{

    /**
     * @Rest\View
     */
    public function read(Request $request){
        $user = $this->get('security.context')->getToken()->getUser();
        $user->setAllowedServices(
            $this->get('net.telepay.service_provider')->findByRoles($user->getRoles())
        );
        //die(print_r($user->getLimitCount()[0]->getUser(), true));
        //return $this->restV2(200, "ok", "OLE", $user->getLimitCount()[0]);


        return $this->restV2(200, "ok", "Account info got successfully", $user);
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request,$id=null){

        $user = $this->get('security.context')->getToken()->getUser();
        $id=$user->getId();

        if($request->request->has('password')){
            if($request->request->has('repassword')){
                $userManager = $this->container->get('access_key.security.user_provider');
                $user = $userManager->loadUserById($id);
                $user->setPlainPassword($request->get('password'));
                $userManager->updatePassword($user);
                $request->request->remove('password');
                $request->request->remove('repassword');
            }else{
                throw new HttpException(404,'Parameter repassword not found');
            }

        }

        return parent::updateAction($request, $id);

    }

    /**
     * @Rest\View
     */
    public function speed(Request $request){
        $end_time = new \MongoDate();
        $start_time = new \MongoDate($end_time->sec-3600);

        $dm = $this->get('doctrine_mongodb')->getManager();

        $userId = $this->get('security.context')
            ->getToken()->getUser()->getId();

        $last1hTrans = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('user')->equals($userId)
            ->field('mode')->equals(true)
            ->field('timeIn')->gt($start_time)
            ->field('timeIn')->lt($end_time)
            ->field('successful')->equals(true)
            ->count()
            ->getQuery()
            ->execute();
        return $this->restV2(
            200,"ok", "Last hour speed got successfully", $last1hTrans
        );
    }

    /**
     * @Rest\View
     */
    public function analytics(Request $request){

        if($request->query->has('start_time') && is_int($request->query->get('start_time')))
            $start_time = new \MongoDate($request->query->get('start_time'));
        else $start_time = new \MongoDate(strtotime(date('Y-m-01 00:00:00'))); // 1th of month

        if($request->query->has('end_time') && is_int($request->query->get('end_time')))
            $end_time = new \MongoDate($request->query->get('end_time'));
        else $end_time = new \MongoDate(strtotime(date('Y-m-01 00:00:00'))+31*24*3600); // 1th of next month

        $interval = 'day';

        $env = true;

        $jsAssocs = array(
            'day' => 'getDate()'
        );

        if(!array_key_exists($interval, $jsAssocs))
            throw new HttpException(400, "Bad interval");

        $dm = $this->get('doctrine_mongodb')->getManager();

        $userId = $this->get('security.context')
            ->getToken()->getUser()->getId();

        $result = $dm->createQueryBuilder('TelepayFinancialApiBundle:Transaction')
            ->field('user')->equals($userId)
            ->field('mode')->equals($env)
            ->field('timeIn')->gt($start_time)
            ->field('timeIn')->lt($end_time)
            ->group(
                new \MongoCode('
                    function(trans){
                        return {
                            '.$interval.': trans.timeIn.'.$jsAssocs[$interval].'
                        };
                    }
                '),
                array(
                    's1'=>0,
                    's2'=>0,
                    's3'=>0,
                    's4'=>0,
                    's5'=>0,
                    's6'=>0,
                    's7'=>0,
                    's8'=>0,
                    's9'=>0,
                    's10'=>0,
                    's11'=>0,
                    's12'=>0,
                    's13'=>0
                )
            )
            ->reduce('
                function(curr, result){
                    if(curr.successful)
                        switch(curr.service){
                            case 1:
                                result.s1++;
                                break;
                            case 2:
                                result.s2++;
                                break;
                            case 3:
                                result.s3++;
                                break;
                            case 4:
                                result.s4++;
                                break;
                            case 5:
                                result.s5++;
                                break;
                            case 6:
                                result.s6++;
                                break;
                            case 7:
                                result.s7++;
                                break;
                            case 8:
                                result.s8++;
                                break;
                            case 9:
                                result.s9++;
                                break;
                            case 10:
                                result.s10++;
                                break;
                            case 11:
                                result.s11++;
                                break;
                            case 12:
                                result.s12++;
                                break;
                            case 13:
                                result.s13++;
                                break;
                        }
                }
            ')
            ->getQuery()
            ->execute();

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total'=>$result->getCommandResult()['count'],
                'elements'=>$result->toArray()
            )
        );
    }

    /**
     * @Rest\View
     */
    public function updateCurrency(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();

        if($request->request->has('currency'))
            $currency=$request->request->get('currency');
        else
            throw new HttpException(404,'currency not found');

        $em=$this->getDoctrine()->getManager();

        $user->setDefaultCurrency(strtoupper($currency));

        $em->persist($user);
        $em->flush();

        return $this->restV2(200,"ok", "Account info got successfully", $user);
    }


    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:User";
    }

    function getNewEntity()
    {
        return new User();
    }

    /**
     * @Rest\View
     */
    public function registerAction(Request $request){

        //cypher_wallet is mandatory
        if(!$request->request->has('cypher_wallet')) throw new HttpException(400, "Paramter cypher_wallet is missing.");
        $cypher_wallet = $request->request->get('cypher_wallet');
        $request->request->remove('cypher_wallet');

        //device_id is optional
        $device_id = null;
        $gcm_token = null;
        if($request->request->has('device_id')){
            if(!$request->request->has('gcm_token')) throw new HttpException(400, 'Missing parameter gcm_token');
            $gcm_token = $request->request->get(('gcm_token'));
            $device_id = $request->request->get('device_id');
            $request->request->remove('device_id');
            $request->request->remove('gcm_token');
        }

        //password is optional
        if(!$request->request->has('password')){
            //nos lo inventamos
            $password = Uuid::uuid1()->toString();
            $request->request->add(array('plain_password'=>$password));

        }else{
            if(!$request->request->has('repassword')){
                throw new HttpException(400, "Missing parameter 'repassword'");
            }else{
                $password = $request->get('password');
                $repassword = $request->get('repassword');
                if($password!=$repassword) throw new HttpException(400, "Password and repassword are differents.");
                $request->request->remove('password');
                $request->request->remove('repassword');
                $request->request->add(array('plain_password'=>$password));
            }

        }

        $fake = Uuid::uuid1()->toString();
        //username fake
        if(!$request->request->has('username')){
            //invent the username
            $username = $fake;
            $request->request->add(array('username'=>$fake));
        }else{
            $username = $request->get('username');
        }

        //name fake
        if(!$request->request->has('name')){
            //invent the name
            $request->request->add(array('name'=>$fake));
        }

        if(!$request->request->has('phone')){
            $request->request->add(array('phone'=>''));
            $request->request->add(array('prefix'=>''));
        }else{
            if(!$request->request->has('prefix')){
                throw new HttpException(400, "Missing parameter 'prefix'");
            }
        }

        if(!$request->request->has('email')){
            $email = $fake.'@default.com';
            $request->request->add(array('email'=>$email));
        }

        $request->request->add(array('enabled'=>1));
        $request->request->add(array('base64_image'=>''));
        $request->request->add(array('default_currency'=>'EUR'));
        $request->request->add(array('gcm_group_key'=>''));

        $resp= parent::createAction($request);

        if($resp->getStatusCode() == 201){
            $em=$this->getDoctrine()->getManager();

            $groupsRepo = $em->getRepository("TelepayFinancialApiBundle:Group");
            $group = $groupsRepo->findOneBy(array('name' => 'Level0'));
            if(!$group) throw new HttpException(404,'Group Level0 not found');

            $usersRepo = $em->getRepository("TelepayFinancialApiBundle:User");
            $data = $resp->getContent();
            $data = json_decode($data);
            $data = $data->data;
            $user_id=$data->id;

            $user = $usersRepo->findOneBy(array('id'=>$user_id));

            $currencies=Currency::$LISTA;

            foreach($currencies as $currency){
                $user_wallet = new UserWallet();
                $user_wallet->setBalance(0);
                $user_wallet->setAvailable(0);
                $user_wallet->setCurrency($currency);
                $user_wallet->setUser($user);
                $em->persist($user_wallet);
            }

            $user->addGroup($group);

            $em->persist($user);
            $em->flush();

            if( $device_id != null){
                $device = new Device();
                $device->setUser($user);
                $device->setDeviceId($device_id);
                $device->setGcmToken($gcm_token);

                $em->persist($device);
            }

            $btc_wallet = new BTCWallet();
            $btc_wallet->setCypherData($cypher_wallet);
            $btc_wallet->setUser($user);

            $em->persist($btc_wallet);
            $em->flush();

            $response = array(
                'id'        =>  $user_id,
                'username'  =>  $username,
                'password'  =>  $password
            );

            return $this->restV2(201,"ok", "Request successful", $response);
        }else{

            return $resp;
        }

    }

    /**
     * @Rest\View
     */
    public function resetCredentials(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();

        $generator = new SecureRandom();
        $access_key=sha1($generator->nextBytes(32));
        $access_secret=base64_encode($generator->nextBytes(32));

        $user->setAccessSecret($access_secret);
        $user->setAccessKey($access_key);

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        return $this->restV2(204,"ok", "Updated successfully");

    }

}