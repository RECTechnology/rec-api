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
use Telepay\FinancialApiBundle\Entity\CashInTokens;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\LimitDefinition;
use Telepay\FinancialApiBundle\Entity\ServiceFee;
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

        if(!$user){
            //user exists
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

            $url = $this->container->getParameter('base_panel_url');

            $tokenGenerator = $this->container->get('fos_user.util.token_generator');
            $user->setConfirmationToken($tokenGenerator->generateToken());
            $em->persist($user);
            $em->flush();
            $url = $url.'/nfc/validation/'.$user->getConfirmationToken();

            //Add user to group with admin role
            $userGroup = new UserGroup();
            $userGroup->setUser($user);
            $userGroup->setGroup($company);
            $userGroup->setRoles(array('ROLE_ADMIN'));

            $em->persist($userGroup);
            $em->flush();

        }

        //TODO create card


        $this->_sendEmail('Chip-Chap validation e-mail and Active card', $url, $user->getEmail(), $password, $pin);

    }

    /**
     * @Rest\View
     */
    public function addFundsToCard(Request $request){

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

    private function _sendEmail($subject, $body, $to, $password, $pin){
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
                            'message'        =>  $body,
                            'password'  =>  $password,
                            'pin'   =>  $pin
                        )
                    )
            )
            ->setContentType('text/html');

        $this->container->get($mailer)->send($message);
    }

}