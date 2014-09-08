<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/26/14
 * Time: 12:55 PM
 */


namespace Telepay\FinancialApiBundle\Security\Firewall;


use Telepay\FinancialApiBundle\Security\Authentication\Token\SignatureToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class SignatureListener implements ListenerInterface {

    protected $securityContext;
    protected $authenticationManager;

    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager){

        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;

    }

    /**
     * @param GetResponseEvent $event
     * @throws \Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException
     */
    public function handle(GetResponseEvent $event)
    {

        $request = $event->getRequest();

        $authRequestRegex = '/Signature '
            .'access-key="([^"]+)", '
            .'nonce="([^"]+)", '
            .'timestamp="([^"]+)", '
            .'version="([^"]+)", '
            .'signature="([^"]+)"/';

        $authHeaderName = 'x-signature';

        if(! $request->headers->has($authHeaderName)) return;
        $signature = $request->headers->get($authHeaderName);
        if(1 != preg_match($authRequestRegex, $signature, $matches)) return;

        $token = new SignatureToken();
        $token->setUser($matches[1]);

        $token->nonce = $matches[2];
        $token->timestamp = $matches[3];
        $token->version = $matches[4];
        $token->signature = $matches[5];

        try{
            $authToken = $this->authenticationManager->authenticate($token);
            $this->securityContext->setToken($authToken);
            return;
        } catch(AuthenticationException $failed){
            //TODO: log something here
        }

        $response = new Response();
        $response->setStatusCode(Response::HTTP_FORBIDDEN);
        $event->setResponse($response);
    }
}