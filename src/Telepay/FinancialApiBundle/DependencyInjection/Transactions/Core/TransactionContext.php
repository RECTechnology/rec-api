<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/23/15
 * Time: 6:51 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core;

class TransactionContext implements TransactionContextInterface {

    private $requestStack;
    private $user;
    private $odm;
    private $orm;
    function __construct($requestStack, $securityContext, $odm, $orm)
    {
        $this->requestStack = $requestStack;
        //die(print_r($this->requestStack->getCurrentRequest()->get('id'),true));
        if($this->requestStack->getCurrentRequest()->get('_route') === 'service_notificate'){
            $transaction_id = $this->requestStack->getCurrentRequest()->get('id');
            $transaction = $odm->getRepository('TelepayFinancialApiBundle:Transaction')->find($transaction_id);
            //die(print_r($transaction->getUser(),true));
            $this->user = $orm->getRepository('TelepayFinancialApiBundle:User')->find($transaction->getUser());
        }else{
            $this->user = $securityContext->getToken()->getUser();
        }
        $this->odm = $odm;
        $this->orm = $orm;
    }

    /**
     * @return mixed
     */
    public function getRequestStack()
    {
        return $this->requestStack;
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
        return $this->odm;
    }

    /**
     * @return mixed
     */
    public function getORM()
    {
        return $this->orm;
    }

}