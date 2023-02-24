<?php

namespace App\Controller\Management\User;

use App\Controller\BaseApiController;
use App\Controller\Google2FA;
use App\Controller\Management\Admin\UsersController;
use App\Controller\SecurityTrait;
use App\DependencyInjection\Commons\MailerAwareTrait;
use App\Document\Transaction;
use App\Entity\AccountCampaign;
use App\Entity\AccountChallenge;
use App\Entity\Campaign;
use App\Entity\CashInTokens;
use App\Entity\Document;
use App\Entity\DocumentKind;
use App\Entity\Group;
use App\Entity\KYC;
use App\Entity\PaymentOrder;
use App\Entity\ServiceFee;
use App\Entity\SmsTemplates;
use App\Entity\Tier;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UsersSmsLogs;
use App\Entity\UserWallet;
use App\Exception\AppException;
use App\Financial\Currency;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class AccountController extends BaseApiController {

    use MailerAwareTrait;
    const PLATFORM_REZERO_B2B_WEB = 'rezero-b2b-web';
    const DNI_NOT_VALID = 'Wrong DNI';

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
        $active_group = $this->get('security.token_storage')->getToken()->getUser()->getActiveGroup();
        if(!$active_group->getActive()) throw new AppException(412, "Default account is not active");
        $group_data = $active_group->getUserView();
        $user->setGroupData($group_data);
        $activeAccounts = [];
        foreach($user->getGroups() as $group){
            if($group->getActive()){
                $activeAccounts[] = $group;
            }
        }
        $resp = $this->secureOutput($user);
        $resp["activeAccounts"] =  $this->secureOutput($activeAccounts);
        if($user->getPin()){
            $resp["has_pin"] = true;
        }else{
            $resp["has_pin"] = false;
        }

        if($active_group->getKycManager()->getId() === $user->getId()){
            $resp['is_kyc_manager'] = true;
        }else{
            $resp['is_kyc_manager'] = false;
        }



        return $this->rest(200, "ok", "Account info got successfully", $resp);
    }

    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     */
    public function resume(Request $request){
        /** @var User $user */
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $user->setRoles($user->getRoles());
        /** @var Group $group */
        $group = $this->get('security.token_storage')->getToken()->getUser()->getActiveGroup();
        if(!$group->getActive()) throw new AppException(412, "Default account is not active");

        /** @var DocumentManager $dm */
        $dm = $this->get('doctrine_mongodb')->getManager();
        $em = $this->getDoctrine()->getManager();

        $transactions = $dm->getRepository(Transaction::class)->findBy(array(
            'group' => $group->getId(),
            'type' => Transaction::$TYPE_OUT,
            'internal' => false,
            'method' => strtolower($this->getCryptoCurrency())
        ));
        //get total compras
        $total_purchases = 0;
        //sum total amount
        $total_spent = 0;

        foreach ($transactions as $transaction){
            //check if receiver is a shop
            $receiver_address = $transaction->getPayOutInfo()['address'];
            /** @var Group $receiver_account */
            $receiver_account = $em->getRepository(Group::class)->findOneBy(array('rec_address' => $receiver_address));
            if(!$receiver_account){
                /** @var PaymentOrder $payment_order */
                $payment_order = $em->getRepository(PaymentOrder::class)->findOneBy(array('payment_address' => $receiver_address));
                $receiver_account = $payment_order->getPos()->getAccount();
            }
            if($receiver_account && $receiver_account->getType() === Group::ACCOUNT_TYPE_ORGANIZATION){
                $total_purchases++;
            }
            $total_spent += $transaction->getAmount();
        }
        //retos completados
        $account_challenges = $em->getRepository(AccountChallenge::class)->findBy(array(
            'account' => $group
        ));

        $resp = array(
            'total_purchases' => $total_purchases,
            'total_spent' => $total_spent,
            'completed_challenges' => count($account_challenges)
        );

        return $this->rest(200, "ok", "Account resume got successfully", $resp);
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

        return $this->rest(200,"ok", "Active group changed successfully", $resp);
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
        return $this->rest(200,"ok", "2FA activated successfully", $result);
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
        return $this->rest(200,"ok", "2FA deactivated successfully", $result);
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
        return $this->rest(200,"ok", "Account info got successfully", $result);
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

        return $this->rest(200,"ok", "Account info got successfully", $resp);
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
        return $this->rest(201, "ok", "List of public phones registered", $sorted_public_phone_list);
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
            return $this->rest(201,"ok", "Request successful.", $response);
        }
        $user->setConfirmationToken($tokenGenerator->generateToken());
        $em->persist($user);
        $em->flush();
        $url = $url.'/user/validation/'.$user->getConfirmationToken();
        $this->_sendEmail('Chip-Chap validation e-mail', $url, $user->getEmail(), 'register');

        $response = array(
            'email'  =>  $email,
        );

        return $this->rest(201,"ok", "Request successful", $response);
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
        $params['dni'] = preg_replace("/[^0-9A-Z ]/", "", $params['dni']);
        $params['username'] = $params['dni'];

        if(strlen($params['username'])<9){
            for($i = strlen($params['username']); $i<9; $i+=1){
                $params['username'] = "0" . $params['username'];
            }
        }
        $user_checker = $this->container->get('net.app.commons.user_checker');
        $dni_val = $user_checker->validateUserIdentification((string)$params['username']);
        if(!$dni_val['result'])
            throw new HttpException(400, $dni_val['errors'][0]);

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

        $methodsList = array(strtolower($this->getCryptoCurrency()).'-out', strtolower($this->getCryptoCurrency()).'-in');

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
            $user_checker = $this->container->get('net.app.commons.user_checker');
            $cif_val = $user_checker->validateCompanyIdentification((string)$request->request->get('company_cif'));
            if(!$cif_val['result']){
                throw new HttpException(400, $cif_val['errors'][0]);
            }
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
        if(!UsersController::checkPhone($phone, $prefix)){
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
            if(!UsersController::checkPhone($phone, $prefix)){
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
        $currencies = [Currency::$EUR, $this->getCryptoCurrency()];
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

        $template = $em->getRepository(SmsTemplates::class)->findOneBy(['type' => 'validate_phone']);
        if (!$template) {
            throw new HttpException(404, 'Template not found');
        }
        $sms_text = str_replace("%SMS_CODE%", $code, $template->getBody());
        UsersController::sendSMSv4($prefix, $phone, $sms_text, $this->container);


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
        $recAddress->setCurrency($this->getCryptoCurrency());
        $recAddress->setCompany($company);
        $recAddress->setLabel('REC account');
        $recAddress->setMethod(strtolower($this->getCryptoCurrency()).'-in');
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

        return $this->rest(201,"ok", "Request successful", $this->secureOutput($response));
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

        return $this->rest(204,"ok", "Updated successfully");

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

        $template = $em->getRepository(SmsTemplates::class)->findOneBy(['type' => 'forget_password']);
        if (!$template) {
            throw new HttpException(404, 'Template not found');
        }
        $sms_text = str_replace("%SMS_CODE%", $code, $template->getBody());
        UsersController::sendSMSv4($user->getPrefix(), $user->getPhone(), $sms_text, $this->container);
        return $this->rest(200,"ok", "Request successful");
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
        return $this->rest(204,"ok", "password recovered");
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
            $dt = new \DateTime($request->request->get('date_birth'));
            $kyc->setDateBirth($dt);
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

        if($request->request->has('zip') && $request->request->get('zip') !== ''){
            $kyc->setZip($request->request->get('zip'));
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

        return $this->rest(201, "ok", "KYC Info saved");
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

        return $this->rest(201,"ok", "Validation email succesfully", $response);

    }

    private function _sendEmail($subject, $body, $to, $action, $params=null){
        $from = $this->container->getParameter('no_reply_email');
        if($action === 'register'){
            $template = 'Email/registerconfirm.html.twig';
        }elseif($action === 'recover'){
            $template = 'Email/recoverpassword.html.twig';
        }elseif($action ==='kyc'){
            $template = 'Email/document_uploaded.html.twig';
        }elseif($action === 'document'){
            $template = 'Email/document_uploaded_v4.html.twig';
        }else{
            $template = 'Email/registerconfirm.html.twig';
        }

        if(is_null($params)){
            $params = array();
        }
        $params['message'] = $body;
        $message = new Email();
        $message->subject($subject)
            ->from($from)
            ->to($to)
            ->html(
                $this->container->get('templating')
                    ->render($template, $params)
            );

        $this->mailer->send($message);
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

        return $this->rest(
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
        return $this->rest(
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
        return $this->rest(200,"ok", "Account PIN got successfully", $resp);
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
        $params['dni'] = preg_replace("/[^0-9A-Z ]/", "", $params['dni']);
        $params['username'] = $params['dni'];

        if(strlen($params['username'])<9){
            for($i = strlen($params['username']); $i<9; $i+=1){
                $params['username'] = "0" . $params['username'];
            }
        }
        $user_checker = $this->container->get('net.app.commons.user_checker');
        $dni_val = $user_checker->validateUserIdentification((string)$params['username']);
        if(!$dni_val['result'])
            throw new HttpException(400, $dni_val['errors'][0]);

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

        $methodsList = array(strtolower($this->getCryptoCurrency()).'-out', strtolower($this->getCryptoCurrency()).'-in');

        $allowed_types = array('PRIVATE', 'COMPANY');

        if($request->request->has('company_cif') && $request->request->get('company_cif')!='') {
            $user_checker = $this->container->get('net.app.commons.user_checker');
            $cif_val = $user_checker->validateCompanyIdentification((string)$request->request->get('company_cif'));
            if(!$cif_val['result'])
            {
                throw new HttpException(400, $cif_val['errors'][0]);
            }
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
        if(!UsersController::checkPhone($phone, $prefix)){
            throw new HttpException(400, "Incorrect phone or prefix number");
        }

        if($request->request->has('platform')){
            $platform = $request->request->get('platform');
            if($platform !== AccountController::PLATFORM_REZERO_B2B_WEB) throw new HttpException(403, 'Platform not allowed');

            if(!$request->request->has('rezero_b2b_username') || $request->request->get('rezero_b2b_username') === '')
                throw new HttpException(403, 'Param rezero_b2b_username is required');
            $b2b_username = $request->request->get('rezero_b2b_username');

            if(strpos($b2b_username, " "))
                throw new HttpException(403, 'Param rezero_b2b_username contains whitespaces');

            if(strlen($b2b_username) > 32)
                throw new HttpException(403, 'Param rezero_b2b_username is too long');

            $account = $em->getRepository(Group::class)->findOneBy(array(
                'rezero_b2b_username'  =>  $b2b_username
            ));
            if($account){
                throw new HttpException(400, "Rezero b2b username already registered");
            }

            $company->setRezeroB2bUsername($b2b_username);
            $company->setRezeroB2bAccess(Group::ACCESS_STATE_PENDING);
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

        $currencies = array($this->getCryptoCurrency(), "EUR");
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

        return $this->rest(204,"ok", "No content");
    }

    /**
     * @Rest\View
     */
    public function forgetPasswordRequest(Request $request){
        $paramNames = array(
            'dni',
            'phone',
            'prefix'
        );

        $params = array();
        foreach($paramNames as $param){
            if($request->request->has($param) && $request->request->get($param)!=''){
                $params[$param] = $request->request->get($param);
            }else{
                throw new HttpException(404, 'Param ' . $param . ' not found');
            }
        }
        $user_checker = $this->container->get('net.app.commons.user_checker');
        $dni_val = $user_checker->validateUserIdentification($params['dni']);
        if(!$dni_val['result'])
            throw new HttpException(400, $dni_val['errors'][0]);


        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository($this->getRepositoryName())->findOneBy(array(
            'phone'  =>  $params['phone'],
            'prefix'  =>  $params['prefix'],
            'usernameCanonical'  =>  strtolower($params['dni'])
        ));

        $logger = $this->get('manager.logger');
        $logger->info('PASS RECOVERY REQ: '. $params['phone'] . " " . $params['dni']);
        if(!$user){
            $logger->info('PASS RECOVERY REQ: User not found');
            throw new HttpException(404, 'User not found');
        }
        $template_type = 'forget_password';

        return $this->sendSmsCode($em, $template_type, $user);
    }

    /**
     * @param \Doctrine\Persistence\ObjectManager $em
     * @param string $template_type
     * @param object $user
     * @return Response
     * @throws \Exception
     */
    private function sendSmsCode(\Doctrine\Persistence\ObjectManager $em, string $template_type, object $user): Response
    {
        $code = strval(random_int(100000, 999999));
        $template = $em->getRepository(SmsTemplates::class)->findOneBy(['type' => $template_type]);
        if (!$template) {
            throw new HttpException(404, 'Template not found');
        }
        // TODO remove when use flutter
        $user->setRecoverPasswordToken($code);
        $user->setPasswordRequestedAt(new \DateTime());

        $user->setLastSmscode($code);
        $user->setSmscodeRequestedAt(new \DateTime());
        $em->persist($user);

        $sms_log = new UsersSmsLogs();
        $sms_log->setUserId($user->getId());
        $sms_log->setType($template_type);
        $sms_log->setSecurityCode($code);
        $em->persist($sms_log);
        $em->flush();

        $sms_text = str_replace("%SMS_CODE%", $code, $template->getBody());
        UsersController::sendSMSv4($user->getPrefix(), $user->getPhone(), $sms_text, $this->container);
        return $this->rest(200, "ok", "Request successful");
    }

    /**
     * @Rest\View
     */
    public function validatePhoneRequest(Request $request){
        $paramNames = array(
            'phone',
            'prefix'
        );

        $params = array();
        foreach($paramNames as $param){
            if($request->request->has($param) && $request->request->get($param)!=''){
                $params[$param] = $request->request->get($param);
            }else{
                throw new HttpException(404, 'Param ' . $param . ' not found');
            }
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository($this->getRepositoryName())->findOneBy(array(
            'phone'  =>  $params['phone'],
            'prefix'  =>  $params['prefix']
        ));

        $logger = $this->get('manager.logger');
        $logger->info('VAL PHONE REQ: '. $params['phone']);
        if(!$user){
            $logger->info('VAL PHONE REQ: User not found');
            throw new HttpException(404, 'User not found');
        }
        $template_type = 'validate_phone';

        return $this->sendSmsCode($em, $template_type, $user);
    }

    /**
     * @Rest\View
     */
    public function changePinRequest(Request $request){
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $logger = $this->get('manager.logger');
        if(!$user){
            $logger->info('CHANGE PIN REQ: User not found');
            throw new HttpException(404, 'User not found');
        }
        $template_type = 'change_pin';
        return $this->sendSmsCode($em, $template_type, $user);
    }

    /**
     * @Rest\View
     */
    public function changePasswordRequest(Request $request){
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $logger = $this->get('manager.logger');
        if(!$user){
            $logger->info('CHANGE PASSWORD REQ: User not found');
            throw new HttpException(404, 'User not found');
        }
        $template_type = 'change_password';
        return $this->sendSmsCode($em, $template_type, $user);
    }

    /**
     * @Rest\View
     */
    public function passwordRecoveryV4(Request $request){
        $paramNames = array(
            'dni',
            'prefix',
            'phone',
            'smscode',
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
            'recover_password_token' => $params['smscode']
        ));

        $logger = $this->get('manager.logger');
        $logger->info('PASS RECOVERY: '. $params['smscode']);
        if(!$user) {
            $logger->info('PASS RECOVERY: Smscode not found');
            throw new HttpException(404, 'Smscode not found');
        }

        if(strtoupper($user->getDNI()) != strtoupper($params['dni'])){
            throw new HttpException(404, AccountController::DNI_NOT_VALID);
        }
        if($user->getPrefix() != $params['prefix']){
            throw new HttpException(404, 'Wrong prefix');
        }
        if($user->getPhone() != $params['phone']){
            throw new HttpException(404, 'Wrong phone');
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
            throw new HttpException(404, 'Expired smscode');
        }
        $logger->info('PASS RECOVERY: All done');
        return $this->rest(204,"ok", "password recovered");
    }

    public function validatePhoneCodeV4(Request $request){
        $paramNames = array(
            'phone',
            'prefix',
            'smscode',
            'dni'
        );

        $params = array();
        foreach($paramNames as $param){
            if($request->request->has($param) && $request->request->get($param)!=''){
                $params[$param] = $request->request->get($param);
            }else{
                throw new HttpException(404, 'Param ' . $param . ' not found');
            }
        }

        $code = $request->get('smscode');
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('FinancialApiBundle:User')->findOneBy(array(
            'dni' => $request->get('dni')
        ));
        if(!$user){
            throw new HttpException(400, "DNI not registered");
        }
        $validation_code = $user->getLastSmscode();

        $kyc = $em->getRepository('FinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user
        ));

        if($kyc){
            if($code == $validation_code){
                $kyc->setPhoneValidated(true);
                $em->persist($kyc);
                $user->setEnabled(true);
                $em->persist($user);
                $em->flush();
            }
            else{
                throw new HttpException(400, 'Incorrect code');
            }
        }
        else{
            throw new HttpException(400, 'User without kyc information');
        }

        $resp = $this->secureOutput($user);
        return $this->rest(204,"ok", "Request successful", $resp);
    }

    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function changePinV4(Request $request){
        $paramNames = array(
            'pin',
            'repin',
            'sms_code'
        );

        $params = array();
        foreach($paramNames as $param){
            if($request->request->has($param) && $request->request->get($param)!=''){
                $params[$param] = $request->request->get($param);
            }else{
                throw new HttpException(404, 'Param ' . $param . ' not found');
            }
        }
        /** @var User $user */
        $user = $this->get('security.token_storage')->getToken()->getUser();
        /** @var UserPasswordEncoder $encoder_service */

        if ($user->getPin() !== null){
            if($request->request->has('password') && $request->request->get('password')!=''){
                $encoder = $this->get('security.password_encoder');
                $encoded_pass = $encoder->encodePassword($user, $request->request->get('password'));
                if($encoded_pass != $user->getPassword())
                    throw new HttpException(404, 'Bad password');
            }else{
                throw new HttpException(404, 'Param password not found');
            }
        }


        $pin = preg_replace("/[^0-9]/", "", $params['pin']);
        if(strlen($pin)!=4){
            throw new HttpException(400, "Pin must be a number with 4 digits");
        }
        if($params['pin'] != $params['repin']) throw new HttpException(404, 'Pin and repin are different');

        $user->setPin($pin);
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
        $resp = $this->secureOutput($user);

        return $this->rest(200,"ok", "Password changed successfully", $resp);
    }

    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function changePasswordV4(Request $request){
        $paramNames = array(
            'old_password',
            'password',
            'repassword',
            'sms_code'
        );

        $params = array();
        foreach($paramNames as $param){
            if($request->request->has($param) && $request->request->get($param)!=''){
                $params[$param] = $request->request->get($param);
            }else{
                throw new HttpException(404, 'Param ' . $param . ' not found');
            }
        }
        /** @var User $user */
        $user = $this->get('security.token_storage')->getToken()->getUser();
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
        $userManager = $this->container->get('access_key.security.user_provider');
        $userManager->updatePassword($user);

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
        $resp = $this->secureOutput($user);

        return $this->rest(200,"ok", "Password changed successfully", $resp);
    }

    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function getDocumentsV4(Request $request){
        /** @var User $user */
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();


        if($request->query->has('company_id')){
            $user_group = $em->getRepository(UserGroup::class)->find(
                ['group' => $request->query->get('company_id'), 'user' => $user->getId()]);
            if(!isset($user_group) or !in_array("ROLE_ADMIN", $user_group->getRoles())){
                throw new HttpException(404, 'Insufficient permission');
            }
            $documents = $em->getRepository(Document::class)->findBy(
                ['account' => $request->query->get('company_id')]);
        }else{
            $documents = $em->getRepository(Document::class)->findBy(
                ['user' => $user]);
        }

        $resp = [];
        foreach ($documents as $document){
            $kind = $document->getKind();
            array_push ($resp, [
                'id'=> $document->getId(),
                'document_kind'=>  [
                    'id' => $kind->getId(),
                    'name' => $kind->getName(),
                    'description' => $kind->getDescription(),
                    'is_user_document' => $kind->getIsUserDocument(),
                    ],
                'status'=> $document->getStatus(),
                'status_text'=> $document->getStatusText(),
            ]);
        }
        return $this->rest(200,"ok", "ok", $this->secureOutput($resp));
    }

    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function addDocumentsV4(Request $request)
    {
        $paramNames = array(
            'content',
            'name',
            'kind_id'
        );

        $params = array();
        foreach($paramNames as $param){
            if($request->request->has($param) && $request->request->get($param)!=''){
                $params[$param] = $request->request->get($param);
            }else{
                throw new HttpException(404, 'Param ' . $param . ' not found');
            }
        }
        /** @var User $user */
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $kind = $em->getRepository(DocumentKind::class)->find(
            ['id' => $request->request->get('kind_id')]);

        if(!isset($kind)){
            throw new HttpException(404, 'Document Kind not found');
        }

        $document = new Document();
        $document->setContent($request->request->get('content'));
        $document->setName($request->request->get('name'));
        $document->setKind($kind);
        $document->setUserId($user->getId());
        $document->setUser($user);

        if($request->request->has('account_id') && $request->request->get('account_id')!=''){
            $account = $em->getRepository(Group::class)->find(
                ['id' => $request->request->get('account_id')]);
            $document->setAccount($account);
        }

        $em->persist($document);
        $em->flush();

        $params = [
            'mail' => ['lang' => $user->getLocale()],
            'user_id' => $user->getId(),
            'content' => $request->request->get('content')
        ];
        $to = $this->container->getParameter('kyc_email');
        $this->_sendEmail('Documentación cuenta', null, $to, 'document', $params);

        return $this->rest(200, "ok", "New Document created", ['document_id'=> $document->getId()]);
    }

    /**
     * @Rest\View
     * @param Request $request
     * @param $doc_id
     * @return Response
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function updateDocumentsV4(Request $request, $doc_id){
        /** @var User $user */
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $document = $em->getRepository(Document::class)->find(
            ['id' => $doc_id]);

        if(!isset($document)){
            throw new HttpException(404, 'Document not found');
        }elseif (!($document->getStatus() === Document::STATUS_APP_DECLINED or $document->getStatus() == 'rec_expired')){
            throw new HttpException(404, 'Only declined or expired documents can be updated');
        }

        if($document->getUserId() !== $user->getId()){
            $user_group = $em->getRepository(UserGroup::class)->find(
                ['group' => $document->getAccount(), 'user' => $user->getId()]);
            if(!isset($user_group) or !in_array("ROLE_ADMIN", $user_group->getRoles())){
                throw new HttpException(404, 'Insufficient permission');
            }
        }

        $params = [
            'mail' => ['lang' => $user->getLocale()],
            'user_id' => $user->getId(),
            'content' => $request->request->get('content')
        ];
        $document->setContent($request->request->get('content'));
        $document->setStatus(Document::STATUS_APP_SUBMITTED);
        $em->persist($document);
        $em->flush();

        $to = $this->container->getParameter('kyc_email');
        $this->_sendEmail('Documentación cuenta', null, $to, 'document', $params);

        return $this->rest(200, "ok", "Document updated", ['document_id'=> $document->getId()]);

    }

    /**
     * @Rest\View
     */
    public function unlockUser(Request $request){
        $paramNames = array(
            'dni',
            'prefix',
            'phone',
            'smscode'
        );

        $params = array();
        foreach($paramNames as $param){
            if($request->request->has($param) && $request->request->get($param)!=''){
                $params[$param] = $request->request->get($param);
            }else{
                throw new HttpException(404, 'Param ' . $param . ' not found');
            }
        }

        $em = $this->getDoctrine()->getManager();

        /** @var User $user */
        $user = $em->getRepository(User::class)->findOneBy(['usernameCanonical' => strtolower($params['dni'])]);

        if(!isset($user)){
            throw new HttpException(404, AccountController::DNI_NOT_VALID);
        }
        if($user->getPrefix() != $params['prefix']){
            throw new HttpException(404, 'Wrong prefix');
        }
        if($user->getPhone() != $params['phone']){
            throw new HttpException(404, 'Wrong phone');
        }

        if($user->getLastSmscode() == $request->request->get('smscode')){

            $user->unLockUser();
            $user->setPasswordFailures(0);
            $user->setPinFailures(0);
            $em->persist($user);
            $em->flush();
        }else{
            throw new HttpException(404, 'The sms code is invalid or has expired');
        }

        return $this->rest(204,"ok", "user unlocked");
    }

    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     * @throws AnnotationException
     * @throws \ReflectionException
     */
    public function updateV4Action(Request $request){
        /** @var User $user */
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $id = $user->getId();
              /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();

        foreach($request->request->keys() as $param){
            if($param !== 'campaign_code'){
                throw new HttpException(404,'Parameter '.$param. ' not allowed.');
            }
        }

        if($request->request->has('campaign_code')){
            /** @var EntityManagerInterface $em */
            $em = $this->getDoctrine()->getManager();
            $campaign = $em->getRepository(Campaign::class)->findOneBy(['code' => $request->request->get('campaign_code')]);

            if(isset($campaign)){
                $var_name = $campaign->getTos();
                $request->request->set($var_name, 1);
            }else{
               throw new HttpException(404,'Campaign not found');
            }
        }else{
            throw new HttpException(404,'Parameter campaign_code not found');
        }
        $request->request->remove('campaign_code');

        if($campaign->getVersion() === 1){
            $response = parent::updateAction($request, $id);

            if($response->getStatusCode() == 200) {
                if ($campaign->getName() === Campaign::CULTURE_CAMPAIGN_NAME) {
                    $this->createCultureAccountIfUserDoesNotHaveOne($id, $em, $campaign);
                }
                return $this->rest(204,"ok", "TOS updated");
            }
            return $response;
        }

        //add all private accounts from this user to campaign
        $private_accounts = $em->getRepository(Group::class)->findBy(array(
            'kyc_manager' => $user,
            'type' => Group::ACCOUNT_TYPE_PRIVATE
        ));
        foreach ($private_accounts as $private_account){
            //TODO check that this account is not in campaign already
            $account_campaign = $em->getRepository(AccountCampaign::class)->findOneBy(array(
                'account' => $private_account,
                'campaign' => $campaign
            ));
            if(!$account_campaign){
                $account_campaign = new AccountCampaign();
                $account_campaign->setCampaign($campaign);
                $account_campaign->setAccount($private_account);
                $em->persist($account_campaign);
            }

        }

        $em->flush();

        return $this->rest(204,"ok", "TOS updated");
    }

    /**
     * @param $id
     * @param $em
     * @param $campaign
     */
    private function createCultureAccountIfUserDoesNotHaveOne($id, $em, $campaign): void
    {
        $user_has_culture_account = false;
        $userAccounts = $em->getRepository(Group::class)->findBy(['kyc_manager' => $id, 'type' => Group::ACCOUNT_TYPE_PRIVATE]);
        foreach($userAccounts as $account){
            $account_campaigns = $account->getCampaigns()->getValues();
            if (count($account_campaigns) > 0 and in_array($campaign, $account_campaigns)){
                $user_has_culture_account = true;
            }
        }
        if (!$user_has_culture_account) {
            $this->container->get('bonissim_service')->CreateCampaignAccount($id, Campaign::CULTURE_CAMPAIGN_NAME, 0);
        }
    }

}
