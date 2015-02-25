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

    function __construct($requestStack, $user, $odm, $orm)
    {
        $this->requestStack = $requestStack;
        $this->user = $user;
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