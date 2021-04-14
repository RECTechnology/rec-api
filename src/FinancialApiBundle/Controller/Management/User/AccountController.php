<?php

namespace App\FinancialApiBundle\Controller\Management\User;

use App\FinancialApiBundle\Entity\Client as OAuthClient;
use App\FinancialApiBundle\Entity\Tier;
use App\FinancialApiBundle\Exception\AppException;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use App\FinancialApiBundle\Entity\CashInTokens;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\KYC;
use App\FinancialApiBundle\Entity\ServiceFee;
use App\FinancialApiBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Entity\UserGroup;
use App\FinancialApiBundle\Entity\UserWallet;
use App\FinancialApiBundle\Financial\Currency;
use App\FinancialApiBundle\Controller\Google2FA;
use FOS\OAuthServerBundle\Util\Random;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use App\FinancialApiBundle\Controller\SecurityTrait;

class AccountController extends BaseApiController {

    use SecurityTrait;

    function getRepositoryName()
    {
        return User::class;
    }

    function getNewEntity()
    {
        return new User();
    }

    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     */
    public function read(Request $request){
        /** @var User $user */
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $user->setRoles($user->getRoles());
        /** @var Group $group */
        $group = $this->get('security.token_storage')->getToken()->getUser()->getActiveGroup();
        if(!$group->getActive()) throw new AppException(412, "Default account is not active");
        $group_data = $group->getUserView();
        $user->setGroupData($group_data);

        $resp = $this->secureOutput($user);

        return $this->restV2(200, "ok", "Account info got successfully", $resp);
    }

    /**
     * @Rest\View
     * @param Request $request
     * @param null $id
     * @return Response
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function updateAction(Request $request,$id = null){
        /** @var User $user */
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $id = $user->getId();
        $params = array();
        if($request->request->has('password')){
            $params['password'] = $request->request->get('password');
            if($request->request->has('repassword')){
                $params['repassword'] = $request->request->get('repassword');
                if($request->request->has('old_password')){
                    $params['old_password'] = $request->request->get('old_password');
                    $userManager = $this->container->get('access_key.security.user_provider');
                    /** @var User $user */
                    $user = $userManager->loadUserById($id);

                    /** @var UserPasswordEncoder $encoder_service */
                    $encoder = $this->get('security.password_encoder');
                    if(strlen($params['password']) < 6)
                        throw new HttpException(404, 'Password must be longer than 6 characters');
                    if($params['password'] != $params['repassword'])
                        throw new HttpException(404, 'Password and repassword are differents');
                    $encoded_pass = $encoder->encodePassword($user, $request->request->get('old_password'));
                    if($encoded_pass != $user->getPassword())
                        throw new HttpException(404, 'Bad old_password');
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
        $request->request->remove('username');
        $request->request->remove('phone');
        $request->request->remove('dni');

        if($request->request->has('roles')){
            $roles = $request->request->get('roles');
            if(in_array('ROLE_SUPER_ADMIN', $roles)) throw new HttpException(403, 'Bad parameter roles');
        }

        return parent::updateAction($request, $id);
    }


    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     */
    public function changeGroup(Request $request){
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $user->setRoles($user->getRoles());
        if($request->request->has('group_id'))
            $group_id = $request->request->get('group_id');
        else
            throw new HttpException(404,'group_id not found');
        /** @var Group $account */
        $account = false;
        foreach($user->getGroups() as $group){
            if($group->getId() == $group_id && $group->getActive()){
                $account = $group;
            }
        }
        if(!$account){
            throw new HttpException(404,'Group selected is not accessible for you');
        }
        $em = $this->getDoctrine()->getManager();
        $user->setActiveGroup($account);
        $user->setRoles($user->getRoles());
        $em->persist($user);
        $em->flush();

        $resp = $this->secureOutput($user);

        $resp['group_data'] = [
            'id' => $account->getId(),
            'name' => $account->getName(),
            'image' => $account->getCompanyImage()
        ];

        return $this->restV2(200,"ok", "Active group changed successfully", $resp);
    }

    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     */
    public function active2faAction(Request $request){

        /** @var User $user */
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $user->setTwoFactorAuthentication(true);
        if(!$user->getTwoFactorCode()){
            $user->setTwoFactorCode(Google2FA::generate_secret_key());
        }
        $em->persist($user);
        $em->flush();
        $result = $this->secureOutput($user);
        return $this->restV2(200,"ok", "2FA activated successfully", $result);
    }

    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     */
    public function deactive2faAction(Request $request){
        /** @var User $user */
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $user->setTwoFactorAuthentication(false);
        $em->persist($user);
        $em->flush();
        $result = $this->secureOutput($user);
        return $this->restV2(200,"ok", "2FA deactivated successfully", $result);
    }

    /**
     * @Rest\View
     */
    public function update2faAction(Request $request){
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $user->setTwoFactorCode(Google2FA::generate_secret_key());
        $em->persist($user);
        $em->flush();
        $result = $this->secureOutput($user);
        return $this->restV2(200,"ok", "Account info got successfully", $result);
    }


    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     */
    public function publicPhoneAction(Request $request){
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        if($request->request->has('activate'))
            $user->setPublicPhone(true);
        elseif($request->request->has('deactivate'))
            $user->setPublicPhone(false);
        else
            throw new HttpException(400, "Missing parameters");
        $em->persist($user);
        $em->flush();

        $resp = $this->secureOutput($user);

        return $this->restV2(200,"ok", "Account info got successfully", $resp);
    }

    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     */
    public function publicPhoneListAction(Request $request){
        $em = $this->getDoctrine()->getManager();
        if(!$request->request->has('phone_list')){
            throw new HttpException(400, "Missing parameters phone_list");
        }
        $sorted_public_phone_list = [];

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $my_accounts = $em->getRepository(UserGroup::class)->findBy(['user'  =>  $user]);
        foreach($my_accounts as $account){
            /** @var Group $group */
            $group = $account->getGroup();
            if($group->getActive() && $group->getId() != $user->getActiveGroup()->getId()) {
                array_push($sorted_public_phone_list, ['phone' => $user->getPhone(),
                                                            'account' => $group->getName(),
                                                            'address' => $group->getRecAddress(),
                                                            'image' => $group->getCompanyImage(),
                                                            'is_my_account' => 1
                    ]);
            }
        }

        $phone_list = $request->request->get('phone_list');
        $clean_phone_list = [];

        foreach ($phone_list as $phone) {
            $original = $phone;
            $phone = preg_replace('/[^0-9]/', '', $phone);
            $phone = substr($phone, -9);
            $clean_phone_list[$original] = $phone;
        }

        $public_users = $this->getRepository()->findBy(['public_phone' => 1]);

        $selected = [$user->getPhone()];

        foreach ($clean_phone_list as $original => $phone) {
            if(!in_array($phone, $selected)){
                foreach($public_users as $user){
                    $user_clean_phone = substr(preg_replace('/[^0-9]/', '', $user->getPhone()), -9);
                    if(($user_clean_phone == $phone) && $user->getActiveGroup()->getActive()){
                        array_push($sorted_public_phone_list, ['phone' => $user->getPhone(),
                                                                        'account' => $user->getActiveGroup()->getName(),
                                                                        'address' => $user->getActiveGroup()->getRecAddress(),
                                                                        'image' => $user->getActiveGroup()->getCompanyImage(),
                                                                        'is_my_account' => 0
                        ]);


                        $selected[] = $phone;
                    }
                }
            }
        }
        return $this->restV2(201, "ok", "List of public phones registered", $sorted_public_phone_list);
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
        $em->persist($user);
        $em->flush();
        $url = $url.'/user/validation/'.$user->getConfirmationToken();
        $this->_sendEmail('Chip-Chap validation e-mail', $url, $user->getEmail(), 'register');

        $response = array(
            'email'  =>  $email,
        );

        return $this->restV2(201,"ok", "Request successful", $response);
    }

    public function validar_dni($dni){
        $nie_letter = array('X','Y','Z');
        $nie_letter_number = array('0','1','2');
        $letra = substr($dni, -1);
        $numeros = substr($dni, 0, -1);
        $numeros = str_replace($nie_letter, $nie_letter_number, $numeros);
        return ( substr("TRWAGMYFPDXBNJZSQVHLCKE", $numeros%23, 1) == $letra && strlen($letra) == 1 && strlen ($numeros) == 8 );
    }

    public function validate_cif ($cif) {
        $cif_codes = 'JABCDEFGHI';

        $sum = (string) $this->getCifSum ($cif);
        $n = (10 - substr ($sum, -1)) % 10;

        if (preg_match ('/^[ABCDEFGHJNPQRSUVW]{1}/', $cif)) {
            if (in_array ($cif[0], array ('A', 'B', 'E', 'H'))) {
                return ($cif[8] == $n);
            } elseif (in_array ($cif[0], array ('K', 'P', 'Q', 'S'))) {
                return ($cif[8] == $cif_codes[$n]);
            } else {
                if (is_numeric ($cif[8])) {
                    return ($cif[8] == $n);
                } else {
                    return ($cif[8] == $cif_codes[$n]);
                }
            }
        }
        return false;
    }

    /**
     * @Rest\View
     * @param Request $request
     * @param $type
     * @return Response
     * @throws \Exception
     */
    public function registerCommerceAction(Request $request, $type){
        $logger = $this->get('manager.logger');
        $paramNames = array(
            'name',
            'password',
            'repassword',
            'phone',
            'prefix',
            'pin',
            'repin',
            'dni',
            'security_question',
            'security_answer'
        );
        //throw new HttpException(404, 'Must update');
        $valid_types = array('mobile');
        if(!in_array($type, $valid_types)) throw new HttpException(400, 'Type not valid, valid types: mobile');

        $params = array();
        foreach($paramNames as $param){
            if($request->request->has($param) && $request->request->get($param) != ''){
                $params[$param] = $request->request->get($param);
            }else{
                throw new HttpException(400, "Bad request: param '$param' is required");
            }
        }
        if(strlen($params['password'])<6)
            throw new HttpException(400, 'Password must be longer than 6 characters');
        if($params['password'] != $params['repassword'])
            throw new HttpException(400, 'Password and repassword are differents');
        $params['plain_password'] = $params['password'];
        unset($params['password']);
        unset($params['repassword']);

        if($request->request->has('roles')){
            $roles = $request->request->get('roles');
            if(in_array('ROLE_SUPER_ADMIN', $roles)) throw new HttpException(403, 'Bad parameters');
        }

        $em = $this->getDoctrine()->getManager();

        $params['dni'] = strtoupper($params['dni']);
        $params['dni'] = preg_replace("/[^0-9A-Z]/", "", $params['dni']);
        $params['username'] = $params['dni'];

        if(strlen($params['username'])<9){
            for($i = strlen($params['username']); $i<9; $i+=1){
                $params['username'] = "0" . $params['username'];
            }
        }
        if(!$this->validar_dni((string)$params['username']))
            throw new HttpException(400, 'NIF not valid');

        $user = $em->getRepository($this->getRepositoryName())->findOneBy(array(
            'phone'  =>  $params['phone']
        ));
        if($user){
            throw new HttpException(400, "phone already registered");
        }

        $user = $em->getRepository($this->getRepositoryName())->findOneBy(array(
            'dni'  =>  $params['dni']
        ));
        if($user){
            throw new HttpException(400, "dni already registered");
        }

        $user = $em->getRepository($this->getRepositoryName())->findOneBy(array(
            'username'  =>  $params['username']
        ));
        if($user){
            throw new HttpException(400, "Username already registered");
        }

        if($request->request->has('email') && $request->request->get('email')!=''){
            $params['email'] = $request->request->get('email');
            if (!filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
                throw new HttpException(400, 'Email is invalid');
            }
            /*
            $user = $em->getRepository($this->getRepositoryName())->findOneBy(array(
                'email'  =>  $params['email']
            ));
            if($user){
                throw new HttpException(400, "Email already registered");
            }
            */
        }
        else{
            $params['email'] = '';
        }

        if(strlen($params['security_question'])<1 || strlen($params['security_question'])>200)
            throw new HttpException(400, 'Security question is too large or too simple');
        if(strlen($params['security_answer'])<1 || strlen($params['security_answer'])>50)
            throw new HttpException(400, 'Security answer is too large or too simple');
        $params['security_answer'] = $this->cleanString($params['security_answer']);

        $methodsList = array('rec-out', 'rec-in');

        $allowed_types = array('PRIVATE', 'COMPANY');
        if($request->request->has('type') && $request->request->get('type')!='') {
            $params['type'] = $request->request->get('type');
            if(in_array($params['type'], $allowed_types)) {
                $type = $params['type'];
            }
            else{
                throw new HttpException(400, "Invalid type");
            }
        }
        else{
            $type = $allowed_types[0];
        }

        $list_subtypes = array(
            'PRIVATE' => array('NORMAL', 'BMINCOME'),
            'COMPANY' => array('RETAILER', 'WHOLESALE')
        );
        $allowed_subtypes = $list_subtypes[$type];
        if($request->request->has('subtype') && $request->request->get('subtype')!='') {
            $params['subtype'] = $request->request->get('subtype');
            if(in_array($params['subtype'], $allowed_subtypes)) {
                $subtype = $params['subtype'];
            }
            else{
                throw new HttpException(400, "Invalid subtype");
            }
        }
        else{
            $subtype = $allowed_subtypes[0];
        }

        if($request->request->has('company_name') && $request->request->get('company_name')!='') {
            $company_name = $request->request->get('company_name');
        }
        else{
            if($type == 'COMPANY'){ throw new HttpException(400, "Company name required"); }
            $company_name = $params['name'];
        }

        if($request->request->has('company_cif') && $request->request->get('company_cif')!='') {
            $company_cif = $request->request->get('company_cif');
        }
        else{
            if($type == 'COMPANY'){ throw new HttpException(400, "Company cif required"); }
            $company_cif = $params['dni'];
        }

        /*
        $account = $em->getRepository('FinancialApiBundle:Group')->findOneBy(array(
            'cif'  =>  $company_cif
        ));
        if($account){
            throw new HttpException(400, "CIF already registered");
        }
        */

        //create company
        $company = new Group();
        if($request->request->has('company_email') && $request->request->get('company_email')!='') {
            $company->setEmail($request->request->get('company_email'));
        }
        else{
            $company->setEmail($params['email']);
        }

        $phone = preg_replace("/[^0-9]/", "", $params['phone']);
        $prefix = preg_replace("/[^0-9]/", "", $params['prefix']);
        if(!$this->checkPhone($phone, $prefix)){
            throw new HttpException(400, "Incorrect phone or prefix number");
        }

        if($type == 'COMPANY'){
            if($request->request->has('company_phone') && $request->request->get('company_phone')!='') {
                $phone_com = preg_replace("/[^0-9]/", "", $request->request->get('company_phone'));
                $company->setPhone($phone_com);
            }
            if($request->request->has('company_prefix') && $request->request->get('company_prefix')!='') {
                $prefix_com = preg_replace("/[^0-9]/", "", $request->request->get('company_prefix'));
                $company->setPrefix($prefix_com);
            }
            if(!$this->checkPhone($phone_com, $prefix_com)){
                throw new HttpException(400, "Incorrect phone or prefix company number");
            }
        }
        else{
            $company->setPhone($phone);
            $company->setPrefix($prefix);
        }

        $latitude=0;
        $longitude=0;
        if($request->request->has('latitude') && $request->request->get('latitude')!='') {
            $latitude = $request->request->get('latitude');
        }
        if($request->request->has('longitude') && $request->request->get('longitude')!='') {
            $longitude = $request->request->get('longitude');
        }

        $company->setName($company_name);
        $company->setCif(strtoupper($company_cif));
        $company->setType($type);
        $company->setSubtype($subtype);
        $company->setActive(true);
        $company->setRoles(['ROLE_COMPANY']);
        $company->setRecAddress('temp');
        $company->setMethodsList($methodsList);
        $company->setLatitude($latitude);
        $company->setLongitude($longitude);
        $level = $em->getRepository(Tier::class)->findOneBy(['code' => Tier::KYC_LEVELS[1]]);
        $company->setLevel($level);
        $em->persist($company);
        $em->flush();

        //create wallets for this company
        $currencies = Currency::$ALL_COMPLETED;
        foreach($currencies as $currency){
            $userWallet = new UserWallet();
            $userWallet->setBalance(0);
            $userWallet->setAvailable(0);
            $userWallet->setCurrency(strtoupper($currency));
            $userWallet->setGroup($company);
            $em->persist($userWallet);
        }

        $pin = preg_replace("/[^0-9]/", "", $params['pin']);
        if(strlen($pin)!=4){
            throw new HttpException(400, "Pin must be a number with 4 digits");
        }
        if($params['pin'] != $params['repin']) throw new HttpException(400, 'Pin and repin are differents');

        $user = new User();
        $user->setPlainPassword($params['plain_password']);
        $user->setEmail($params['email']);
        $user->setRoles(array('ROLE_USER'));
        $user->setName($params['name']);
        $user->setPhone($phone);
        $user->setPrefix($prefix);
        $user->setUsername($params['username']);
        $user->setDNI($params['dni']);
        $user->setActiveGroup($company);
        $user->setEnabled(false);
        $user->setPin($params['pin']);
        $user->setSecurityQuestion($params['security_question']);
        $user->setSecurityAnswer($params['security_answer']);
        $em->persist($user);

        $company->setKycManager($user);
        $em->persist($company);

        //Add user to group with admin role
        $userGroup = new UserGroup();
        $userGroup->setUser($user);
        $userGroup->setGroup($company);
        $userGroup->setRoles(array('ROLE_ADMIN'));

        $kyc = new KYC();
        $kyc->setUser($user);
        $kyc->setName($user->getName());
        $kyc->setEmail($user->getEmail());

        $em->persist($userGroup);
        $em->persist($kyc);
        $em->flush();

        $code = strval(random_int(100000, 999999));
        $kyc->setPhoneValidated(false);
        $kyc->setValidationPhoneCode(json_encode(array("code" => $code, "tries" => 0)));
        $phone_info = array(
            "prefix" => $prefix,
            "number" => $phone
        );
        $kyc->setPhone(json_encode($phone_info));
        $sms_text = $code." es tu codigo de seguridad para validar tu nueva cuenta y completar tu registro en la app. del REC.";
        $this->sendSMS($prefix, $phone, $sms_text);

        if($params['email'] != '') {
            /*
             * POR AHORA NO ENVIAMOS MAIL
            $tokenGenerator = $this->container->get('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
            $em->persist($user);
            $em->flush();
            $url = "NO";
            $url_validation = $url . '/user/validation/' . $user->getConfirmationToken();
            $this->_sendEmail('Validation e-mail', $url_validation, $user->getEmail(), 'register');
            */
        }
        $em->persist($kyc);
        $em->flush();

        foreach($methodsList as $method){
            $method_ex = explode('-', $method);
            $meth = $method_ex[0];
            $meth_type = $method_ex[1];

            //create new ServiceFee
            $newFee = new ServiceFee();
            $newFee->setGroup($company);
            $newFee->setFixed(0);
            $newFee->setVariable(0);
            $newFee->setServiceName($method);
            $newFee->setCurrency(strtoupper($meth));
            $em->persist($newFee);

        }

        //create new fixed address for rec and return
        $recAddress = new CashInTokens();
        $recAddress->setCurrency(Currency::$REC);
        $recAddress->setCompany($company);
        $recAddress->setLabel('REC account');
        $recAddress->setMethod('rec-in');
        $recAddress->setExpiresIn(-1);
        $recAddress->setStatus(CashInTokens::$STATUS_ACTIVE);

        //TODO: create mock for REC method
        $methodDriver = $this->get('net.app.in.rec.v1');
        $paymentInfo = $methodDriver->getPayInInfo($company->getId(), 0);
        # $paymentInfo = ['address' => (new Random())->generateToken()];
        $token = $paymentInfo['address'];
        $recAddress->setToken($token);
        $em->persist($recAddress);

        $company->setRecAddress($token);
        $em->persist($company);

        $response['rec_address'] = $token;
        $response['user'] = $user;
        $response['company'] = $company;
        $em->flush();

        return $this->restV2(201,"ok", "Request successful", $this->secureOutput($response));
    }

    private function cleanString($string){
        $string = strtoupper($string);
        $not_letters = array(".", " ", ",", "-", "?", "!", ":", ";", "_", "(". ")");
        $string = str_replace($not_letters, "", $string);
        $unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
        $string = strtr( $string, $unwanted_array );
        return $string;
    }

    /**
     * @Rest\View
     * @throws \Exception
     */
    public function resetCredentials(Request $request){

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $access_key = sha1(random_bytes(32));
        $access_secret = base64_encode(random_bytes(32));

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
    public function passwordRecoveryRequest(Request $request){
        $paramNames = array(
            'dni',
            'phone'
        );

        $params = array();
        foreach($paramNames as $param){
            if($request->request->has($param) && $request->request->get($param)!=''){
                $params[$param] = $request->request->get($param);
            }else{
                throw new HttpException(404, 'Param ' . $param . ' not found');
            }
        }

        if($request->request->has('secret') && $request->request->get('secret')!='') {
            $params['secret'] = $request->request->get('secret');
        }
        else{
            $params['secret'] = 'm3ft56';
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository($this->getRepositoryName())->findOneBy(array(
            'phone'  =>  $params['phone'],
            'dni'  =>  strtoupper($params['dni'])
        ));

        $logger = $this->get('manager.logger');
        $logger->info('PASS RECOVERY REQ: '. $params['phone'] . " " . $params['dni']);
        if(!$user){
            $logger->info('PASS RECOVERY REQ: User not found');
            throw new HttpException(404, 'User not found');
        }
        $code = strval(random_int(100000, 999999));

        //generate a token to add to the return url
        $user->setRecoverPasswordToken($code);
        $user->setPasswordRequestedAt(new \DateTime());
        $em->persist($user);
        $em->flush();
        $sms_text = $code. " es tu codigo para cambiar tu contraseña del REC. No lo compartas con nadie!";
        $this->sendSMS($user->getPrefix(), $user->getPhone(), $sms_text);;
        return $this->restV2(200,"ok", "Request successful");
    }

    /**
     * @Rest\View
     */
    public function passwordRecovery(Request $request){
        $paramNames = array(
            'code',
            'password',
            'repassword'
        );

        $params = array();
        foreach($paramNames as $param){
            if($request->request->has($param) && $request->request->get($param)!=''){
                $params[$param] = $request->request->get($param);
            }else{
                throw new HttpException(404, 'Param ' . $param . ' not found');
            }
        }

        if($params['password']==''){
            throw new HttpException('Paramater password not found');
        }

        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository($this->getRepositoryName())->findOneBy(array(
            'recover_password_token' => $params['code']
        ));

        $logger = $this->get('manager.logger');
        $logger->info('PASS RECOVERY: '. $params['code']);
        if(!$user) {
            $logger->info('PASS RECOVERY: Code not found');
            throw new HttpException(404, 'Code not found');
        }

        if($user->isPasswordRequestNonExpired(1200)){
            if(strlen($params['password'])<6) throw new HttpException(404, 'Password must be longer than 6 characters');
            if($params['password'] != $params['repassword']) throw new HttpException('Password and repassword are differents');

            $userManager = $this->container->get('access_key.security.user_provider');
            $user = $userManager->loadUserById($user->getId());

            $user->setPlainPassword($request->get('password'));
            $userManager->updatePassword($user);

            $em->persist($user);
            $em->flush();
            $logger->info('PASS RECOVERY: Pass updated (' . $user->getId() .')');
        }else{
            throw new HttpException(404, 'Expired code');
        }

        $logger->info('PASS RECOVERY: All done');
        return $this->restV2(204,"ok", "password recovered");
    }

    /**
     * @Rest\View
     */
    public function kycSave(Request $request){
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $kyc = $em->getRepository('FinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user
        ));
        if(!$kyc){
            $kyc = new KYC();
            $kyc->setEmail($user->getEmail());
            $kyc->setUser($user);
        }

        if($request->request->has('name') && $request->request->get('name')!=''){
            $user->setName($request->request->get('name'));
            $kyc->setName($request->request->get('name'));
            $em->persist($user);
        }

        if($request->request->has('last_name') && $request->request->get('last_name')!=''){
            $kyc->setLastName($request->request->get('last_name'));
            $em->persist($user);
        }

        if($request->request->has('neighborhood') && $request->request->get('neighborhood')!=''){
            $kyc->setNeighborhood($request->request->get('neighborhood'));
            $em->persist($kyc);
        }

        if($request->request->has('street_type') && $request->request->get('street_type')!=''){
            $kyc->setStreetType($request->request->get('street_type'));
            $em->persist($kyc);
        }

        if($request->request->has('street_number') && $request->request->get('street_number')!=''){
            $kyc->setStreetNumber($request->request->get('street_number'));
            $em->persist($kyc);
        }

        if($request->request->has('street_name') && $request->request->get('street_name')!=''){
            $kyc->setStreetName($request->request->get('street_name'));
            $em->persist($kyc);
        }

        if($request->request->has('email') && $request->request->get('email')!=''){
            $user->setEmail($request->request->get('email'));
            $em->persist($user);
            $kyc->setEmail($request->request->get('email'));
            $kyc->setEmailValidated(false);
            $em->persist($kyc);
        }

        if($request->request->has('date_birth') && $request->request->get('date_birth')!=''){
            $kyc->setDateBirth($request->request->get('date_birth'));
            $em->persist($kyc);
        }

        if($request->request->has('country') && $request->request->get('country')!=''){
            $kyc->setCountry($request->request->get('country'));
            $em->persist($kyc);
        }

        if($request->request->has('address') && $request->request->get('address')!=''){
            $kyc->setAddress($request->request->get('address'));
            $em->persist($kyc);
        }

        if($request->request->has('document_front') && $request->request->get('document_front')!=''){
            $kyc->setDocumentFront($request->request->get('document_front'));
            $kyc->setDocumentFrontStatus('pending');
            $em->persist($kyc);
            $document_front = $request->request->get('document_front');
            $document_rear = '';
            if($request->request->has('document_rear')) {
                $document_rear = $request->request->get('document_rear');
            }

            $params = [
                'mail' => ['lang' => $user->getLocale()],
                'user_id' => $user->getId(),
                'document_front' => $document_front,
                'document_rear' => $document_rear
            ];
            $to = $this->container->getParameter('kyc_email');
            $this->_sendEmail('Documentación cuenta', null, $to, 'kyc', $params);

        }

        if($request->request->has('document_rear') && $request->request->get('document_rear')!=''){
            $kyc->setDocumentRear($request->request->get('document_rear'));
            $kyc->setDocumentRearStatus('pending');
            $em->persist($kyc);
        }

        $em->flush();

        return $this->restV2(201, "ok", "KYC Info saved");
    }

    /**
     * @Rest\View
     */
    public function validateEmail(Request $request){

        $em = $this->getDoctrine()->getManager();

        if(!$request->request->has('confirmation_token')) throw new HttpException(404, 'Param confirmation_token not found');

        $user = $em->getRepository('FinancialApiBundle:User')->findOneBy(array(
            'confirmationToken' => $request->request->get('confirmation_token')
        ));

        if(!$user) throw new HttpException(400, 'User not found');
        $user->setEnabled(true);
        $em->persist($user);
        $em->flush();

        $kyc = $em->getRepository('FinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user
        ));

        if(!$kyc){
            $kyc = new KYC();
            $kyc->setEmail($user->getEmail());
            $kyc->setUser($user);
            $em->persist($kyc);

        }

        if($kyc->getEmailValidated() == true){
            throw new HttpException(403, 'This email is validated yet');
        }

        $kyc->setEmailValidated(true);
        $em->flush();

        $response = array(
            'username'  =>  $user->getUsername(),
            'email'     =>  $user->getEmail()
        );

        return $this->restV2(201,"ok", "Validation email succesfully", $response);

    }

    private function _sendEmail($subject, $body, $to, $action, $params=null){
        $from = $this->container->getParameter('no_reply_email');
        $mailer = 'mailer';
        if($action == 'register'){
            $template = 'FinancialApiBundle:Email:registerconfirm.html.twig';
        }elseif($action == 'recover'){
            $template = 'FinancialApiBundle:Email:recoverpassword.html.twig';
        }elseif($action == 'kyc'){
            $template = 'FinancialApiBundle:Email:document_uploaded.html.twig';
        }else{
            $template = 'FinancialApiBundle:Email:registerconfirm.html.twig';
        }

        if(is_null($params)){
            $params = array();
        }
        $params['message'] = $body;
        $message = \Swift_Message::newInstance();
        $message->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody(
                $this->container->get('templating')
                    ->render($template, $params)
            )
            ->setContentType('text/html');

        $this->container->get($mailer)->send($message);
    }

    /**
     * @Rest\View
     * @return Response
     */
    public function indexCompanies(){
        /** @var User $user */
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $all = [];
        /** @var UserGroup $permission */
        foreach ($user->getUserGroups() as $permission){

            /** @var Group $account */
            $account = $permission->getGroup();
            if($account->getActive()) {
                $all [] = [
                    'company' => $permission->getGroup(),
                    'roles' => $permission->getRoles()
                ];
            }
        }

        $resp = $this->secureOutput($all);

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => count($all),
                'elements' => $resp
            )
        );
    }

    /**
     * @Rest\View
     */
    public function showQuestion(Request $request){
        $user = $this->get('security.token_storage')->getToken()->getUser();
        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'question' => $user->getSecurityQuestion(),
            )
        );
    }

    /**
     * @Rest\View
     */
    public function updatePin(Request $request){
        $paramNames = array(
            'pin',
            'repin',
            'security_answer'
        );

        $params = array();
        foreach($paramNames as $param){
            if($request->request->has($param) && $request->request->get($param)!=''){
                $params[$param] = $request->request->get($param);
            }else{
                throw new HttpException(404, 'Param ' . $param . ' not found');
            }
        }

        $pin = preg_replace("/[^0-9]/", "", $params['pin']);
        if(strlen($pin)!=4){
            throw new HttpException(400, "Pin must be a number with 4 digits");
        }
        if($params['pin'] != $params['repin']) throw new HttpException(404, 'Pin and repin are different');

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $params['security_answer'] = $this->cleanString($params['security_answer']);
        if(strtoupper($params['security_answer']) != $user->getSecurityAnswer()){
            throw new HttpException(404, 'Security answer is incorrect');
        }

        $em = $this->getDoctrine()->getManager();
        $user->setPin($pin);
        $em->persist($user);
        $em->flush();
        $resp = $this->secureOutput($user);
        return $this->restV2(200,"ok", "Account PIN got successfully", $resp);
    }

    private function checkPhone($phone, $prefix){
        if(strlen($prefix)<1){
            return false;
        }
        $first = substr($phone, 0, 1);

        //SP xxxxxxxxx
        if($prefix == '34' && ($first == '6' || $first == '7')){
            return strlen($phone)==9;
        }
        //PL xxxxxxxxx
        elseif($prefix == '48'){
            return strlen($phone)==9;
        }
        //GR xxxxxxxxx
        elseif($prefix == '30'){
            return strlen($phone)==10;
        }
        //GB 07xxx xxxxxx
        elseif($prefix == '44'){
            return strlen($phone)==11;
        }
        elseif(strlen($phone)>7){
            return true;
        }
        return false;
    }

    private function sendSMS($prefix, $number, $text){
        $user = $this->container->getParameter('labsmobile_user');
        $pass = $this->container->getParameter('labsmobile_pass');
        $text = str_replace(" ", "+", $text);

        $url = 'http://api.labsmobile.com/get/send.php?';
        $url .= 'username=' . $user . '&';
        $url .= 'password=' . $pass . '&';
        $url .= 'msisdn=' . $prefix . $number . '&';
        $url .= 'message=' . $text . '&';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $result = curl_exec($ch);
        curl_close($ch);
    }

    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function registerAccountAction(Request $request){
        $type = 'mobile';
        $logger = $this->get('manager.logger');
        $paramNames = array(
            'password',
            'phone',
            'prefix',
            'dni'
        );
        $params = array();
        foreach($paramNames as $param){
            if($request->request->has($param) && $request->request->get($param) != ''){
                $params[$param] = $request->request->get($param);
            }else{
                throw new HttpException(400, "Bad request: param '$param' is required");
            }
        }
        if(strlen($params['password'])<6)
            throw new HttpException(400, 'Password must be longer than 6 characters');
        $params['plain_password'] = $params['password'];
        unset($params['password']);

        if($request->request->has('roles')){
            $roles = $request->request->get('roles');
            if(in_array('ROLE_SUPER_ADMIN', $roles)) throw new HttpException(403, 'Bad parameters');
        }

        $em = $this->getDoctrine()->getManager();

        $params['dni'] = strtoupper($params['dni']);
        $params['dni'] = preg_replace("/[^0-9A-Z]/", "", $params['dni']);
        $params['username'] = $params['dni'];

        if(strlen($params['username'])<9){
            for($i = strlen($params['username']); $i<9; $i+=1){
                $params['username'] = "0" . $params['username'];
            }
        }
        if(!$this->validar_dni((string)$params['username']))
            throw new HttpException(400, 'NIF not valid');

        $user = $em->getRepository($this->getRepositoryName())->findOneBy(array(
            'phone'  =>  $params['phone']
        ));
        if($user){
            throw new HttpException(400, "phone already registered");
        }

        $user = $em->getRepository($this->getRepositoryName())->findOneBy(array(
            'dni'  =>  $params['dni']
        ));
        if($user){
            throw new HttpException(400, "dni already registered");
        }

        $user = $em->getRepository($this->getRepositoryName())->findOneBy(array(
            'username'  =>  $params['username']
        ));
        if($user){
            throw new HttpException(400, "Username already registered");
        }

        $methodsList = array('rec-out', 'rec-in');



        $allowed_types = array('PRIVATE', 'COMPANY');
        if($request->request->has('company_cif') && $request->request->get('company_cif')!='') {
            if(!$this->validate_cif((string)$request->request->get('company_cif')))
                throw new HttpException(400, 'CIF not valid');
            $type = $allowed_types[1];
            $company_cif = $request->request->get('company_cif');
        }else{
            $type = $allowed_types[0];
            $company_cif = $params['dni'];
        }

        $list_subtypes = array(
            'PRIVATE' => array('NORMAL', 'BMINCOME'),
            'COMPANY' => array('RETAILER', 'WHOLESALE')
        );
        $allowed_subtypes = $list_subtypes[$type];
        $subtype = $allowed_subtypes[0];

        if($request->request->has('company_name') && $request->request->get('company_name')!='') {
            $company_name = $request->request->get('company_name');
        }
        else{
            if($type == 'COMPANY'){ throw new HttpException(400, "Company name required"); }
            $company_name = '';
        }

        //create company
        $company = new Group();
        $phone = preg_replace("/[^0-9]/", "", $params['phone']);
        $prefix = preg_replace("/[^0-9]/", "", $params['prefix']);
        if(!$this->checkPhone($phone, $prefix)){
            throw new HttpException(400, "Incorrect phone or prefix number");
        }
        $company->setName($company_name);
        $company->setCif(strtoupper($company_cif));
        $company->setType($type);
        $company->setSubtype($subtype);
        $company->setActive(true);
        $company->setRoles(['ROLE_COMPANY']);
        $company->setRecAddress('temp');
        $company->setMethodsList($methodsList);
        $level = $em->getRepository(Tier::class)->findOneBy(['code' => Tier::KYC_LEVELS[1]]);
        $company->setLevel($level);
        $em->persist($company);
        $em->flush();

        //create wallets for this company
        $currencies = Currency::$ALL_COMPLETED;
        foreach($currencies as $currency){
            $userWallet = new UserWallet();
            $userWallet->setBalance(0);
            $userWallet->setAvailable(0);
            $userWallet->setCurrency(strtoupper($currency));
            $userWallet->setGroup($company);
            $em->persist($userWallet);
        }

        $user = new User();
        $user->setPlainPassword($params['plain_password']);
        $user->setRoles(array('ROLE_USER'));
        $user->setPhone($phone);
        $user->setEmail("");
        $user->setPrefix($prefix);
        $user->setUsername($params['username']);
        $user->setDNI($params['dni']);
        $user->setActiveGroup($company);
        $user->setEnabled(false);
        $em->persist($user);

        $company->setKycManager($user);
        $em->persist($company);

        //Add user to group with admin role
        $userGroup = new UserGroup();
        $userGroup->setUser($user);
        $userGroup->setGroup($company);
        $userGroup->setRoles(array('ROLE_ADMIN'));

        $kyc = new KYC();
        $kyc->setUser($user);
        $kyc->setName($user->getName());

        $em->persist($userGroup);
        $em->persist($kyc);
        $em->flush();

        $code = strval(random_int(100000, 999999));
        $kyc->setPhoneValidated(false);
        $kyc->setValidationPhoneCode(json_encode(array("code" => $code, "tries" => 0)));
        $phone_info = array(
            "prefix" => $prefix,
            "number" => $phone
        );
        $kyc->setPhone(json_encode($phone_info));
        $sms_text = $code." es tu codigo de seguridad para validar tu nueva cuenta y completar tu registro en la app. del REC.";
        $this->sendSMS($prefix, $phone, $sms_text);

        $em->persist($kyc);
        $em->flush();

        foreach($methodsList as $method){
            $method_ex = explode('-', $method);
            $meth = $method_ex[0];
            $meth_type = $method_ex[1];

            //create new ServiceFee
            $newFee = new ServiceFee();
            $newFee->setGroup($company);
            $newFee->setFixed(0);
            $newFee->setVariable(0);
            $newFee->setServiceName($method);
            $newFee->setCurrency(strtoupper($meth));
            $em->persist($newFee);

        }

        //create new fixed address for rec and return
        $recAddress = new CashInTokens();
        $recAddress->setCurrency(Currency::$REC);
        $recAddress->setCompany($company);
        $recAddress->setLabel('REC account');
        $recAddress->setMethod('rec-in');
        $recAddress->setExpiresIn(-1);
        $recAddress->setStatus(CashInTokens::$STATUS_ACTIVE);

        //TODO: create mock for REC method
        $methodDriver = $this->get('net.app.in.rec.v1');
        $paymentInfo = $methodDriver->getPayInInfo($company->getId(), 0);
        $token = $paymentInfo['address'];
        $recAddress->setToken($token);
        $em->persist($recAddress);

        $company->setRecAddress($token);
        $em->persist($company);

        $response['rec_address'] = $token;
        $response['user'] = $user;
        $response['company'] = $company;
        $em->flush();

        return $this->restV2(201,"ok", "Request successful", $this->secureOutput($response));
    }

}