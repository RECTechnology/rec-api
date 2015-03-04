<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/23/15
 * Time: 6:51 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core;


use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\StringManipulator;

class TransactionContext implements TransactionContextInterface {

    private $requestStack;
    private $mode;
    private $user;
    private $odm;
    private $orm;
    function __construct($requestStack, $securityContext, $odm, $orm)
    {
        $this->requestStack = $requestStack;
        $this->user = $securityContext->getToken()->getUser();
        $this->odm = $odm;
        $this->orm = $orm;
        $sm = new StringManipulator();
        if($sm->endsWith('Test', $requestStack->getCurrentRequest()->attributes->get('_controller')))
            $this->mode = 'test';
        else $this->mode = 'real';
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


    public function getMode()
    {
        return $this->mode;
    }
}