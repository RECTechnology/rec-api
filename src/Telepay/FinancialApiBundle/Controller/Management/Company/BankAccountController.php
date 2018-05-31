<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Exception;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\BankAccount;
use Telepay\FinancialApiBundle\Controller\BaseApiController;

class BankAccountController extends BaseApiController{

    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:BankAccount";
    }

    function getNewEntity()
    {
        return new BankAccount();
    }

    /**
     * @Rest\View
     */
    public function registerAccount(Request $request){
        $user = $this->get('security.context')->getToken()->getUser();
        $group = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        $paramNames = array(
            'owner',
            'iban'
        );

        $params = array();
        foreach($paramNames as $paramName){
            if($request->request->has($paramName)){
                $params[$paramName] = $request->request->get($paramName);
            }else{
                throw new HttpException(404, 'Param '.$paramName.' not found');
            }
        }
        $em = $this->getDoctrine()->getManager();
        $bank = new BankAccount();
        $bank->setCompany($group);
        $bank->setUser($user);
        $bank->setOwner($params['owner']);
        $bank->setIban($params['iban']);
        $em->persist($bank);
        $em->flush();
        return $this->restV2(201,"ok", "Bank account registered successfully", $bank);
    }

    /**
     * @Rest\View
     */
    public function indexAccounts(Request $request){
        $user = $this->getUser();
        $company = $user->getActiveGroup();
        $em = $this->getDoctrine()->getManager();
        $accounts = $em->getRepository('TelepayFinancialApiBundle:BankAccount')->findBy(array(
            'company'   =>  $company,
            'user'   =>  $user
        ));
        return $this->restV2(200, 'ok', 'Request successfull', $accounts);
    }

    /**
     * @Rest\View
     */
    public function updateAccountFromCompany(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $account = $em->getRepository('TelepayFinancialApiBundle:BankAccount')->find($id);

        if($account->getCompany()->getId() != $this->getUser()->getActiveGroup()->getId() )
            throw new HttpException(403, 'You don\'t have the necessary permissions');

        if(!$account) throw new HttpException(404, 'Bank account not found');

        if($request->request->has('alias')){
            $account->setAlias($request->request->get('alias'));
        }
        $em->persist($account);
        $em->flush();
        return $this->restV2(204, 'ok', 'Bank account updated successfully');
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $account = $em->getRepository('TelepayFinancialApiBundle:BankAccount')->findOneBy(array(
            'id'    =>  $id,
            'company' =>  $user->getActiveGroup()
        ));

        if(!$account) throw new HttpException(404, 'Bank Account not found');

        return parent::deleteAction($id);
    }
}