<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Exception;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\KYC;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\NFCCard;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
use Telepay\FinancialApiBundle\Entity\TierValidations;
use Telepay\FinancialApiBundle\Entity\User;
use Telepay\FinancialApiBundle\Entity\UserGroup;
use Telepay\FinancialApiBundle\Entity\UserWallet;
use Telepay\FinancialApiBundle\Financial\Currency;

class NFCController extends RestApiController{

    /**
     * @Rest\View
     */
    public function registerCard(Request $request){

        $paramNames = array(
            'email',
            'alias',
            'id_card'
        );

        $params = array();
        foreach($paramNames as $paramName){
            if($request->request->has($paramName)){
                $params[$paramName] = $request->request->get($paramName);
            }else{
                throw new HttpException(404, 'Param '.$paramName.' not found');
            }
        }

        //TODO optional values amount and currency...if exists recharge card

        //TODO check client => only android client is allowed

        //get default creators for this king of register
        $user_creator_id = $this->container->getParameter('default_user_creator_commerce_android');
        $company_creator_id = $this->container->getParameter('default_company_creator_commerce_android');

        $em = $this->getDoctrine()->getManager();
        $userCreator = $em->getRepository('TelepayFinancialApiBundle:User')->find($user_creator_id);
        $companyCreator = $em->getRepository('TelepayFinancialApiBundle:Group')->find($company_creator_id);

        //check if email has account
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
            'email' =>  $params['email']
        ));

        $tokenGenerator = $this->container->get('fos_user.util.token_generator');
        $url = $this->container->getParameter('base_panel_url');

        $em->getConnection()->beginTransaction();

        try{
            if(!$user){
                if($request->request->has('pin') && $request->request->get('pin') != ''){
                    $pin = $request->request->get('pin');
                    $enabled = true;
                }else{
                    throw new HttpException(403, 'User not found');
                }
                //user NOT exists
                //check if company name exists and generate new one
                $group = $em->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
                    'name'  =>  $params['alias']. 'Company'
                ));
                $name = $params['alias']. 'Company';
                if($group){
                    $name = rand(0,1000).'_'.$params['alias'].'Company';
                }
                //create company
                $company = new Group();
                $company->setName($name);
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
//                    $limit = new LimitDefinition();
//                    $limit->setDay(0);
//                    $limit->setWeek(0);
//                    $limit->setMonth(0);
//                    $limit->setYear(0);
//                    $limit->setTotal(0);
//                    $limit->setSingle(0);
//                    $limit->setCname('exchange_'.$exchange->getCname());
//                    $limit->setCurrency($exchange->getCurrencyOut());
//                    $limit->setGroup($company);
                    //create fee for this group
                    $fee = new ServiceFee();
                    $fee->setFixed(0);
                    $fee->setVariable(1);
                    $fee->setCurrency($exchange->getCurrencyOut());
                    $fee->setServiceName('exchange_'.$exchange->getCname());
                    $fee->setGroup($company);

//                    $em->persist($limit);
                    $em->persist($fee);

                }

//                $fac_limit = new LimitDefinition();
//                $fac_limit->setDay(-1);
//                $fac_limit->setCname('fac-in');
//                $fac_limit->setWeek(-1);
//                $fac_limit->setMonth(-1);
//                $fac_limit->setYear(-1);
//                $fac_limit->setSingle(-1);
//                $fac_limit->setTotal(-1);
//                $fac_limit->setCurrency(Currency::$FAC);
//                $fac_limit->setGroup($company);
//                $em->persist($fac_limit);
//                $em->flush();

                //generate data for generated user
                $explode_email = explode('@',$params['email']);
                $username = $explode_email[0];
                $user = $em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
                    'username'  =>  $username
                ));

                if($user) $username = rand(0, 1000).'-'.$username;
                //cambiar por password random
                $password = $this->_randomPassword();

                //create user
                $user = new User();
                $user->setPlainPassword($password);
                $user->setEmail($params['email']);
                $user->setRoles(array('ROLE_USER'));
                $user->setName($username);
                $user->setUsername($username);
                $user->setActiveGroup($company);
                $user->setBase64Image('');
                $user->setEnabled(false);

                $user->setConfirmationToken($tokenGenerator->generateToken());
                $em->persist($user);
                $em->flush();
                $url = $url.'/user/validation_nfc/'.$user->getConfirmationToken();

                //Add user to group with admin role
                $userGroup = new UserGroup();
                $userGroup->setUser($user);
                $userGroup->setGroup($company);
                $userGroup->setRoles(array('ROLE_ADMIN'));

                $em->persist($userGroup);

                //create card
                $card = new NFCCard();
                $card->setCompany($company);
                $card->setUser($user);
                $card->setAlias($params['alias']);
                $card->setEnabled($enabled);
                $card->setIdCard($params['id_card']);
                $card->setPin($pin);
                $card->setConfirmationToken($user->getConfirmationToken());

                $kyc = new KYC();
                $kyc->setUser($user);
                $kyc->setEmail($user->getEmail());

                $em->persist($card);
                $em->persist($kyc);
                $em->flush();

                $this->_sendRegisterAndroidEmail('Chip-Chap validation e-mail and Active card', $url, $user->getEmail(), $password, $pin, $user);
            }else{

                if($request->request->has('pin')) throw new HttpException(403, 'This user already has an account');

                //user exists
                //get companies
                $userGroups = $em->getRepository('TelepayFinancialApiBundle:UserGroup')->findBy(array(
                    'user'  =>  $user
                ));

                $companies = array();

                $confirmationToken = $tokenGenerator->generateToken();
                foreach($userGroups as $userGroup){
                    if($userGroup->hasRole('ROLE_ADMIN')){
                        $companies[] = $userGroup->getGroup();

                    }
                }

                if(count($companies) < 1) throw new HttpException(403, 'You don\' have the necessary permissions for this company');

                $card = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->findOneBy(array(
                    'id_card' =>  $params['id_card']
                ));

                if($card) throw new HttpException(409, 'Duplicated id');

                $card = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->findOneBy(array(
                    'user' =>  $user,
                    'alias' =>  $params['alias']
                ));

                if($card) throw new HttpException(409, 'Duplicated alias');

                //create card
                $pin = rand(0,9999);
                $card = new NFCCard();
                $card->setUser($user);
                $card->setAlias($params['alias']);
                $card->setEnabled(false);
                $card->setIdCard($params['id_card']);
                $card->setPin($pin);
                $card->setConfirmationToken($confirmationToken);

                $em->persist($card);
                $em->flush();

                $body = 'Please validate this card for one of this companies';
                $subject = 'Chip-Chap validate NFC card';
                $base_url = $url.'/user/validation_nfc/';
                //send mail with card information and validation
                $this->_sendValidateCardEmail($subject, $body, $user->getEmail(), $pin, $companies, $base_url, $confirmationToken );

            }
            $em->getConnection()->commit();
        }catch(ConstraintViolationException $e){
            $em->getConnection()->rollBack();
            if(preg_match('/1062 Duplicate entry/i',$e->getMessage()))
                throw new HttpException(409, "Duplicated resource");
            else if(preg_match('/1048 Column/i',$e->getMessage()))
                throw new HttpException(400, "Bad parameters");
            throw new HttpException(500, "Unknown error occurred when save");
        }catch(HttpException $e){
            $em->getConnection()->rollBack();
            throw $e;
        }catch (Exception $e){
            $em->getConnection()->rollBack();
            throw new HttpException(500, "Unknown error occurred when save");
        }

        $response = array(
            'status'  =>  'created',
            'id_card'   =>  $card->getId()
        );

        return $this->restV2(201,"ok", "Card registered successfully", $response);

    }

    /**
     * @Rest\View
     */
    public function validateEmailCard(Request $request){

        if(!$request->request->has('confirmation_token')) throw new HttpException(404, 'Param confirmation_token not found');

        $token = $request->request->get('confirmation_token');

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
            'confirmationToken'    =>  $token
        ));

        if(!$user) throw new HttpException(404, 'User not found');

        if($user->isEnabled() == true) throw new HttpException(403, 'This user is validated yet');

        $card = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->findOneBy(array(
            'confirmation_token'    =>  $token
        ));

        if(!$card) throw new HttpException(404, 'NFCCard not found');

//        $tierValidation = $em->getRepository('TelepayFinancialApiBundle:TierValidations')->findOneBy(array(
//            'user' => $user
//        ));
//
//        if(!$tierValidation){
//            $tier = new TierValidations();
//            $tier->setUser($user);
//            $tier->setEmail(true);
//            $em->persist($tier);
//            $em->flush();
//        }else{
//            throw new HttpException(409, 'Validation not allowed');
//        }

        $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user
        ));

        if($kyc){
            $kyc->setEmailValidated(true);
            $em->persist($kyc);
            $em->flush();
        }else{
            $kyc = new KYC();
            $kyc->setUser($user);
            $kyc->setEmail($user->getEmail());
            $kyc->setEmailValidated(true);

            $em->persist($kyc);
            $em->flush();
        }

        //change confirmation_token
        $tokenGenerator = $this->container->get('fos_user.util.token_generator');
        $confirmationToken = $tokenGenerator->generateToken();
        $card->setConfirmationToken($confirmationToken);
        $user->setEnabled(true);
        $card->setEnabled(true);

        $em->persist($user);
        $em->persist($card);

        $em->flush();

        $response = array(
            'username'  =>  $user->getUsername(),
            'email'     =>  $user->getEmail()
        );

        return $this->restV2(201,"ok", "Validation email and NFC Card succesfully", $response);
    }

    /**
     * @Rest\View
     */
    public function validateCard(Request $request, $company_id){

        if(!$request->request->has('confirmation_token')) throw new HttpException(404, 'Param confirmation_token not found');

        $token = $request->request->get('confirmation_token');

        $em = $this->getDoctrine()->getManager();

        $card = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->findOneBy(array(
            'confirmation_token'    =>  $token
        ));

        if(!$card) throw new HttpException(404, 'NFCCard not found');
        $company = $em->getRepository('TelepayFinancialApiBundle:Group')->find($company_id);

        if($card->getEnabled() == true) throw new HttpException(403, 'Card validated yet');

        $card->setEnabled(true);
        $card->setCompany($company);

        //change confirmation_token
        $tokenGenerator = $this->container->get('fos_user.util.token_generator');
        $confirmationToken = $tokenGenerator->generateToken();
        $card->setConfirmationToken($confirmationToken);
        $em->persist($card);
        $em->flush();

        $response = array(
            'company'  =>  $company->getName(),
            'card'     =>  $card->getAlias()
        );

        return $this->restV2(201,"ok", "Validation NFC Card succesfully", $response);

    }

    /**
     * @Rest\View
     */
    public function disableCard(Request $request){

        if(!$request->request->has('confirmation_token')) throw new HttpException(404, 'Param confirmation_token not found');

        $token = $request->request->get('confirmation_token');

        $em = $this->getDoctrine()->getManager();

        $card = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->findOneBy(array(
            'confirmation_token'    =>  $token
        ));

        if(!$card) throw new HttpException(404, 'NFCCard not found');

        //TODO check lastDisableRequested

        $card->setEnabled(false);

        $em->persist($card);
        $em->flush();

        $response = array(
            'card'     =>  $card->getAlias()
        );

        return $this->restV2(201,"ok", "Deactivate NFC Card succesfully", $response);

    }

    /**
     * @Rest\View
     */
    public function refreshPin(Request $request){

        if(!$request->request->has('refresh_pin_token')) throw new HttpException(404, 'Param refresh_pin_token not found');

        $token = $request->request->get('refresh_pin_token');

        $em = $this->getDoctrine()->getManager();

        $card = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->findOneBy(array(
            'refresh_pin_token'    =>  $token
        ));

        if(!$card) throw new HttpException(404, 'NFCCard not found');

        if($card->getNewPin() == '') throw new HttpException('This pin has been changed yet');
        //TODO check lastDisableRequested

        $card->setPin($card->getNewPin());
        $card->setNewPin('');

        $em->persist($card);
        $em->flush();

        $response = array(
            'card'     =>  $card->getAlias()
        );

        return $this->restV2(201,"ok", "Deactivate NFC Card succesfully", $response);

    }

    /**
     * @Rest\View
     */
    public function rechargeCard(Request $request, $company_id){

        $paramNames = array(
            'id_card',
            'amount'
        );

        $params = array();

        foreach($paramNames as $paramName){
            if($request->request->has($paramName)){
                $params[$paramName] = $request->request->get($paramName);
            }else{
                throw new HttpException(404, 'Param '.$paramName.' not found');
            }
        }

        //create transaction walletToWallet
        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository('TelepayFinancialApiBundle:Group')->find($company_id);
        if(!$company) throw new HttpException(404, 'Group not found');

        $user = $this->get('security.context')->getToken()->getUser();

        $userGroup = $em->getRepository('TelepayFinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $user->getId(),
            'group'   =>  $company->getId()
        ));

        if(!$userGroup->hasRole('ROLE_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        $card = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->findOneBy(array(
            'id_card'   =>  $params['id_card']));

        if(!$card) throw new HttpException(404, 'NFC Card not found');

        if(!$card->getEnabled()) throw new HttpException(403, 'Disabled card');

        //check validation email
        $kyc = $card->getUser()->getKycValidations();

        if($kyc->getEmailValidated() == false) throw new HttpException(403, 'Email not validated');

        $receiverCompany = $card->getCompany();

        //currency FAC
        $sender_wallet = $em->getRepository('TelepayFinancialApiBundle:UserWallet')->findOneBy(array(
            'group'  =>  $company,
            'currency'  =>  Currency::$FAC
        ));

        $receiver_wallet = $em->getRepository('TelepayFinancialApiBundle:UserWallet')->findOneBy(array(
            'group'  =>  $receiverCompany,
            'currency'  =>  Currency::$FAC
        ));

        $amount = $params['amount'];
        $currency_in = Currency::$FAC;
        //get currency in
        if($request->request->has('currency_in')){
            $currency_in = strtoupper($request->request->get('currency_in'));
            if($currency_in != Currency::$FAC){
                //do exchange
                $amount = $this->get('net.telepay.commons.exchange_manipulator')->exchangeInverse($params['amount'], $currency_in, 'FAIRP');
            }
        }
        if($amount > $sender_wallet->getAvailable()) throw new HttpException(403, 'Not funds enough');

        //Generate transactions and update wallets - without fees
        //SENDER TRANSACTION
        $sender_transaction = new Transaction();
        $sender_transaction->setStatus(Transaction::$STATUS_SUCCESS);
        $sender_transaction->setScale($sender_wallet->getScale());
        $sender_transaction->setCurrency($sender_wallet->getCurrency());
        $sender_transaction->setIp('');
        $sender_transaction->setVersion('');
        $sender_transaction->setService('transfer');
        $sender_transaction->setMethod('wallet_to_wallet');
        $sender_transaction->setType('out');
        $sender_transaction->setVariableFee(0);
        $sender_transaction->setFixedFee(0);
        $sender_transaction->setAmount($amount);
        $sender_transaction->setDataIn(array(
            'description'   =>  'transfer->FAC',
            'concept'       =>  'walletToWallet from ANDROID APP'
        ));
        $sender_transaction->setDataOut(array(
            'sent_to'   =>  $receiverCompany->getName(),
            'id_to'     =>  $receiverCompany->getId(),
            'amount'    =>  -$amount,
            'currency'  =>  Currency::$FAC
        ));
        $sender_transaction->setPayOutInfo(array(
            'beneficiary'   =>  $receiverCompany->getName(),
            'beneficiary_id'     =>  $receiverCompany->getId(),
            'amount'    =>  -$amount,
            'currency'  =>  Currency::$FAC,
            'scale'     =>  Currency::$SCALE[Currency::$FAC],
            'concept'       =>  'walletToWallet from ANDROID APP'
        ));
        $sender_transaction->setTotal(-$amount);
        $sender_transaction->setUser($user->getId());
        $sender_transaction->setGroup($company->getId());


        $dm = $this->get('doctrine_mongodb')->getManager();

        $dm->persist($sender_transaction);

        $balancer = $this->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($company, -$params['amount'], $sender_transaction);

        //FEE=1% al user
        $variable_fee = round($amount*0,0);
        $rec_amount = $amount - $variable_fee;

        //RECEIVER TRANSACTION
        $receiver_transaction = new Transaction();
        $receiver_transaction->setStatus(Transaction::$STATUS_SUCCESS);
        $receiver_transaction->setScale($sender_wallet->getScale());
        $receiver_transaction->setCurrency($sender_wallet->getCurrency());
        $receiver_transaction->setIp('');
        $receiver_transaction->setVersion('');
        $receiver_transaction->setService('transfer');
        $receiver_transaction->setMethod('wallet_to_wallet');
        $receiver_transaction->setType('in');
        $receiver_transaction->setVariableFee(0);
        $receiver_transaction->setFixedFee(0);
        $receiver_transaction->setAmount($amount);
        $receiver_transaction->setDataOut(array(
            'received_from' =>  $company->getName(),
            'id_from'       =>  $company->getId(),
            'amount'        =>  $amount,
            'currency'      =>  $receiver_wallet->getCurrency(),
            'previous_transaction'  =>  $sender_transaction->getId()
        ));
        $receiver_transaction->setDataIn(array(
            'sent_to'   =>  $receiverCompany->getName(),
            'id_to'     =>  $receiverCompany->getId(),
            'amount'    =>  -$amount,
            'currency'  =>  Currency::$FAC,
            'description'   =>  'transfer->FAC',
            'concept'   =>  'walletToWallet from ANDROID APP'
        ));
        $receiver_transaction->setPayInInfo(array(
            'sender'   =>  $company->getName(),
            'sender_id'     =>  $company->getId(),
            'amount'    =>  $amount,
            'currency'  =>  Currency::$FAC,
            'scale'  =>  Currency::$SCALE[Currency::$FAC],
            'concept'   =>  'walletToWallet from ANDROID APP'
        ));
        $receiver_transaction->setTotal($amount);
        $receiver_transaction->setGroup($receiverCompany->getId());

        $dm->persist($receiver_transaction);
        $dm->flush();

        $balancer = $this->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($receiverCompany, $rec_amount, $receiver_transaction);

        //update wallets
        $sender_wallet->setAvailable($sender_wallet->getAvailable() - $amount);
        $sender_wallet->setBalance($sender_wallet->getBalance() - $amount);

        $receiver_wallet->setAvailable($receiver_wallet->getAvailable() + $rec_amount);
        $receiver_wallet->setBalance($receiver_wallet->getBalance() + $rec_amount);

        $em->persist($sender_wallet);
        $em->persist($receiver_wallet);
        $em->flush();

        //create feeTransactions
        $this->_dealer(0, $variable_fee, $receiver_transaction);

        return $this->restV2(200, "ok", "Transaction got successfully");

    }

    /**
     * @Rest\View
     */
    public function updateCard(Request $request){

        //TODO deactivate card by email
        //get card
        $em = $this->getDoctrine()->getManager();

        if($request->request->has('action')){
            $action = $request->request->get('action');
            if($action == 'refresh_pin'){
                if(!$request->request->has('id_card')) throw new HttpException(404, 'id_card not found');
                $id_card = $request->request->get('id_card');

                $card = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->findOneBy(array(
                    'id_card'   =>  $id_card
                ));

                if(!$card) throw new HttpException(404, 'NFC Card not found');

                if($card->getEnabled() == 0) throw new HttpException(403, 'Disabled card');

                //check validation email
                $kyc = $card->getUser()->getKycValidations();

                if($kyc->getEmailValidated() == false) throw new HttpException(403, 'Email not validated');

                //generate new pin
                $tokenGenerator = $this->container->get('fos_user.util.token_generator');
                $confirmationToken = $tokenGenerator->generateToken();

                $pin = rand(0,9999);
                $card->setNewPin($pin);
                $card->setLastPinRequested(new \DateTime());
                $card->setRefreshPinToken($confirmationToken);
                $em->flush();

                $url = $this->container->getParameter('base_panel_url');
                $url = $url.'/user/refresh_pin/';

                //send email
                $this->_sendUpdateCardEmail($card, 'refresh_pin', $url);

                return $this->restV2(204, 'Pin request successfully.');
            }elseif($action == 'disable_card'){
                if(!$request->request->has('email')) throw new HttpException(404, 'Param email not found');
                $user = $em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
                    'email' =>  $request->request->get('email')
                ));

                if(!$user) throw new HttpException(404, 'User not found');
                //check validation email
                $kyc = $user->getKycValidations();

                if($kyc->getEmailValidated() == false) throw new HttpException(403, 'Email not validated');

                $cards = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->findBy(array(
                    'user'   =>  $user->getId()
                ));

                if(!$cards) throw new HttpException(404, 'No cards found');

                $url = $this->container->getParameter('base_panel_url');
                $url = $url.'/user/disable_nfc/';

                foreach($cards as $card){
                    $card->setLastDisableRequested(new \DateTime());
                    $em->flush();
                }

                $this->_sendDeactivateCardEmail($request->request->get('email'), $cards, 'disable', $url);

                return $this->restV2(204, 'Pin successfully changed');

            }else{
                throw new HttpException(404, 'Action not allowed');
            }
        }else{
            throw new HttpException(403, 'Method not implemented');
        }

    }

    /**
     * @Rest\View
     */
    public function readBalanceNFCCard(Request $request){
        $paramNames = array(
            'id_card'
        );

        $params = array();
        foreach($paramNames as $paramName){
            if($request->request->has($paramName)){
                $params[$paramName] = $request->request->get($paramName);
            }else{
                throw new HttpException(404, 'Param '.$paramName.' not found');
            }
        }

        $em = $this->getDoctrine()->getManager();
        $card = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->findOneBy(array(
            'id_card'   =>  $params['id_card']
        ));

        if(!$card) throw new HttpException(404, 'Card not found');

        if($card->getEnabled() == 0) throw new HttpException(403, 'Disabled card');

        //check validation email
        $kyc = $card->getUser()->getKycValidations();

        if($kyc->getEmailValidated() == false) throw new HttpException(403, 'Email not validated');

        $company = $card->getCompany();

        $wallet = $company->getWallet(Currency::$FAC);

        $balance = number_format(round($wallet->getAvailable()/1e8,6),6);

        //send balance email
        $this->_sendNFCBalanceEmail($card, $balance);

        return $this->restV2(201, "ok", "Send balance successfully");
    }

    /**
     * @Rest\View
     */
    public function NFCPayment(Request $request, $id_company){

        $em = $this->getDoctrine()->getManager();
        $receiverCompany = $em->getRepository('TelepayFinancialApiBundle:Group')->find($id_company);
        $logger = $this->get('transaction.logger');
        $logger->error('NFCPymanet INIT');
        if(!$receiverCompany) throw new HttpException(404, 'Company not found');

        $paramNames = array(
            'id_card',
            'amount',
            'signature'
        );
        $params = array();
        foreach($paramNames as $paramName){
            if($request->request->get($paramName)){
                $params[$paramName] = $request->request->get($paramName);
            }else{
                throw new HttpException(404, 'Param '.$paramName.' not found');
            }
        }

        $logger->error('NFCPymanet GETTING CARD');
        $card = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->findOneBy(array(
            'id_card' => $params['id_card']
        ));

        if(!$card) throw new HttpException(404, 'Card not found');

        $data_to_sign = $params['amount'] . $params['id_card'] . $id_company;
        $logger->error('NFCPymanet DATA TO SIGN => '. $data_to_sign);
        $logger->error('NFCPymanet secret => '. $receiverCompany->getAccessSecret());

        $signature = hash_hmac('sha256', $data_to_sign, $receiverCompany->getAccessSecret().$card->getPin());

        $logger->error('NFCPymanet signature => '. $signature. ' received signature '.$params['signature']);
        if($params['signature'] != $signature) throw new HttpException(403, 'Bad signature');

        if(!$card->getEnabled()) throw new HttpException(403, 'Disabled card');

        //check validation email
        $kyc = $card->getUser()->getKycValidations();

        if($kyc->getEmailValidated() == false) throw new HttpException(403, 'Email not validated');

        $amount = $params['amount'];
        $logger->error('NFCPymanet RECEIVED AMOUNT => '.$amount);
        $currency_in = Currency::$FAC;
        //get currency in
        if($request->request->has('currency_in')){
            $currency_in = strtoupper($request->request->get('currency_in'));
            $logger->error('NFCPymanet CURRENCY IN => '.$currency_in);
            if($currency_in != Currency::$FAC){
                //do exchange
                $amount = $this->get('net.telepay.commons.exchange_manipulator')->exchangeInverse($params['amount'], $currency_in, 'FAIRP');
                $logger->error('NFCPymanet AMOUNT AFTER EXCHANGE => '.$amount);
            }
        }

        //walletToWallet transaction from user to commerce
        $senderCompany = $card->getCompany();

        $senderWallet = $senderCompany->getWallet(Currency::$FAC);
        $receiverWallet = $receiverCompany->getWallet(Currency::$FAC);

        //Check funds sender wallet
        if($senderWallet->getAvailable() < $amount) throw new HttpException(403, 'Insuficient funds');

        $concept = 'walletToWallet from ANDROID FAIR APP';
        $url_notification = '';
        if($request->request->has('url_notification')) $url_notification = $request->request->get('url_notification');
        if($request->request->has('concept')) $concept = $request->request->get('concept');

        //SENDER TRANSACTION
        $sender_transaction = new Transaction();
        $sender_transaction->setStatus(Transaction::$STATUS_SUCCESS);
        $sender_transaction->setScale($senderWallet->getScale());
        $sender_transaction->setCurrency($senderWallet->getCurrency());
        $sender_transaction->setIp('');
        $sender_transaction->setVersion('');
        $sender_transaction->setService('transfer');
        $sender_transaction->setMethod('wallet_to_wallet');
        $sender_transaction->setType('out');
        $sender_transaction->setVariableFee(0);
        $sender_transaction->setFixedFee(0);
        $sender_transaction->setAmount($amount);
        $sender_transaction->setDataIn(array(
            'description'   =>  'transfer->FAC',
            'concept'       =>  'walletToWallet from ANDROID APP'
        ));
        $sender_transaction->setDataOut(array(
            'sent_to'   =>  $receiverCompany->getName(),
            'id_to'     =>  $receiverCompany->getId(),
            'amount'    =>  -$params['amount'],
            'currency'  =>  Currency::$FAC
        ));
        $sender_transaction->setPayOutInfo(array(
            'beneficiary'   =>  $receiverCompany->getName(),
            'beneficiary_id'     =>  $receiverCompany->getId(),
            'amount'    =>  -$amount,
            'currency'  =>  Currency::$FAC,
            'scale'     =>  Currency::$SCALE[Currency::$FAC],
            'concept'       =>  $concept,
            'url_notification'  =>  $url_notification,
            'received_amount'   =>  $params['amount'],
            'currency_in'   =>  $currency_in
        ));
        $sender_transaction->setTotal(-$amount);
        $sender_transaction->setUser($card->getUser()->getId());
        $sender_transaction->setGroup($card->getCompany()->getId());


        $dm = $this->get('doctrine_mongodb')->getManager();

        $dm->persist($sender_transaction);

        $balancer = $this->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($senderCompany, -$amount, $sender_transaction);

        //FEE=1% al user
        $variable_fee = round($amount*0,0);
        $receiver_amount = $amount - $variable_fee;

        //RECEIVER TRANSACTION
        $receiver_transaction = new Transaction();
        $receiver_transaction->setStatus(Transaction::$STATUS_SUCCESS);
        $receiver_transaction->setScale($senderWallet->getScale());
        $receiver_transaction->setCurrency($senderWallet->getCurrency());
        $receiver_transaction->setIp('');
        $receiver_transaction->setVersion('');
        $receiver_transaction->setService('transfer');
        $receiver_transaction->setMethod('wallet_to_wallet');
        $receiver_transaction->setType('in');
        $receiver_transaction->setVariableFee($variable_fee);
        $receiver_transaction->setFixedFee(0);
        $receiver_transaction->setAmount($receiver_amount);
        $receiver_transaction->setDataOut(array(
            'received_from' =>  $senderCompany->getName(),
            'id_from'       =>  $senderCompany->getId(),
            'amount'        =>  $receiver_amount,
            'currency'      =>  $receiverWallet->getCurrency(),
            'previous_transaction'  =>  $sender_transaction->getId()
        ));
        $receiver_transaction->setDataIn(array(
            'sent_to'   =>  $receiverCompany->getName(),
            'id_to'     =>  $receiverCompany->getId(),
            'amount'    =>  -$receiver_amount,
            'currency'  =>  Currency::$FAC,
            'description'   =>  'transfer->FAC',
            'concept'   =>  'walletToWallet from ANDROID APP'
        ));
        $receiver_transaction->setPayInInfo(array(
            'sender'   =>  $senderCompany->getName(),
            'sender_id'     =>  $senderCompany->getId(),
            'amount'    =>  $receiver_amount,
            'currency'  =>  Currency::$FAC,
            'scale'  =>  Currency::$SCALE[Currency::$FAC],
            'concept'   =>  'walletToWallet from ANDROID APP'
        ));
        $receiver_transaction->setTotal($receiver_amount);
        $receiver_transaction->setGroup($receiverCompany->getId());

        $dm->persist($receiver_transaction);
        $dm->flush();

        $balancer->addBalance($receiverCompany, $amount, $receiver_transaction);

        //update wallets
        $senderWallet->setAvailable($senderWallet->getAvailable() - $amount);
        $senderWallet->setBalance($senderWallet->getBalance() - $amount);

        $receiverWallet->setAvailable($receiverWallet->getAvailable() + $receiver_amount);
        $receiverWallet->setBalance($receiverWallet->getBalance() + $receiver_amount);

        $em->persist($senderWallet);
        $em->persist($receiverWallet);
        $em->flush();

        //create feeTransactions
        $this->_dealer(0, $variable_fee, $receiver_transaction);

        return $this->methodTransaction(201, $receiver_transaction, "Done");

    }

    /**
     * @Rest\View
     */
    public function checkNFCPayment(Request $request, $id_company, $id){

        $logger = $this->get('transaction.logger');
        $logger->error('CHECKNFCPymanet INIT company => '.$id_company.' transaction => '.$id);
        $dm = $this->get('doctrine_mongodb')->getManager();

        $user = $this->getUser();
        //TODO check if this user has this company

        $transaction = $dm->getRepository('TelepayFinancialApiBundle:Transaction')->findOneBy(array(
            'id'    =>  $id,
            'group' =>  intval($id_company),
            'method'    =>  'wallet_to_wallet'
        ));

        if(!$transaction){
            $logger->error('CHECKNFCPymanet transaction not found');
            throw new HttpException(404, 'Transaction not found');
        }
        $logger->error('CHECKNFCPymanet successfully');
        return $this->methodTransaction(200, $transaction, "Done");

    }

    private function _sendRegisterAndroidEmail($subject, $url, $to, $password, $pin, $user){
        $from = $this->container->getParameter('no_reply_email');
        $mailer = 'mailer';
        $template = 'TelepayFinancialApiBundle:Email:registerconfirm_android.html.twig';

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
                            'url'        =>  $url,
                            'password'  =>  $password,
                            'pin'   =>  $pin,
                            'user'  =>  $user
                        )
                    )
            )
            ->setContentType('text/html');

        $this->container->get($mailer)->send($message);
    }

    private function _sendValidateCardEmail($subject, $body, $to, $pin, $companies, $base_url, $confirmation_token){
        $from = $this->container->getParameter('no_reply_email');
        $mailer = 'mailer';
        $template = 'TelepayFinancialApiBundle:Email:NFCconfirm_android.html.twig';

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
                            'message'        =>  $body,
                            'pin'   =>  $pin,
                            'companies' =>  $companies,
                            'base_url'  =>  $base_url,
                            'confirmation_token'    =>  $confirmation_token
                        )
                    )
            )
            ->setContentType('text/html');

        $this->container->get($mailer)->send($message);
    }

    private function _dealer($fixed_fee, $variable_fee, Transaction $user_transaction){
        //TODO generate 2 fee transactions

        $total_fee = $fixed_fee + $variable_fee;
        //user fee
        $userFee = new Transaction();
        if($user_transaction->getUser()) $userFee->setUser($user_transaction->getUser());
        $userFee->setGroup($user_transaction->getGroup());
        $userFee->setType(Transaction::$TYPE_FEE);
        $userFee->setCurrency($user_transaction->getCurrency());
        $userFee->setScale($user_transaction->getScale());
        $userFee->setAmount($total_fee);
        $userFee->setFixedFee($fixed_fee);
        $userFee->setVariableFee($variable_fee);
        $userFee->setService($user_transaction->getMethod().' ->fee');
        $userFee->setMethod($user_transaction->getMethod().' ->fee');
        $userFee->setStatus(Transaction::$STATUS_SUCCESS);
        $userFee->setTotal(-$total_fee);
        $userFee->setDataIn(array(
            'previous_transaction'  =>  $user_transaction->getId(),
            'transaction_amount'    =>  $user_transaction->getAmount(),
            'total_fee' =>  $total_fee
        ));
        $userFeeInfo = array(
            'previous_transaction'  =>  $user_transaction->getId(),
            'previous_amount'   =>  $user_transaction->getAmount(),
            'amount'                =>  $total_fee,
            'currency'      =>  $user_transaction->getCurrency(),
            'scale'     =>  $user_transaction->getScale(),
            'concept'           =>  $user_transaction->getMethod().' ->fee',
            'status'    =>  Transaction::$STATUS_SUCCESS
        );
        $userFee->setFeeInfo($userFeeInfo);
        $userFee->setClient($user_transaction->getClient());

        $dm = $this->get('doctrine_mongodb')->getManager();

        $em = $this->getDoctrine()->getManager();

        $rootGroupId = $this->container->getParameter('id_group_root');
        $rootGroup = $em->getRepository('TelepayFinancialApiBundle:Group')->find($rootGroupId);

        //commerce fee
        $rootFee = new Transaction();
        $rootFee->setGroup($rootGroupId);
        $rootFee->setType(Transaction::$TYPE_FEE);
        $rootFee->setCurrency($user_transaction->getCurrency());
        $rootFee->setScale($user_transaction->getScale());
        $rootFee->setAmount($total_fee);
        $rootFee->setFixedFee($fixed_fee);
        $rootFee->setVariableFee($variable_fee);
        $rootFee->setService($user_transaction->getMethod().' ->fee');
        $rootFee->setMethod($user_transaction->getMethod().' ->fee');
        $rootFee->setStatus(Transaction::$STATUS_SUCCESS);
        $rootFee->setTotal($total_fee);
        $rootFee->setDataIn(array(
            'previous_transaction'  =>  $user_transaction->getId(),
            'transaction_amount'    =>  $user_transaction->getAmount(),
            'total_fee' =>  $total_fee
        ));
        $rootFeeInfo = array(
            'previous_transaction'  =>  $user_transaction->getId(),
            'previous_amount'   =>  $user_transaction->getAmount(),
            'amount'                =>  $total_fee,
            'currency'      =>  $user_transaction->getCurrency(),
            'scale'     =>  $user_transaction->getScale(),
            'concept'           =>  $user_transaction->getMethod().' ->fee',
            'status'    =>  Transaction::$STATUS_SUCCESS
        );
        $rootFee->setFeeInfo($rootFeeInfo);
        $rootFee->setClient($user_transaction->getClient());

        $dm->flush();

    }

    private function _sendNFCBalanceEmail(NFCCard $card, $balance){
        $from = $this->container->getParameter('no_reply_email');
        $mailer = 'mailer';
        $template = 'TelepayFinancialApiBundle:Email:NFCBalance.html.twig';

        $message = \Swift_Message::newInstance()
            ->setSubject('This is your balance')
            ->setFrom($from)
            ->setTo(array(
                $card->getUser()->getEmail()
            ))
            ->setBody(
                $this->container->get('templating')
                    ->render($template,
                        array(
                            'card'  =>  $card,
                            'balance' =>  $balance
                        )
                    )
            )
            ->setContentType('text/html');

        $this->container->get($mailer)->send($message);
    }

    private function _sendUpdateCardEmail(NFCCard $card, $action, $url){
        $from = $this->container->getParameter('no_reply_email');
        $mailer = 'mailer';
        $template = 'TelepayFinancialApiBundle:Email:NFCUpdate.html.twig';

        if($action == 'refresh_pin'){
            $subject = 'Your pin has been changed';
        }else{
            $subject = 'Deactivate card';
        }
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo(array(
                $card->getUser()->getEmail()
            ))
            ->setBody(
                $this->container->get('templating')
                    ->render($template,
                        array(
                            'card'  =>  $card,
                            'action'    =>  $action,
                            'url'   =>  $url
                        )
                    )
            )
            ->setContentType('text/html');

        $this->container->get($mailer)->send($message);
    }

    private function _randomPassword() {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array();
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

    private function _sendDeactivateCardEmail($email, $cards, $action, $url){
        $from = $this->container->getParameter('no_reply_email');
        $mailer = 'mailer';
        $template = 'TelepayFinancialApiBundle:Email:NFCUpdate.html.twig';

        $subject = 'Disable card';
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo(array(
                $email
            ))
            ->setBody(
                $this->container->get('templating')
                    ->render($template,
                        array(
                            'cards'  =>  $cards,
                            'action'    =>  $action,
                            'url'   =>  $url
                        )
                    )
            )
            ->setContentType('text/html');

        $this->container->get($mailer)->send($message);
    }
}