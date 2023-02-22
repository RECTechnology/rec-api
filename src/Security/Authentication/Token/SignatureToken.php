<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/26/14
 * Time: 12:42 PM
 */

namespace App\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;


class SignatureToken extends AbstractToken {

    public $nonce;
    public $timestamp;
    public $version;
    public $signature;

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