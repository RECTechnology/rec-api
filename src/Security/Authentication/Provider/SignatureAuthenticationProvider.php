<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/26/14
 * Time: 1:43 PM
 */

namespace App\Security\Authentication\Provider;

use FOS\OAuthServerBundle\Tests\Functional\TestBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Security\Authentication\Token\SignatureToken;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\NonceExpiredException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SignatureAuthenticationProvider implements AuthenticationProviderInterface {

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
    public function authenticate(TokenInterface $token)
    {
        $user = $this->userProvider->loadUserByAccessKey($token->getUsername());


        if($user && $this->validateSignature($token->getUsername(), $token->nonce, $token->timestamp, $token->version, $token->signature, $user->getAccessSecret())){
            $authenticatedToken = new SignatureToken($user->getRoles());
            $authenticatedToken->setUser($user);
            if($user->isLocked()) throw new AuthenticationException('User is locked');
            if(!$user->isEnabled()) throw new AuthenticationException('User is disabled');
            return $authenticatedToken;
        }

        throw new AuthenticationException('Signature authentication failed.');
    }

    protected function validateSignature($accessKey, $nonce, $timestamp, $version, $signature, $secret){
        // Check created time is not in the future

        $algorithm = 'SHA256';

        if($version!='1')
            return false;

        if ($timestamp - 30 > time()) {
            return false;
        }

        // Expire timestamp after 5 minutes
        if (time() - $timestamp > 300) {
            return false;
        }

        // Validate that the nonce is *not* used in the last 5 minutes
        // if it has, this could be a replay attack
        if (file_exists($this->cacheDir.'/'.$nonce) && intval(file_get_contents($this->cacheDir.'/'.$nonce)) + 300 > time()) {
            throw new NonceExpiredException('Previously used nonce detected');
        }

        // If cache directory does not exist we create it
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
        file_put_contents($this->cacheDir.'/'.$nonce, time());

        // Validate Secret
        $expected = hash_hmac($algorithm, $accessKey.$nonce.$timestamp, base64_decode($secret));
        return $signature === $expected;
    }

    /**
     * Checks whether this provider supports the given token.
     *
     * @param TokenInterface $token A TokenInterface instance
     *
     * @return bool    true if the implementation supports the Token, false otherwise
     */
    public function supports(TokenInterface $token){
        return $token instanceof SignatureToken;
    }
}