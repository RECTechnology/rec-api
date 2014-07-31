<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/26/14
 * Time: 12:42 PM
 */

namespace Arbaf\FinancialApiBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;


class IPToken extends AbstractToken {

    public $ip;

    public function __construct(array $roles = array()){
        parent::__construct($roles);
        $this->setAuthenticated(count($roles) > 0);
    }

    /**
     * Returns the user credentials.
     *
     * @return mixed The user credentials
     */
    public function getCredentials()
    {
        return '';
    }
}