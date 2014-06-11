<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/10/14
 * Time: 2:52 PM
 */
namespace Arbaf\FinancialApiBundle\Security;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ApiKeyAuthenticator implements SimplePreAuthenticatorInterface {

    protected $userProvider;

    public function __construct(ApiKeyUserProvider $userProvider){
        $this->userProvider = $userProvider;
    }


    //build a token with the request
    public function createToken(Request $request, $providerKey) {

        $PARAMS_MAP=array(
            'GET' => $request->query->all(),
            'POST' => $request->request->all(),
            'PUT' => $request->request->all(),
            'DELETE' => $request->request->all(),
            'HEAD' => $request->request->all(),
        );

        $params = $PARAMS_MAP[$request->getMethod()];

        $paramsToCheck=array('access_key','timestamp','signature');
        foreach($paramsToCheck as $param){
            if(!array_key_exists($param, $params)){
                throw new BadCredentialsException("Param '$param' not found in the request");
            }
        }


        return new PreAuthenticatedToken(
            'anon.',
            $params,
            $providerKey
        );
    }


    public function supportsToken(TokenInterface $token, $providerKey) {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey) {
        $accessKey = $token->getCredentials()['access_key'];
        $username = $this->userProvider->getUsernameForAccessKey($accessKey);

        if(!$username){
            throw new AuthenticationException(
                sprintf('access_secret "%s" not found', $accessKey)
            );
        }

        $user = $this->userProvider->loadUserByUsername($username);

        return new PreAuthenticatedToken(
            $user,
            $accessKey,
            $providerKey,
            $user->getRoles()
        );
    }


}