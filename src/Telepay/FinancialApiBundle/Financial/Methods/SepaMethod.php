<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace Telepay\FinancialApiBundle\Financial\Methods;

use FOS\OAuthServerBundle\Util\Random;
use MongoDBODMProxies\__CG__\Telepay\FinancialApiBundle\Document\Transaction;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseMethod;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashInInterface;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashOutInterface;
use Telepay\FinancialApiBundle\Financial\Currency;

class SepaMethod extends BaseMethod {

    private $driver;

    public function __construct($name, $cname, $type, $currency, $base64Image, $container, $driver){
        parent::__construct($name, $cname, $type, $currency, $base64Image, $container);
        $this->driver = $driver;
    }

    public function getPayInInfo($amount)
    {
        $paymentInfo = $this->driver->request();

        $paymentInfo['amount'] = $amount;
        $paymentInfo['currency'] = $this->getCurrency();
        $paymentInfo['scale'] = Currency::$SCALE[$this->getCurrency()];
        $paymentInfo['status'] = Transaction::$STATUS_CREATED;
        $paymentInfo['final'] = false;

        return $paymentInfo;

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

    public function getPayOutInfo($request)
    {
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

        if(!$iban_verification) throw new Exception('Invalid iban.',400);
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

        return $params;
    }

    public function sendMail($id, $type, $paymentInfo){

        $message = \Swift_Message::newInstance()
            ->setSubject('Sepa_out ALERT')
            ->setFrom('no-reply@chip-chap.com')
            ->setTo(array(
                'cto@chip-chap.com',
                'pere@chip-chap.com'
            ))
            ->setBody(
                $this->getContainer()->get('templating')
                    ->render('TelepayFinancialApiBundle:Email:sepa_out_alert.html.twig',array(
                        'id'    =>  $id,
                        'type'  =>  $type,
                        'beneficiary'   =>  $paymentInfo['beneficiary'],
                        'iban'  =>  $paymentInfo['iban'],
                        'amount'    =>  $paymentInfo['amount'],
                        'bic_swift' =>  $paymentInfo['bic_swift'],
                        'concept'   =>  $paymentInfo['concept'],
                        'currency'  =>  $paymentInfo['currency'],
                        'scale'     =>  $paymentInfo['scale'],
                        'final'     =>  $paymentInfo['final'],
                        'status'    =>  $paymentInfo['status']
                    ))
            );

        $this->getContainer()->get('mailer')->send($message);
    }

}