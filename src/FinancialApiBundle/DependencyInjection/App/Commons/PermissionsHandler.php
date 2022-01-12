<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use App\FinancialApiBundle\Exception\AppLogicException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Balance;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\User;

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
        $company = $em->getRepository('FinancialApiBundle:Group')->find($transaction->getGroup());
        $tier = $company->getTier();
        $this->container->get('logger')->info('_checkMethodPermissions');
        $method = $this->container->get('net.app.'.$transaction->getType().'.'.$transaction->getMethod().'.v1');

        if($method->getMinTier() > $tier){
            throw new HttpException(403, 'You don\'t have the necessary permissions. You must to be Tier '.$method->getMinTier().' and your current Tier is '.$tier);
        }

        //check if method is available
        $statusMethod = $em->getRepository('FinancialApiBundle:StatusMethod')->findOneBy(array(
            'method'    =>  $transaction->getMethod(),
            'type'      =>  $transaction->getType()
        ));

        if(!$statusMethod) throw new \LogicException("Attempted to call an undefined method '{$transaction->getMethod()}'");

        if($statusMethod->getStatus() != 'available') throw new HttpException(403, 'Method temporally unavailable.');

    }

}