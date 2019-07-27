<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/23/15
 * Time: 6:51 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\Transactions\Core;

use Symfony\Component\DependencyInjection\ContainerInterface;

class TransactionContext implements TransactionContextInterface {

    private $container;

    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        if($this->getRequestStack()->getCurrentRequest()->get('_route') === 'service_notificate'){
            $transaction_id = $this->getRequestStack()->getCurrentRequest()->get('id');
            $transaction = $this->getODM()->getRepository('FinancialApiBundle:Transaction')->find($transaction_id);
            $this->user = $this->getORM()->getRepository('FinancialApiBundle:User')->find($transaction->getUser());
        }else{
            $this->user = $this->container->get('security.token_storage')->getToken()->getUser();
        }
    }

    /**
     * @return mixed
     */
    public function getRequestStack()
    {
        return $this->container->get('request_stack');
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return mixed
     */
    public function getODM()
    {
        return $this->container->get('doctrine_mongodb');
    }

    /**
     * @return mixed
     */
    public function getORM()
    {
        return $this->container->get('doctrine');
    }

    public function getEnvironment()
    {
        return $this->container->get('kernel')->getEnvironment();
    }

    public function getContainer()
    {
        return $this->container;
    }
}