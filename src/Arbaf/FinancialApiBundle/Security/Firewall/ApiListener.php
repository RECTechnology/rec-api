<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/26/14
 * Time: 12:55 PM
 */


namespace Arbaf\FinancialApiBundle\Security\Firewall;


use Arbaf\FinancialApiBundle\Security\Authentication\Token\ApiToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class ApiListener implements ListenerInterface {

    protected $securityContext;
    protected $authenticationManager;

    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager){

        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;

    }

    /**
     * This interface must be implemented by firewall listeners.
     *
     * @param GetResponseEvent $event
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $authRequestRegex = '/Signature access-key="([^"]+)", nonce="([^"]+)", timestamp="([^"]+)", algorithm="([^"]+)", signature="([^"]+)"/';
        if(
            ! $request->headers->has('x-api-authorization')
            ||
            1 != preg_match($authRequestRegex, $request->headers->get('x-api-authorization'), $matches)
        ){
            return;
        }

        $token = new ApiToken();
        $token->setUser($matches[1]);

        $token->nonce = $matches[2];
        $token->timestamp = $matches[3];
        $token->algorithm = $matches[4];
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