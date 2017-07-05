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

        $user = $this->getUser();
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
        if($total == 0){
            $all[] = "default";
        }
        $bank_info = array();
        foreach($all as $one){
            if($one == "default"){
                $methode = $method;
            }
            else {
                $methode = $one->getMethod();
            }
            $meth = explode('-', $methode);

            $methodDriver = $this->get('net.telepay.in.'.$meth[0].'.v1');

            $info = $methodDriver->getInfo();
            if($methode == 'easypay-in'){
                if($one != "default") {
                    $one->setAccountNumber($info['account_number']);
                }
                $bank_info = array($info);
            }elseif($methode == 'sepa-in'){
                if($user->getActiveGroup()->getPremium()){
                    $info = array(
                        'iban'  =>  'ES15 1491 0001 2320 1444 7722',
                        'beneficiary'   =>  ' XARXA INTEGRAL DE PROFESSIONALS I USUARIES',
                        'bic_swift' =>  'TRIOESMMXXX'
                    );
                }
                $botc_id = $this->container->getParameter('default_company_creator_commerce_botc');
                if(($company->getGroupCreator()->getId() == $botc_id) || ($company->getId() == $botc_id)){
                    $info = array(
                        'iban'  =>  'ES43 1491 0001 2420 8685 5729',
                        'beneficiary'   =>  'Xarxa Autogestió Social SCCL',
                        'bic_swift' =>  'TRIOESMMXXX',
                        'message' => 'Send sepa transfer to this account'
                    );
                }
                if($one != "default") {
                    $one->setAccountNumber($info['iban']);
                    $one->setBeneficiary($info['beneficiary']);
                    $one->setBicSwift($info['bic_swift']);
                }
                $bank_info = array($info);
            }

        }

        if($all[0]=="default") $all = array();
        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'elements' => $all,
                'bank_info' =>  $bank_info
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
//        $company_methods = $company->getMethodsList();

//        if(!in_array($method.'-'.$type, $company_methods)) throw new HttpException(405, 'Method not allowed in this company.');

        if($methodDriver->getMinTier() > $company->getTier()) {
            throw new HttpException(403, 'You don\'t have the necessary permissions. You must to be Tier ' . $method->getMinTier() . ' and your current Tier is ' . $company->getTier());
        }

        $tokens = $this->getRepository('TelepayFinancialApiBundle:CashInTokens')->findBy(array(
            'company'  =>  $company->getId(),
            'method'    =>  $request->request->get('method'),
            'status'    =>  CashInTokens::$STATUS_ACTIVE
        ));

        if($method != 'sepa'){
            if(count($tokens) >= 5) throw new HttpException(403, 'You has exceeded the max addresses allowed');
        }else{
            if(count($tokens) > 1) throw new HttpException(403, 'You has exceeded the max addresses allowed');
        }

        $paymentInfo = $methodDriver->getPayInInfo(0);

        if($method == 'easypay'){
            $token = 'deposit '.$paymentInfo['reference_code'];
        }elseif($method == 'sepa'){
            $ref = str_replace('BUY BITCOIN ', '', $paymentInfo['reference']);
            $token = 'deposit '.$ref;
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

        $user = $this->get('security.context')->getToken()->getUser();

        $company = $user->getActiveGroup();

        $cashIn = $this->getRepository()->findOneBy(array(
            'id'    =>  $id,
            'company' =>  $company
        ));

        if(!$cashIn) throw new HttpException(404, 'Cash In Token not found');

        if($request->request->has('method') || $request->request->has('currency') || $request->request->has('status') || $request->request->has('token'))
            throw new HttpException(403, 'The request has not allowed params');

        if($request->request->has('disable')){
            $disable = $request->request->get('disable');

            if($disable){
                $request->request->add(array(
                    'status'    =>  CashInTokens::$STATUS_CLOSED
                ));
            }

            $request->request->remove('disable');
        }

        return parent::updateAction($request, $id);

    }

    /**
     * @Rest\View
     */
    public function reactiveToken(Request $request){

        $user = $this->get('security.context')->getToken()->getUser();

        $company = $user->getActiveGroup();

        if($request->request->has('token')){
            $token = $request->request->get('token');
        } else{
            throw new HttpException(404, 'Param token not found');
        }

        $cashIn = $this->getRepository()->findOneBy(array(
            'token'    =>  $token,
            'company' =>  $company
        ));

        if(!$cashIn) throw new HttpException(404, 'Cash In Token not found');

        $all = $this->getRepository()->findBy(array(
            'company'   =>  $company,
            'method'    =>  $cashIn->getMethod(),
            'status'    =>  CashInTokens::$STATUS_ACTIVE
        ));

        if(count($all) >= 5) throw new HttpException(409, 'You has exceeded the number of tokens actives for this method');

        $em = $this->getDoctrine()->getManager();
        $cashIn->setStatus(CashInTokens::$STATUS_ACTIVE);
        $cashIn->setUpdated(new \DateTime());

        $em->persist($cashIn);
        $em->flush();

        return $this->rest(204, 'Token activated successfully');

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