<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace Telepay\FinancialApiBundle\Financial\Methods;

use MongoDBODMProxies\__CG__\Telepay\FinancialApiBundle\Document\Transaction;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseMethod;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashInInterface;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashOutInterface;
use Telepay\FinancialApiBundle\Financial\Currency;
use Symfony\Component\HttpFoundation\Request;

class EasyPayMethod extends BaseMethod {

    private $driver;

    public function __construct($name, $cname, $type, $currency, $email_required, $base64Image, $container, $driver, $min_tier){
        parent::__construct($name, $cname, $type, $currency, $email_required, $base64Image, $container, $min_tier);
        $this->driver = $driver;
    }

    public function getPayInInfo($amount){
        $paymentInfo = $this->driver->request();

        $paymentInfo['amount'] = $amount;
        $paymentInfo['currency'] = $this->getCurrency();
        $paymentInfo['scale'] = Currency::$SCALE[$this->getCurrency()];
        $paymentInfo['status'] = Transaction::$STATUS_CREATED;
        $paymentInfo['final'] = false;

        return $paymentInfo;
    }

    public function send($paymentInfo){
        $paymentInfo['status'] = 'sending';

        //TODO send email with the payment information

        return $paymentInfo;
    }

    public function getPayInStatus($paymentInfo){
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

    public function getPayOutInfo($request)
    {

    }

    public function getPayOutInfoData($data)
    {

    }

    /**
     * @return Boolean
     */
    public function checkKYC(Request $request, $type){
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
            $factory = $this->getContainer()->get('security.encoder_factory');
            $user = $em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
                'email' => $email
            ));
            if(!$user){
                throw new HttpException(400, "Email is not registred");
            }
            $encoder = $factory->getEncoder($user);
            $bool = ($encoder->isPasswordValid($user->getPassword(), $pass, $user->getSalt())) ? true : false;
            $request->request->remove('password');
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

        if(!$kyc->getPhoneValidated()){
            throw new Exception('Number phone must be validated.',400);
        }

        return $request;
    }

    public function getInfo(){
        return $this->driver->getInfo();
    }
}