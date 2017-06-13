<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\Financial\Methods;

use FOS\OAuthServerBundle\Util\Random;
use MongoDBODMProxies\__CG__\Telepay\FinancialApiBundle\Document\Transaction;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseMethod;
use Telepay\FinancialApiBundle\Financial\Currency;
use Symfony\Component\HttpFoundation\Request;


class TeleingresoMethod extends BaseMethod{

    private $driver;

    public function __construct($name, $cname, $type, $currency, $email_required, $base64Image, $container, $driver, $min_tier){
        parent::__construct($name, $cname, $type, $currency, $email_required, $base64Image, $container, $min_tier);
        $this->driver = $driver;
    }

    public function send($paymentInfo)
    {
        // TODO: Implement getPayInInfo() method.
    }

    public function getPayInInfo($amount)
    {

        try{
            $teleingreso = $this->driver->createIssue($amount/100);

        }catch (HttpException $e){
            throw new Exception($e->getMessage(), $e->getStatusCode());
        }

        if($teleingreso['TxtCode'] == 0){
            $paymentInfo['status'] = 'created';
            $paymentInfo['amount'] = $teleingreso['amount']*100;
            $paymentInfo['teleingreso_status'] = $teleingreso['TxtDescription'];
            $paymentInfo['teleingreso_id'] = $teleingreso['transactionId'];
            $paymentInfo['charge_id'] = $teleingreso['chargeId'];
            $paymentInfo['track'] = $teleingreso['track'];
            $paymentInfo['expires_in'] = 7*24*60*60;
            $paymentInfo['currency'] = $teleingreso['currency'];
            $paymentInfo['scale'] = Currency::$SCALE[$teleingreso['currency']];
            $paymentInfo['final'] = false;
        }else{
            $paymentInfo['status'] = 'failed';
            $paymentInfo['final'] = false;
            $paymentInfo['errorCode'] = $teleingreso['errCode'];
            $paymentInfo['errorDescription'] = $teleingreso['errDescription'];
        }

        return $paymentInfo;
    }

    public function getPayOutInfo($request){
        throw new HttpException(405, 'Method not implemented');
    }

    public function getPayOutInfoData($data){
        throw new HttpException(405, 'Method not implemented');
    }

    public function getPayInStatus($paymentInfo)
    {
        if($paymentInfo['status'] == Transaction::$STATUS_RECEIVED){
            $paymentInfo['status'] = Transaction::$STATUS_SUCCESS;
        }

        return $paymentInfo;
    }

    public function getPayOutStatus($paymentInfo)
    {

        throw new HttpException(405, 'Method not implemented');
    }

    public function cancel($paymentInfo){
        throw new HttpException(405, 'Method not implemented');
    }

    public function notification($params, $paymentInfo){

        $response = $this->driver->notification($params);

        if($response['status'] == 1){
            $paymentInfo['status'] = Transaction::$STATUS_RECEIVED;
            $paymentInfo['response'] = $response['response'];
        }else{
            $paymentInfo['response'] = $response['response'];
        }

        return $paymentInfo;
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
}