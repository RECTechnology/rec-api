<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 4:42 AM
 */

namespace Telepay\FinancialApiBundle\Financial\Methods;

use MongoDBODMProxies\__CG__\Telepay\FinancialApiBundle\Document\Transaction;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseMethod;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashInInterface;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\CashOutInterface;
use Telepay\FinancialApiBundle\Financial\Currency;

class EchoMethod extends BaseMethod {

    private $driver;

    public function __construct($name, $cname, $type, $currency, $base64Image, $container, $driver){
        parent::__construct($name, $cname, $type, $currency, $base64Image, $container);
        $this->driver = $driver;
    }

    //PAY IN
    public function getPayInInfo($amount)
    {

        $response = array(
            'amount'    =>  $amount,
            'currency'  =>  $this->getCurrency(),
            'scale' =>  Currency::$SCALE[$this->getCurrency()],
            'expires_in' => intval(1200),
            'received' => 0.0,
            'status'    =>  'created',
            'final'     =>  false
        );

        return $response;
    }

    public function getPayInStatus($paymentInfo)
    {

        $amount = $paymentInfo['amount'];

        return $paymentInfo;

    }


    //PAY OUT
    public function getPayOutInfo($request)
    {
        $paramNames = array(
            'amount'
        );

        $params = array();

        foreach($paramNames as $param){
            if(!$request->request->has($param)) throw new HttpException(400, 'Parameter '.$param.' not found');
            $params[$param] = $request->request->get($param);

        }

        $params['currency'] = $this->getCurrency();
        $params['scale'] = Currency::$SCALE[$this->getCurrency()];
        $params['final'] = false;
        $params['status'] = false;


        return $params;
    }

    public function send($paymentInfo)
    {
        throw new HttpException('Method not implemented');
    }

    public function getPayOutStatus($id)
    {
        throw new HttpException('Method not implemented');
    }

    public function cancel($payment_info){
        throw new HttpException('Method not implemented');
    }


}