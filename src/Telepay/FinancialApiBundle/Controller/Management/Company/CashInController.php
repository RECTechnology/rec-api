<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\CashInTokens;

class CashInController extends BaseApiController{

    private $allowed_methods = array(
        'easypay-in',
        'sepa-in',
        'fac-in',
        'btc-in'
    );
    /**
     * @Rest\View
     */
    public function indexAction(Request $request, $method = null){

        $user = $this->get('security.context')->getToken()->getUser();
        $company = $user->getActiveGroup();

        if($method){
            $all = $this->getRepository()->findBy(array(
                'company'  =>  $company,
                'method'    =>  $method,
                'status'    =>  CashInTokens::$STATUS_ACTIVE
            ));
        }else{
            $all = $this->getRepository()->findBy(array(
                'company'  =>  $company,
                'status'    =>  CashInTokens::$STATUS_ACTIVE
            ));
        }


        $total = count($all);

        foreach($all as $one){
            $methode = $one->getMethod();
            $meth = explode('-', $methode);

            $methodDriver = $this->get('net.telepay.in.'.$meth[0].'.v1');

            if($methode == 'easypay'){
                $info = $methodDriver->getInfo();
                $one->setAccountNumber($info['account_number']);
            }elseif($methode == 'sepa'){
                $info = $methodDriver->getInfo();
                $one->setAccountNumber($info['iban']);
                $one->setBeneficiary($info['beneficiary']);
                $one->setBicSwift($info['bic_swift']);
            }

        }

        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'elements' => $all
            )
        );
    }

    /**
     * @Rest\View
     */
    public function showAction($id){
        return parent::showAction($id);
    }

    /**
     * @Rest\View
     */
    public function createAction(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();

        $company = $user->getActiveGroup();

        if(!$request->request->has('method')) throw new HttpException(404, 'Parameter method not found');
        if(!$request->request->has('label')) throw new HttpException(404, 'Parameter label not found');
        $methods = $request->request->get('method');
        $label = $request->request->get('label');

        $methods = explode('-', $methods);

        $method = $methods[0];
        $type = $methods[1];

        //check if the service is allowed
        if(!in_array($method.'-'.$type, $this->allowed_methods)) throw new HttpException(405, 'Method not allowed');
        $methodDriver = $this->get('net.telepay.in.'.$method.'.v1');

        //check if company has method available
        $company_methods = $company->getMethodsList();

        if(!in_array($method.'-'.$type, $company_methods)) throw new HttpException(405, 'Method not allowed in this company.');

        $tokens = $this->getRepository()->findBy(array(
            'company'  =>  $company,
            'method'    =>  $method,
            'status'    =>  CashInTokens::$STATUS_ACTIVE
        ));

        if(count($tokens) >= 5) throw new HttpException(409, 'You has exceeded the max addresses allowed');

        $paymentInfo = $methodDriver->getPayInInfo(0);

        if($method == 'easypay'){
            $token = 'IN-'.$paymentInfo['reference_code'];
        }elseif($method == 'sepa'){
            $ref = str_replace('BUY BITCOIN ', '', $paymentInfo['reference']);
            $token = 'IN-'.$ref;
        }else{
            $token = $paymentInfo['address'];
        }

        $request->request->add(array(
            'token' =>  $token,
            'currency'  =>  $paymentInfo['currency'],
            'expires_in'    =>  604800,
            'label' =>  $label,
            'status'    =>  CashInTokens::$STATUS_ACTIVE,
            'company'   =>  $company

        ));

        return parent::createAction($request);
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){

        //TODO active and disable token
        throw new HttpException(403, 'Method not implemented');

    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        return parent::deleteAction($id);

    }

    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:CashInTokens";
    }

    function getNewEntity()
    {
        return new CashInTokens();
    }

}