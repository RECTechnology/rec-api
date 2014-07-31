<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/26/14
 * Time: 1:43 PM
 */

namespace Arbaf\FinancialApiBundle\Security\Authentication\Provider;

use Arbaf\FinancialApiBundle\Security\Authentication\Token\IPToken;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class IPAuthenticationProvider implements AuthenticationProviderInterface {

    private $userProvider;
    private $cacheDir;

    public function __construct(UserProviderInterface $userProvider, $cacheDir)
    {
        $this->userProvider = $userProvider;
        $this->cacheDir     = $cacheDir;
    }

    /**
     * Attempts to authenticate a TokenInterface object.
     *
     * @param TokenInterface $token The TokenInterface instance to authenticate
     *
     * @return TokenInterface An authenticated TokenInterface instance, never null
     *
     * @throws AuthenticationException if the authentication fails
     */
    public function authenticate(TokenInterface $token) {
        $user = $this->userProvider->loadUserByIP($token->getUsername());

        if($user){
            $authenticatedToken = new IPToken($user->getRoles());
            $authenticatedToken->setUser($user);
            //die("auth ok\n");
            return $authenticatedToken;
        }

            //die("auth failed\n");
        throw new AuthenticationException('IP authentication failed.');
    }

    /**
     * Checks whether this provider supports the given token.
     *
     * @param TokenInterface $token A TokenInterface instance
     *
     * @return bool    true if the implementation supports the Token, false otherwise
     */
    public function supports(TokenInterface $token){
        return $token instanceof IPToken;
    }
}