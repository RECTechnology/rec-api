<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

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
    public function index(Request $request){

        $em = $this->getDoctrine()->getManager();
        $cards = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->findAll();

        return $this->rest(200, 'Request successfull', $cards);

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

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $userGroup = $em->getRepository('TelepayFinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $user->getId(),
            'group'   =>  $company->getId()
        ));

        if(!$userGroup->hasRole('ROLE_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        $card = $em->getRepository('TelepayFinancialApiBundle:NFCCard')->findOneBy(array(
            'id_card'   =>  $params['id_card']));

        if(!$card) throw new HttpException(404, 'NFC Card not found');

        //if(!$card->getEnabled()) throw new HttpException(403, 'Disabled card');

        //check validation email
        $kyc = $card->getUser()->getKycValidations();

//        if($kyc->getEmailValidated() == false) throw new HttpException(403, 'Email not validated');

        if(!$company->getFairpayVendor()) throw new HttpException(403, 'You are not a fairpay vendor');

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
        $balancer->addBalance($company, -$params['amount'], $sender_transaction, "nfc contr 1");

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
        $balancer->addBalance($receiverCompany, $rec_amount, $receiver_transaction, "nfc contr 2");

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

                $pin = rand(1000,9999);
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