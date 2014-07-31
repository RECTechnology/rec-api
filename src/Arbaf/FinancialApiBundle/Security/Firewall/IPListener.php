<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/26/14
 * Time: 12:55 PM
 */


namespace Arbaf\FinancialApiBundle\Security\Firewall;


use Arbaf\FinancialApiBundle\Security\Authentication\Token\IPToken;
use Arbaf\FinancialApiBundle\Security\Authentication\Token\SignatureToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class IPListener implements ListenerInterface {

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
    public function handle(GetResponseEvent $event) {
        $request = $event->getRequest();

        $ip = $request->getClientIp();

        $token = new IPToken();
        $token->setUser($ip);

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