<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\User;

use Doctrine\DBAL\DBALException;
use Symfony\Component\Security\Core\Util\SecureRandom;
use Telepay\FinancialApiBundle\Entity\CashInTokens;
use Telepay\FinancialApiBundle\Entity\Device;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\KYC;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\LimitCount;
use Telepay\FinancialApiBundle\Entity\POS;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Entity\TierValidations;
use Telepay\FinancialApiBundle\Entity\User;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\UserGroup;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\EventListener\KycListener;
use Telepay\FinancialApiBundle\Financial\Currency;
use Telepay\FinancialApiBundle\Controller\Google2FA;
use WebSocket\Exception;

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
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $user->setRoles($user->getRoles());
        $group = $this->get('security.context')->getToken()->getUser()->getActiveGroup();
        $group_data = $group->getUserView();
        $user->setGroupData($group_data);
        $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user
        ));
        $user->setKycData($kyc);
        $em->persist($user);
        return $this->restV2(200, "ok", "Account info got successfully", $user);
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request,$id = null){

        $user = $this->get('security.context')->getToken()->getUser();
        $id = $user->getId();

        if($request->request->has('password')){
            if($request->request->has('repassword')){
                if($request->request->has('old_password')){
                    $userManager = $this->container->get('access_key.security.user_provider');
                    $user = $userManager->loadUserById($id);
                    $encoder_service = $this->get('security.encoder_factory');
                    $encoder = $encoder_service->getEncoder($user);
                    $encoded_pass = $encoder->encodePassword($request->request->get('old_password'), $user->getSalt());

                    if($encoded_pass != $user->getPassword()) throw new HttpException(404, 'Bad old_password');

                    $user->setPlainPassword($request->get('password'));
                    $userManager->updatePassword($user);
                    $request->request->remove('password');
                    $request->request->remove('repassword');
                    $request->request->remove('old_password');
                }else{
                    throw new HttpException(404,'Parameter old_password not found');
                }

            }else{
                throw new HttpException(404,'Parameter repassword not found');
            }

        }

        return parent::updateAction($request, $id);

    }

    /**
     * @Rest\View
     */
    public function setImage(Request $request){

        $id = $this->get('security.context')->getToken()->getUser()->getId();

        if($request->request->has('base64_image')) $base64Image = $request->request->get('base64_image');
        else throw new HttpException(400, "Missing parameter 'base64_image'");

        $repo = $this->getRepository();
        $user = $repo->findOneBy(array('id'=>$id));
        if(empty($user)) throw new HttpException(404, "User Not found");
        $user->setBase64Image($base64Image);

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);

        try{
            $em->flush();
            return $this->rest(
                204,
                "Image changed successfully"
            );
        } catch(DBALException $e){
            if(preg_match('/SQLSTATE\[23000\]/',$e->getMessage()))
                throw new HttpException(409, "Duplicated resource");
            else
                throw new HttpException(500, "Unknown error occurred when save");
        }
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

        $userGroup = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        if($request->request->has('currency'))
            $currency = $request->request->get('currency');
        else
            throw new HttpException(404,'currency not found');

        $em = $this->getDoctrine()->getManager();

        $userGroup->setDefaultCurrency(strtoupper($currency));
        $em->persist($userGroup);
        $em->flush();

        return $this->restV2(200,"ok", "Account info got successfully", $userGroup);
    }

    /**
     * @Rest\View
     */
    public function changeGroup(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();
        $user->setRoles($user->getRoles());

        if($request->request->has('group_id'))
            $group_id = $request->request->get('group_id');
        else
            throw new HttpException(404,'group_id not found');

        $userGroup = false;
        foreach($user->getGroups() as $group){
            if($group->getId() == $group_id){
                $userGroup = $group;
            }
        }

        if(!$userGroup){
            throw new HttpException(404,'Group selected is not accessible for you');
        }

        $em = $this->getDoctrine()->getManager();

        $user->setActiveGroup($userGroup);
        $user->setRoles($user->getRoles());
        $em->persist($user);
        $em->flush();

        $group_data = array();
        $group_data['id'] = $userGroup->getId();
        $group_data['name'] = $userGroup->getName();
        $group_data['default_currency'] = $userGroup->getDefaultCurrency();
        $group_data['base64_image'] = $userGroup->getBase64Image();
        $group_data['admin'] = $userGroup->getGroupCreator()->getName();
        $group_data['email'] = $userGroup->getGroupCreator()->getEmail();

        $user->setGroupData($group_data);

        return $this->restV2(200,"ok", "Active group changed successfully", $user);
    }

    /**
     * @Rest\View
     */
    public function active2faAction(Request $request){
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $user->setTwoFactorAuthentication(true);
        if($user->getTwoFactorCode() == ""){
            $Google2FA = new Google2FA();
            $user->setTwoFactorCode($Google2FA->generate_secret_key());
        }
        $em->persist($user);
        $em->flush();
        return $this->restV2(200,"ok", "Account info got successfully", $user);
    }

    /**
     * @Rest\View
     */
    public function deactive2faAction(Request $request){
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $user->setTwoFactorAuthentication(false);
        $em->persist($user);
        $em->flush();
        return $this->restV2(200,"ok", "Account info got successfully", $user);
    }

    /**
     * @Rest\View
     */
    public function update2faAction(Request $request){
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $Google2FA = new Google2FA();
        $user->setTwoFactorCode($Google2FA->generate_secret_key());
        $em->persist($user);
        $em->flush();
        return $this->restV2(200,"ok", "Account info got successfully", $user);
    }

    /**
     * @Rest\View
     */
    public function registerKYCAction(Request $request){
        if(!$request->request->has('company') || $request->get('company')==""){
            $company = "chipchap";
        }
        else{
            $company = $request->get('company');
        }
        $request->request->remove('company');

        if(!$request->request->has('email')){
            throw new HttpException(400, "Missing parameter 'email'");
        }
        else{
            $email = $request->request->get('email');
        }
        if(!$request->request->has('password')){
            throw new HttpException(400, "Missing parameter 'password'");
        }
        if(!$request->request->has('repassword')){
            throw new HttpException(400, "Missing parameter 'repassword'");
        }
        else{
            $password = $request->get('password');
            $repassword = $request->get('repassword');
            if($password!=$repassword) throw new HttpException(400, "Password and repassword are differents.");
            $request->request->add(array('plain_password'=>$password));
            $request->request->remove('password');
            $request->request->remove('repassword');
        }
        $request->request->add(array('username'=>$email));
        $request->request->add(array('name'=>''));
        $request->request->add(array('email'=>$email));
        $request->request->add(array('enabled'=>1));
        $request->request->add(array('base64_image'=>''));
        $request->request->add(array('default_currency'=>'EUR'));
        $request->request->add(array('gcm_group_key'=>''));
        $request->request->add(array('services_list'=>array('sample')));
        $request->request->add(array('methods_list'=>array('sample')));
        $resp= parent::createAction($request);

        if($resp->getStatusCode() == 201) {
            $em = $this->getDoctrine()->getManager();

            $usersRepo = $em->getRepository("TelepayFinancialApiBundle:User");
            $data = $resp->getContent();
            $data = json_decode($data);
            $data = $data->data;
            $user_id = $data->id;

            $user = $usersRepo->findOneBy(array('id'=>$user_id));

            $user_kyc = new KYC();
            $user_kyc->setEmail($email);
            $user_kyc->setUser($user);
            $em->persist($user_kyc);
            $user->addRole('ROLE_KYC');
            $em->persist($user);
            $em->flush();

            $url = $this->container->getParameter('web_app_url');
            $tokenGenerator = $this->container->get('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
            $em->persist($user);
            $em->flush();
            if($company == "holytransaction"){
                $url = "https://holytransaction.trade/";
                $url = $url.'?user_token='.$user->getConfirmationToken();
                $this->_sendEmail('Holy Transaction validation e-mail', $url, $user->getEmail(), 'register_kyc_holy');
            }
            else{
                $url = $this->container->getParameter('web_app_url');
                $url = $url.'?user_token='.$user->getConfirmationToken();
                $this->_sendEmail('Chip-Chap validation e-mail', $url, $user->getEmail(), 'register_kyc');
            }
            $em->persist($user);
            $em->flush();

            $response = array(
                'id'        =>  $user_id,
                'username'  =>  $email,
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
    public function sentValidationEmailAction(Request $request){
        if(!$request->request->has('email')){
            throw new HttpException(400, "Missing parameter 'email'");
        }
        else{
            $email = $request->request->get('email');
        }
        $url = $this->container->getParameter('base_panel_url');
        $tokenGenerator = $this->container->get('fos_user.util.token_generator');
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository($this->getRepositoryName())->findOneBy(array(
            'email'  =>  $email
        ));
        if(!$user){
            $response = array(
                'email'  =>  $email,
            );
            return $this->restV2(201,"ok", "Request successful.", $response);
        }
        $user->setConfirmationToken($tokenGenerator->generateToken());
        $url = $url.'/user/validation/'.$user->getConfirmationToken();
        $this->_sendEmail('Chip-Chap validation e-mail', $url, $user->getEmail(), 'register');
        $em->persist($user);
        $em->flush();
        $response = array(
            'email'  =>  $email,
        );
        return $this->restV2(201,"ok", "Request successful", $response);
    }

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

        if($request->request->has('phone')){
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
//        $request->request->add(array('default_currency'=>'EUR'));
        $request->request->add(array('gcm_group_key'=>''));
//        $request->request->add(array('services_list'=>array('sample')));
//        $request->request->add(array('methods_list'=>array('sample')));

        if($request->request->has('captcha')){
            $captcha = $request->request->get('captcha');
            $request->request->remove('captcha');

            $g_url = 'https://www.google.com/recaptcha/api/siteverify?secret=6LeWBBUTAAAAAB_z2gTNI2yu4jerUql7WN_t29Aj&response='.$captcha;
            $g_ch = curl_init();
            curl_setopt($g_ch,CURLOPT_URL, $g_url);
            curl_setopt($g_ch,CURLOPT_RETURNTRANSFER,true);
            $g_result = curl_exec($g_ch);
            curl_close($g_ch);
            $g_result = json_decode($g_result,true);

            if($g_result['success'] != 1){
                throw new HttpException(403, 'You are a bot');
            }

        }

        $resp= parent::createAction($request);

        if($resp->getStatusCode() == 201){
            $em = $this->getDoctrine()->getManager();

            $groupsRepo = $em->getRepository("TelepayFinancialApiBundle:Group");
            $group = $groupsRepo->find($this->container->getParameter('id_group_level_0'));
            if(!$group) throw new HttpException(404,'Group Level 0 not found');

            $usersRepo = $em->getRepository("TelepayFinancialApiBundle:User");
            $data = $resp->getContent();
            $data = json_decode($data);
            $data = $data->data;
            $user_id = $data->id;

            $user = $usersRepo->findOneBy(array('id'=>$user_id));
            $user->addGroup($group);
            $em->persist($user);
            $em->flush();

            $user_kyc = new KYC();
            $user_kyc->setEmail($user->getEmail());
            $user_kyc->setUser($user);
            if($request->request->has('phone')){
                $phone_info = array(
                    "prefix" => $request->request->get('prefix'),
                    "number" => $request->request->get('phone')
                );
                $user_kyc->setPhone(json_encode($phone_info));
            }
            $em->persist($user_kyc);
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
                $url = $this->container->getParameter('base_panel_url');

                $tokenGenerator = $this->container->get('fos_user.util.token_generator');
                $user->setConfirmationToken($tokenGenerator->generateToken());
                $em->persist($user);
                $em->flush();
                $url = $url.'/user/validation/'.$user->getConfirmationToken();
                $this->_sendEmail('Chip-Chap validation e-mail', $url, $user->getEmail(), 'register');
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
    public function registerCommerceAction(Request $request, $type){
        $paramNames = array(
            'username',
            'email',
            'company_name',
            'password',
            'repassword'
        );

        $valid_types = array('prestashop', 'android');
        if(!in_array($type, $valid_types)) throw new HttpException(404, 'Type not valid');

        $params = array();
        foreach($paramNames as $param){
            if($request->request->has($param) && $request->request->get($param)!=''){
                $params[$param] = $request->request->get($param);
            }else{
                throw new HttpException(404, 'Param ' . $param . ' not found');
            }
        }
        if($params['password'] != $params['repassword']) throw new HttpException(404, 'Password and repassword are differents');
        $params['plain_password'] = $params['password'];
        unset($params['password']);
        unset($params['repassword']);

        $user_creator_id = $this->container->getParameter('default_user_creator_commerce_' . $type);
        $company_creator_id = $this->container->getParameter('default_company_creator_commerce_' . $type);

        $em = $this->getDoctrine()->getManager();
        $userCreator = $em->getRepository('TelepayFinancialApiBundle:User')->find($user_creator_id);
        $companyCreator = $em->getRepository('TelepayFinancialApiBundle:Group')->find($company_creator_id);

        //create company
        $company = new Group();
        $company->setName($params['company_name']);
        $company->setActive(true);
        $company->setCreator($userCreator);
        $company->setGroupCreator($companyCreator);
        $company->setRoles(array('ROLE_COMPANY'));
        $company->setDefaultCurrency('EUR');
        $company->setEmail($params['email']);
        $company->setMethodsList('');

        $em->persist($company);

        //create wallets for this company
        $currencies = Currency::$ALL;
        foreach($currencies as $currency){
            $userWallet = new UserWallet();
            $userWallet->setBalance(0);
            $userWallet->setAvailable(0);
            $userWallet->setCurrency(strtoupper($currency));
            $userWallet->setGroup($company);

            $em->persist($userWallet);
        }

        //CRETAE EXCHANGES limits and fees
        $exchanges = $this->container->get('net.telepay.exchange_provider')->findAll();

        foreach($exchanges as $exchange){
            //create limit for this group
            $limit = new LimitDefinition();
            $limit->setDay(0);
            $limit->setWeek(0);
            $limit->setMonth(0);
            $limit->setYear(0);
            $limit->setTotal(0);
            $limit->setSingle(0);
            $limit->setCname('exchange_'.$exchange->getCname());
            $limit->setCurrency($exchange->getCurrencyOut());
            $limit->setGroup($company);
            //create fee for this group
            $fee = new ServiceFee();
            $fee->setFixed(0);
            $fee->setVariable(1);
            $fee->setCurrency($exchange->getCurrencyOut());
            $fee->setServiceName('exchange_'.$exchange->getCname());
            $fee->setGroup($company);

            $em->persist($limit);
            $em->persist($fee);

        }

        //create user
        $user = new User();
        $user->setPlainPassword($params['plain_password']);
        $user->setEmail($params['email']);
        $user->setRoles(array('ROLE_USER'));
        $user->setName($params['username']);
        $user->setUsername($params['username']);
        $user->setActiveGroup($company);
        $user->setBase64Image('');
        $user->setEnabled(false);

        $url = $this->container->getParameter('base_panel_url');

        $tokenGenerator = $this->container->get('fos_user.util.token_generator');
        $user->setConfirmationToken($tokenGenerator->generateToken());
        $em->persist($user);
        $em->flush();
        $url = $url.'/user/validation/'.$user->getConfirmationToken();
        $this->_sendEmail('Chip-Chap validation e-mail', $url, $user->getEmail(), 'register');

        //Add user to group with admin role
        $userGroup = new UserGroup();
        $userGroup->setUser($user);
        $userGroup->setGroup($company);
        $userGroup->setRoles(array('ROLE_ADMIN'));

        $em->persist($userGroup);
        $em->flush();

        if($type == 'prestashop') {
            //create POS-Btc
            $pos = new POS();
            $pos->setName($params['company_name']);
            $pos->setActive(true);
            $pos->setCurrency('BTC');
            $pos->setExpiresIn(1200);
            $pos->setGroup($company);
            $pos->setType('BTC');
            $pos->setPosId(uniqid());
            $pos->setCname('POS-BTC');

            $em->persist($pos);
            $em->flush();

            $response = array(
                'user' => $user,
                'company' => $company,
                'pos' => $pos
            );
        }
        elseif($type == 'android'){
            $methodsList = array('btc-in', 'fac-in', 'btc-out', 'fac-out');
            $company->setMethodsList($methodsList);
            $em->persist($company);

            foreach($methodsList as $method){
                $method_ex = explode('-', $method);
                $meth = $method_ex[0];
                $type = $method_ex[1];

                $daily = -1;
                if($type == 'out'){
                    if($meth == 'btc'){
                        $daily = 100000000;
                    }else{
                        $daily = 1000000000000;
                    }

                }
                //create new ServiceFee
                $newFee = new ServiceFee();
                $newFee->setGroup($company);
                $newFee->setFixed(0);
                $newFee->setVariable(0);
                $newFee->setServiceName($method);
                $newFee->setCurrency(strtoupper($meth));
                $em->persist($newFee);

                //create new LimitDefinition
                $newLimit = new LimitDefinition();
                $newLimit->setGroup($company);
                $newLimit->setCurrency(strtoupper($meth));
                $newLimit->setCname($method);
                $newLimit->setDay($daily);
                $newLimit->setWeek(-1);
                $newLimit->setMonth(-1);
                $newLimit->setYear(-1);
                $newLimit->setSingle(-1);
                $newLimit->setTotal(-1);
                $em->persist($newLimit);

                //create new LimitCount
                $newCount = new LimitCount();
                $newCount->setDay(0);
                $newCount->setWeek(0);
                $newCount->setMonth(0);
                $newCount->setYear(0);
                $newCount->setSingle(0);
                $newCount->setTotal(0);
                $newCount->setCname($method);
                $newCount->setGroup($company);
                $em->persist($newCount);
            }

            //create new fixed address for bitcoin and return
            $btcAddress = new CashInTokens();
            $btcAddress->setCurrency(Currency::$BTC);
            $btcAddress->setCompany($company);
            $btcAddress->setLabel('BTC account');
            $btcAddress->setMethod('btc-in');
            $btcAddress->setExpiresIn(-1);
            $btcAddress->setStatus(CashInTokens::$STATUS_ACTIVE);
            $methodDriver = $this->get('net.telepay.in.btc.v1');
            $paymentInfo = $methodDriver->getPayInInfo(0);
            $token = $paymentInfo['address'];
            $btcAddress->setToken($token);
            $em->persist($btcAddress);

            //create new fixed address for faircoin and return
            $facAddress = new CashInTokens();
            $facAddress->setCurrency(Currency::$FAC);
            $facAddress->setCompany($company);
            $facAddress->setLabel('FAC account');
            $facAddress->setMethod('fac-in');
            $facAddress->setExpiresIn(-1);
            $facAddress->setStatus(CashInTokens::$STATUS_ACTIVE);
            $methodDriver = $this->get('net.telepay.in.fac.v1');
            $paymentInfo = $methodDriver->getPayInInfo(0);
            $token = $paymentInfo['address'];
            $facAddress->setToken($token);
            $em->persist($facAddress);

            $response = array(
                'user' => $user,
                'company' => $company,
                'btc_address'   =>  $btcAddress,
                'fac_address'   =>  $facAddress
            );
        }
        $em->flush();

        return $this->restV2(201,"ok", "Request successful", $response);
    }

    /**
     * @Rest\View
     */
    public function resetCredentials(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();

        $generator = new SecureRandom();
        $access_key = sha1($generator->nextBytes(32));
        $access_secret = base64_encode($generator->nextBytes(32));

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
    public function passwordRecoveryRequest(Request $request, $param, $version_number){
        $company = "chipchap";
        if(!$request->query->has('company') || $request->query->get('company')==""){
            $company = "chipchap";
        }
        else{
            $company = $request->query->get('company');
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository($this->getRepositoryName())->findOneBy(array(
            'username'  =>  $param
        ));

        if(!$user){
            $user = $em->getRepository($this->getRepositoryName())->findOneBy(array(
                'email'  =>  $param
            ));
        }

        if(!$user) throw new HttpException(404, 'User not found');
        //TODO check if the user has validated his email if not OUT

        //generate a token to add to the return url
        $tokenGenerator = $this->container->get('fos_user.util.token_generator');
        $user->setRecoverPasswordToken($tokenGenerator->generateToken());
        $user->setPasswordRequestedAt(new \DateTime());
        $em->persist($user);
        $em->flush();

        if($version_number == '2'){
            if($company == "holytransaction"){
                $url = 'https://holytransaction.trade/login?password_recovery='.$user->getRecoverPasswordToken();
                //send email with a link to recover the password
                $this->_sendEmail('Holy Transaction recover your password', $url, $user->getEmail(), 'recover_holy');
            }
            else{
                $url = $this->container->getParameter('web_app_url').'login?password_recovery='.$user->getRecoverPasswordToken();
                //send email with a link to recover the password
                $this->_sendEmail('Chip-Chap recover your password', $url, $user->getEmail(), 'recover');
            }
        }
        else {
            $url = $this->container->getParameter('base_panel_url').'/user/password_recovery/'.$user->getRecoverPasswordToken();
            //send email with a link to recover the password
            $this->_sendEmail('Chip-Chap recover your password', $url, $user->getEmail(), 'recover');
        }

        return $this->restV2(200,"ok", "Request successful");

    }

    /**
     * @Rest\View
     */
    public function passwordRecovery(Request $request){

        $paramNames = array(
            'token',
            'password',
            'repassword'
        );

        $params = array();

        foreach($paramNames as $paramName){
            if($request->request->has($paramName)){
                $params[$paramName] = $request->request->get($paramName);
            }else{
                throw new HttpException(404, 'Parameter '.$paramName.' not found');
            }
        }

        if($params['password']==''){
            throw new HttpException('Paramater password not found');
        }

        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository($this->getRepositoryName())->findOneBy(array(
            'recover_password_token' => $params['token']
        ));

        if(!$user) throw new HttpException(404, 'User not found');

        if($user->isPasswordRequestNonExpired(1200)){

            if($params['password'] != $params['repassword']) throw new HttpException('Password and repassword are differents');

            $userManager = $this->container->get('access_key.security.user_provider');
            $user = $userManager->loadUserById($user->getId());

            $user->setPlainPassword($request->get('password'));
            $userManager->updatePassword($user);

            $em->persist($user);
            $em->flush();

        }else{
            throw new HttpException(404, 'Expired token');
        }

        return $this->restV2(204,"ok", "password recovered");

    }

    /**
     * @Rest\View
     */
    public function kycSave(Request $request){
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        if($request->request->has('name') && $request->request->get('name')!=''){
            $user->setName($request->request->get('name'));
            $em->persist($user);
        }

        $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user
        ));
        if(!$kyc){
            $kyc = new KYC();
            $kyc->setEmail($user->getEmail());
            $kyc->setUser($user);
        }

        if($request->request->has('email') && $request->request->get('email')!=''){
            $user->setEmail($request->request->get('email'));
            $em->persist($user);
            $kyc->setEmail($request->request->get('email'));
            $kyc->setEmailValidated(false);
            $em->persist($kyc);
        }

        if($request->request->has('last_name') && $request->request->get('last_name')!=''){
            $kyc->setLastName($request->request->get('last_name'));
            $em->persist($kyc);
        }

        if($request->request->has('date_birth') && $request->request->get('date_birth')!=''){
            $kyc->setDateBirth($request->request->get('date_birth'));
            $em->persist($kyc);
        }

        if($request->request->get('phone') != '' || $request->request->get('prefix') != ''){
            $prefix = $request->request->get('prefix');
            $phone = $request->request->get('phone');
            $old_phone = $kyc->getPhone();
            if($old_phone == ''){
                $old_phone = array(
                    "prefix" => '',
                    "number" => ''
                );
            }
            else{
                $old_phone = json_decode($old_phone);
            }
            $phone_info = array(
                "prefix" => $prefix != ''?$prefix:$old_phone['prefix'],
                "number" => $phone != ''?$phone:$old_phone['number']
            );
            $kyc->setPhone(json_encode($phone_info));
            $kyc->setPhoneValidated(false);
            $em->persist($kyc);
        }

        $em->flush();
        return $this->restV2(204, "ok", "KYC Info saved");
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

        $user->setEnabled(true);
        $em->persist($user);
        $em->flush();

        $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user
        ));

        if($kyc){
            $kyc->setEmailValidated(true);
            $em->persist($kyc);
            $em->flush();
        }

        $response = array(
            'username'  =>  $user->getUsername(),
            'email'     =>  $user->getEmail()
        );

        return $this->restV2(201,"ok", "Validation email succesfully", $response);

    }

    /**
     * @Rest\View
     */
//    public function checkExistentUser(Request $request, $username){
//
//        $userRepo = $this->container->get($this->getRepositoryName());
//        $qb = $userRepo->createQueryBuilder('q')
//            ->field('username')->equal($username);
//
//    }

    private function _sendEmail($subject, $body, $to, $action){
        $from = 'no-reply@chip-chap.com';
        $mailer = 'mailer';
        if($action == 'register'){
            $template = 'TelepayFinancialApiBundle:Email:registerconfirm.html.twig';
        }elseif($action == 'recover'){
            $template = 'TelepayFinancialApiBundle:Email:recoverpassword.html.twig';
        }elseif($action == 'recover_holy'){
            $template = 'TelepayFinancialApiBundle:Email:recoverpasswordholy.html.twig';
            $from = 'no-reply@holytransaction.trade';
            $mailer = 'swiftmailer.mailer.holy_mailer';
        }elseif($action == 'register_kyc'){
            $template = 'TelepayFinancialApiBundle:Email:registerconfirmkyc.html.twig';
        }elseif($action == 'register_kyc_holy'){
            $template = 'TelepayFinancialApiBundle:Email:registerconfirmkycholy.html.twig';
            $from = 'no-reply@holytransaction.trade';
            $mailer = 'swiftmailer.mailer.holy_mailer';
        }else{
            $template = 'TelepayFinancialApiBundle:Email:registerconfirm.html.twig';
        }
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo(array(
                $to
            ))
            ->setBody(
                $this->container->get('templating')
                    ->render($template,
                        array(
                            'message'        =>  $body
                        )
                    )
            )
            ->setContentType('text/html');

        $this->container->get($mailer)->send($message);
    }

    /**
     * @Rest\View
     */
    public function indexCompanies(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();
        $repo = $this->getDoctrine()->getRepository("TelepayFinancialApiBundle:UserGroup");
        $data = $repo->findBy(array('user'=>$user));

        $all = array();
        foreach($data as $userCompany){
            $data_company = array(
                'company' => $userCompany->getGroup(),
                'roles' => $userCompany->getRoles()
            );
            $all[] = $data_company;
        }

        $total = count($all);

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'elements' => $all
            )
        );

    }

}