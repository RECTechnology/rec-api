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
        'easypay_in',
        'sepa_in',
        'fac_in',
        'btc_in'
    );
    /**
     * @Rest\View
     */
    public function indexAction(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();
        $company = $user->getActiveGroup();

        $all = $this->getRepository()->findBy(array(
            'company'  =>  $company
        ));

        $total = count($all);

        foreach($all as $one){
            $method = $one->getMethod();
            $methodDriver = $this->get('net.telepay.in.'.$method.'.v1');

            if($method == 'easypay'){
                $info = $methodDriver->getInfo();
                $one->setAccountNumber($info['account_number']);
            }elseif($method == 'sepa'){
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

        if(!in_array($method, $company_methods)) throw new HttpException(405, 'Method not allowed in this company.');

        $paymentInfo = $methodDriver->getPayInInfo(0);

        if($method == 'easypay'){
            $token = 'IN-'.$paymentInfo['reference_code'];
        }elseif($method == 'sepa'){
            $token = 'IN-'.$paymentInfo['reference'];
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