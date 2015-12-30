<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\User;

use Symfony\Component\Security\Core\Util\SecureRandom;
use Telepay\FinancialApiBundle\Entity\Device;
use Telepay\FinancialApiBundle\Entity\TierValidation;
use Telepay\FinancialApiBundle\Entity\TierValidations;
use Telepay\FinancialApiBundle\Entity\User;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

class AccountController extends BaseApiController{

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
    public function read(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();
        $listServices = $user->getServicesList();
        $user->setAllowedServices(
            $this->get('net.telepay.service_provider')->findByCNames($listServices)
        );

        $group = $user->getGroups()[0];

        $group_data = array();
        $group_data['id'] = $group->getId();
        $group_data['name'] = $group->getName();
        $group_data['admin'] = $group->getCreator()->getName();
        $group_data['email'] = $group->getCreator()->getEmail();

        $user->setGroupData($group_data);

        return $this->restV2(200, "ok", "Account info got successfully", $user);
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request,$id=null){

        $user = $this->get('security.context')->getToken()->getUser();
        $id = $user->getId();

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

    /**
     * @Rest\View
     */
    public function registerAction(Request $request){

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

        $confirmation_mail = 0;
        if($request->request->has('email') && $request->request->get('email') != ''){
            $confirmation_mail = 1;
        }else{
            $email = $fake.'@default.com';
            $request->request->add(array('email'=>$email));
        }

        $request->request->add(array('enabled'=>1));
        $request->request->add(array('base64_image'=>''));
        $request->request->add(array('default_currency'=>'EUR'));
        $request->request->add(array('gcm_group_key'=>''));
        $request->request->add(array('services_list'=>array('sample')));

        $resp= parent::createAction($request);

        if($resp->getStatusCode() == 201){
            $em=$this->getDoctrine()->getManager();

            $groupsRepo = $em->getRepository("TelepayFinancialApiBundle:Group");
            $group = $groupsRepo->find($this->container->getParameter('id_group_level_0'));
            if(!$group) throw new HttpException(404,'Group Level 0 not found');

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
                $device->setGcmToken($gcm_token);

                $em->persist($device);
            }

            if($confirmation_mail == 1){
                $tokenManager = $this->container->get('fos_oauth_server.access_token_manager.default');
                $accessToken = $tokenManager->findTokenByToken(
                    $this->container->get('security.context')->getToken()->getToken()
                );
                $client = $accessToken->getClient();
                $urls = $client->getRedirectUris();

                $tokenGenerator = $this->container->get('fos_user.util.token_generator');
                $user->setConfirmationToken($tokenGenerator->generateToken());
                $em->persist($user);
                $em->flush();
                $url = $urls[0].'/user/validation/'.$user->getConfirmationToken();
                $this->_sendEmail('Chip-Chap validation e-mail', $url, $user->getEmail());
            }

            $em->persist($user);
            $em->flush();

            $response = array(
                'id'        =>  $user_id,
                'username'  =>  $username,
                'password'   =>  $password
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

    /**
     * @Rest\View
     */
    public function validateEmail(Request $request){

        $em = $this->getDoctrine()->getManager();

        if(!$request->request->has('confirmation_token')) throw new HttpException(404, 'Param confirmation_token not found');

        $user = $em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
            'confirmationToken' => $request->request->get('confirmation_token')
        ));

        if(!$user) throw new HttpException(400, 'User not found');

        $tierValidation = $em->getRepository('TelepayFinancialApiBundle:TierValidations')->findOneBy(array(
            'user' => $user
        ));

        if(!$tierValidation){
            $tier = new TierValidations();
            $tier->setUser($user);
            $tier->setEmail(true);

            $em->persist($tier);
            $em->flush();
        }else{
            throw new HttpException(409, 'Validation not allowed');
        }

        return $this->restV2(204,"ok", "Validation successfully");

    }

    private function _sendEmail($subject, $body, $to){

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom('no-reply@chip-chap.com')
            ->setTo(array(
                $to
            ))
            ->setBody(
                $this->container->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:registerconfirm.html.twig',
                        array(
                            'message'        =>  $body
                        )
                    )
            )
            ->setContentType('text/html');

        $this->container->get('mailer')->send($message);
    }

}