<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;
use Telepay\FinancialApiBundle\Entity\Balance;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\User;

class PermissionsHandler{
    private $doctrine;
    private $mongo;
    private $container;

    public function __construct($container, $doctrine, $mongo){
        $this->doctrine = $doctrine;
        $this->mongo = $mongo;
        $this->container = $container;
    }

    /**
     * User
     * Transaction amount (+/-)
     * Transaction
     */
    public function checkMethodPermissions(Transaction $transaction){

        $em = $this->doctrine->getManager();
        $company = $em->getRepository('TelepayFinancialApiBundle:Group')->find($transaction->getGroup());
        $tier = $company->getTier();
        $this->container->get('logger')->info('_checkMethodPermissions');
        $method = $this->container->get('net.telepay.'.$transaction->getType().'.'.$transaction->getMethod().'.v'.$transaction->getVersion());
        if($company->getGroupCreator()->getid() == $this->container->getParameter('default_company_creator_commerce_android_fair')){
            //is fairpay user
            if($method->getCname() != 'fac'){
                throw new HttpException(403, 'You don\'t have the necessary permissions. You are fairpay user');
            }
        }

        if($method->getMinTier() > $tier){
            throw new HttpException(403, 'You don\'t have the necessary permissions. You must to be Tier '.$method->getMinTier().' and your current Tier is '.$tier);
        }

        //check if method is available
        $statusMethod = $em->getRepository('TelepayFinancialApiBundle:StatusMethod')->findOneBy(array(
            'method'    =>  $transaction->getMethod(),
            'type'      =>  $transaction->getType()
        ));

        if($statusMethod->getStatus() != 'available') throw new HttpException(403, 'Method temporally unavailable.');

    }

}