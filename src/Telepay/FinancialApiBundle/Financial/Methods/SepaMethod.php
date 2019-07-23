<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace Telepay\FinancialApiBundle\Financial\Methods;

use FOS\OAuthServerBundle\Util\Random;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseMethod;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashInInterface;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashOutInterface;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Financial\Currency;

class SepaMethod extends BaseMethod {

    private $driver;
    private $container;
    private $minimum;

    public function __construct($name, $cname, $type, $currency, $email_required, $base64Image, $image, $container, $driver, $min_tier, $default_fixed_fee, $default_variable_fee, $minimum){
        parent::__construct($name, $cname, $type, $currency, $email_required, $base64Image, $image, $container, $min_tier, $default_fixed_fee, $default_variable_fee);
        $this->driver = $driver;
        $this->container = $container;
        $this->minimum = $minimum;
    }

    public function getPayInInfo($account_id, $amount)
    {
        $paymentInfo = $this->driver->request();

        $paymentInfo['amount'] = $amount;
        $paymentInfo['currency'] = $this->getCurrency();
        $paymentInfo['scale'] = Currency::$SCALE[$this->getCurrency()];
        $paymentInfo['status'] = Transaction::$STATUS_CREATED;
        $paymentInfo['final'] = false;

        return $paymentInfo;

    }

    public function getMinimumAmount(){
        return $this->minimum;
    }

    public function send($paymentInfo)
    {
        if($paymentInfo['status'] == 'sending'){
            $paymentInfo['status'] = Transaction::$STATUS_SENT;
            $paymentInfo['final'] = true;

        }else{
            $paymentInfo['status'] = 'sending';
        }

        return $paymentInfo;

    }

    public function getPayInStatus($paymentInfo)
    {
        if($paymentInfo['status'] == Transaction::$STATUS_RECEIVED){
            $paymentInfo['status'] = Transaction::$STATUS_SUCCESS;
            $paymentInfo['final'] = true;
        }

        return $paymentInfo;

    }

    public function getPayOutStatus($id)
    {
        // TODO: Implement getPayOutStatus() method.
    }

    public function getPayOutInfo($request){
        $paramNames = array(
            'beneficiary',
            'iban',
            'amount',
            'bic_swift'
        );

        $params = array();

        foreach($paramNames as $param){
            if(!$request->request->has($param)) throw new HttpException(404, 'Parameter '.$param.' not found');
            $params[$param] = $request->request->get($param);

        }
        $iban_verification = $this->driver->validateiban($params['iban']);
        $bic_verification = $this->driver->validatebic($params['bic_swift']);

        //if(!$iban_verification) throw new Exception('Invalid iban.',400);
        if(!$bic_verification) throw new Exception('Invalid bic.',400);

        if($request->request->has('concept')){
            $concept = $request->request->get('concept');
        }else{
            $concept = 'Sepa transaction';
        }

        $params['concept'] = $concept;

        $params['find_token'] = $find_token = substr(Random::generateToken(), 0, 6);
        $params['currency'] = $this->getCurrency();
        $params['scale'] = Currency::$SCALE[$this->getCurrency()];
        $params['final'] = false;
        $params['status'] = false;
        $params['gestioned'] = false;

        return $params;
    }

    public function getPayOutInfoData($data){
        $paramNames = array(
            'beneficiary',
            'iban',
            'amount',
            'bic_swift'
        );

        $params = array();

        foreach($paramNames as $param){
            if(!array_key_exists($param, $data)) throw new HttpException(404, 'Parameter '.$param.' not found');
            $params[$param] = $data[$param];
        }
        $iban_verification = $this->driver->validateiban($params['iban']);
        $bic_verification = $this->driver->validatebic($params['bic_swift']);

        //if(!$iban_verification) throw new Exception('Invalid iban.',400);
        if(!$bic_verification) throw new Exception('Invalid bic.',400);

        if(array_key_exists('concept', $data)){
            $concept = $data['concept'];
        }else{
            $concept = 'Sepa transaction';
        }

        $params['concept'] = $concept;

        $params['find_token'] = $find_token = substr(Random::generateToken(), 0, 6);
        $params['currency'] = $this->getCurrency();
        $params['scale'] = Currency::$SCALE[$this->getCurrency()];
        $params['final'] = false;
        $params['status'] = false;
        $params['gestioned'] = false;

        return $params;
    }

    public function sendMail(Transaction $transaction, $options = array()){

        $no_reply = $this->container->getParameter('no_reply_email');

        $message = \Swift_Message::newInstance()
            ->setSubject('Sepa_out ALERT')
            ->setFrom($no_reply)
            ->setTo(array(
                'administration@chip-chap.com',
                'pere@chip-chap.com',
                'ceo@chip-chap.com'
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:sepa_out_alert.html.twig',array(
                        'id'    =>  $transaction->getId(),
                        'type'  =>  $transaction->getType(),
                        'payment_infos'   =>  $transaction->getPayOutInfo(),
                        'transaction'   =>  $transaction,
                        'options'   =>  $options
                    ))
            )
            ->setContentType('text/html');

        $this->getContainer()->get('mailer')->send($message);
    }

    public function getInfo(){
        return $this->driver->getInfo();
    }

    /**
     * @return Boolean
     */
    public function checkKYC(Request $request, $type){

        if($type == 'out'){
            return $request;
        }

        $em = $this->getContainer()->get('doctrine')->getManager();
        if($request->request->has('token')) {
            $access_token = $request->request->get('token');
            $now = time();
            $token_info = $em->getRepository('TelepayFinancialApiBundle:AccessToken')->findOneBy(array(
                'token' => $access_token
            ));
            if($token_info && $token_info->getExpiresAt() > $now) {
                $user = $token_info->getUser();
                $email = $user->getEmail();
                $request->request->remove('token');
                $request->request->set('email', $email);
                $bool = true;
            }
            else{
                throw new HttpException(400, "Access token expired");
            }
        }
        else{
            $email = $request->request->get('email');
            $pass = $request->request->get('password');
            $factory = $this->getContainer()->get('security.password_encoder');
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
                'email' => $email
            ));
            if(!$user){
                throw new HttpException(400, "Email is not registred");
            }
            $encoder = $factory->getEncoder($user);
            $bool = ($encoder->isPasswordValid($user->getPassword(), $pass, $user->getSalt())) ? true : false;
        }

        if(!$bool){
            throw new HttpException(400, "Email or Password not correct");
        }

        $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user
        ));

        if(!$kyc){
            throw new Exception('User without kyc information',400);
        }

        if(!$kyc->getEmailValidated()){
            throw new Exception('Email must be validated.',400);
        }

        if(empty($kyc->getDocument())){
            throw new Exception('ID card must be updated',400);
        }

        return $request;
    }

}