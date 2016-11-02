<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\CashInTokens;
use Telepay\FinancialApiBundle\Entity\Group;
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
    public function registerUserCard(Request $request){

        //TODO check client => only android client is allowed
        //TODO check company => anly certain companies can do this
        //TODO create company
        //TODO create user
        //TODO create wallets
        //TODO create exchanges limits and fees
        //TODO create userGroup

    }

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

        //TODO check if email has account
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
            'email' =>  $params['email']
        ));

        $tokenGenerator = $this->container->get('fos_user.util.token_generator');
        $url = $this->container->getParameter('base_panel_url');

        if(!$user){
            //user NOT exists
            //create company
            $company = new Group();
            $company->setName($params['alias'].' Group');
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

            //generate data for generated user
            $explode_email = explode('@',$params['email']);
            $username = $explode_email[0];
            $password = Uuid::uuid1()->toString();

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
            $pin = rand(0,9999);
            $card = new NFCCard();
            $card->setCompany($company);
            $card->setUser($user);
            $card->getAlias($params['alias']);
            $card->setEnabled(false);
            $card->setIdCard($params['id_card']);
            $card->setPin($pin);
            $card->setConfirmationToken($user->getConfirmationToken());

            $em->persist($card);
            $em->flush();

            $this->_sendRegisterAndroidEmail('Chip-Chap validation e-mail and Active card', $url, $user->getEmail(), $password, $pin, $user);
        }else{
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

            //create card
            $pin = rand(0,9999);
            $card = new NFCCard();
            $card->setUser($user);
            $card->getAlias($params['alias']);
            $card->setEnabled(false);
            $card->setIdCard($params['id_card']);
            $card->setPin($pin);
            $card->setConfirmationToken($confirmationToken);

            $em->persist($card);
            $em->flush();

            $body = 'Please validate this card for one of this companies';
            $subject = 'Chip-Chap validate NFC card';
            $base_url = $url.'/card/validation/';
            //send mail with card information and validation
            $this->_sendValidateCardEmail($subject, $body, $user->getEmail(), $pin, $companies, $base_url, $confirmationToken );

        }

    }

    /**
     * @Rest\View
     */
    public function validateEmailCard(Request $request){

        if(!$request->request->has('confirmation_token')) throw new HttpException(404, 'Param confirmation_token not found');

        $token = $request->request->get('confirmation_token');

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
            'confirmation_token'    =>  $token
        ));

        if(!$user) throw new HttpException(404, 'User not found');

        if($user->getEnabled() == true) throw new HttpException(403, 'This user is validated yet');

        $card = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->findOneBy(array(
            'confirmation_token'    =>  $token
        ));

        if(!$card) throw new HttpException(404, 'NFCCard not found');

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

        $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user
        ));

        if($kyc){
            $kyc->setEmailValidated(true);
            $em->persist($kyc);
            $em->flush();
        }

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

        $card->setEnabled(true);
        $card->setCompany($company);

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

        $userGroup = $em->getRepository('TelepayFinancialApiBundle')->findBy(array(
            'user'  =>  $user,
            'company'   =>  $company
        ));

        if(!$userGroup->hasRole('ROLE_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        $card = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->find($params['id_card']);

        if(!$card) throw new HttpException(404, 'NFC Card not found');

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

        if($params['amount'] > $sender_wallet->getAvailable()) throw new HttpException(403, 'Not funds enough');

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
        $sender_transaction->setAmount($params['amount']);
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
            'amount'    =>  -$params['amount'],
            'currency'  =>  Currency::$FAC,
            'scale'     =>  Currency::$SCALE[Currency::$FAC],
            'concept'       =>  'walletToWallet from ANDROID APP'
        ));
        $sender_transaction->setTotal(-$params['amount']);
        $sender_transaction->setUser($user->getId());
        $sender_transaction->setGroup($userGroup->getId());


        $dm = $this->get('doctrine_mongodb')->getManager();

        $dm->persist($sender_transaction);

        $balancer = $this->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($userGroup, -$params['amount'], $sender_transaction);

        //FEE=1% al user
        $variable_fee = round($params['amount']*0.01,0);
        $amount = $params['amount'] - $variable_fee;

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
        $receiver_transaction->setAmount($params['amount']);
        $receiver_transaction->setDataOut(array(
            'received_from' =>  $company->getName(),
            'id_from'       =>  $company->getId(),
            'amount'        =>  $params['amount'],
            'currency'      =>  $receiver_wallet->getCurrency(),
            'previous_transaction'  =>  $sender_transaction->getId()
        ));
        $receiver_transaction->setDataIn(array(
            'sent_to'   =>  $receiverCompany->getName(),
            'id_to'     =>  $receiverCompany->getId(),
            'amount'    =>  -$params['amount'],
            'currency'  =>  Currency::$FAC,
            'description'   =>  'transfer->FAC',
            'concept'   =>  'walletToWallet from ANDROID APP'
        ));
        $receiver_transaction->setPayInInfo(array(
            'sender'   =>  $company->getName(),
            'sender_id'     =>  $company->getId(),
            'amount'    =>  $params['amount'],
            'currency'  =>  Currency::$FAC,
            'scale'  =>  Currency::$SCALE[Currency::$FAC],
            'concept'   =>  'walletToWallet from ANDROID APP'
        ));
        $receiver_transaction->setTotal($params['amount']);
        $receiver_transaction->setGroup($receiverCompany->getId());

        $dm->persist($receiver_transaction);
        $dm->flush();

        $balancer = $this->get('net.telepay.commons.balance_manipulator');
        $balancer->addBalance($receiverCompany, $amount, $receiver_transaction);

        //update wallets
        $sender_wallet->setAvailable($sender_wallet->getAvailable() - $params['amount']);
        $sender_wallet->setBalance($sender_wallet->getBalance() - $params['amount']);

        $receiver_wallet->setAvailable($receiver_wallet->getAvailable() + $amount);
        $receiver_wallet->setBalance($receiver_wallet->getBalance() + $amount);

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
    public function refreshPINCard(Request $request){

    }

    /**
     * @Rest\View
     */
    public function NFCPayment(Request $request){

    }

    private function _sendRegisterAndroidEmail($subject, $url, $to, $password, $pin, $user){
        $from = 'no-reply@chip-chap.com';
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
        $from = 'no-reply@chip-chap.com';
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
        $dm->persist($userFee);

        $em = $this->getDoctrine()->getManager();

        $rootGroupId = $this->container->getParameter('id_group_root');
        $rootGroup = $em->getRepository('TelepayFinancialApiBundle:Group')->find($rootGroupId);

        //commerce fee
        $rootFee = new Transaction();
        $rootFee->setGroup($rootGroup);
        $rootFee->setType(Transaction::$TYPE_FEE);
        $rootFee->setCurrency($user_transaction->getCurrency());
        $rootFee->setScale($user_transaction->getScale());
        $rootFee->setAmount($total_fee);
        $rootFee->setFixedFee($fixed_fee);
        $rootFee->setVariableFee($variable_fee);
        $rootFee->setService($user_transaction->getMethod().' ->fee');
        $rootFee->setMethod($user_transaction->getMethod().' ->fee');
        $rootFee->setStatus(Transaction::$STATUS_SUCCESS);
        $rootFee->setTotal(-$total_fee);
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

        $dm->persist($rootFee);
        $dm->flush();

    }

}