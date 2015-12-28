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

class SepaMethod extends BaseMethod {

    private $driver;

    public function __construct($name, $cname, $type, $currency, $base64Image, $container, $driver){
        parent::__construct($name, $cname, $type, $currency, $base64Image, $container);
        $this->driver = $driver;
    }

    public function getPayInInfo($amount)
    {

    }

    public function send($paymentInfo)
    {
        $paymentInfo['status'] = 'pending';

        return $paymentInfo;

    }

    public function getPayInStatus($paymentInfo)
    {


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

        if(!$iban_verification) throw new HttpException(400,'Invalid iban.');
        if(!$bic_verification) throw new HttpException(400,'Invalid bic.');

        $params['currency'] = $this->getCurrency();
        $params['scale'] = Currency::$SCALE[$this->getCurrency()];
        $params['final'] = false;
        $params['status'] = false;


        return $params;
    }
}