<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/26/14
 * Time: 12:55 PM
 */


namespace Telepay\FinancialApiBundle\Security\Firewall;


use Symfony\Component\DependencyInjection\ContainerInterface;
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
    protected $container;

    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, ContainerInterface $container){
        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->container = $container;
    }

    /**
     * @param GetResponseEvent $event
     * @throws \Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException
     */
    public function handle(GetResponseEvent $event)
    {

        $logger = $this->container->get('logger');

        $request = $event->getRequest();
        $logger->info($request);
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

        $logger->info('FUCK5 1->'.$matches[1]);
        $logger->info('FUCK5 2->'.$matches[2]);
        $logger->info('FUCK5 3->'.$matches[3]);
        $logger->info('FUCK5 4->'.$matches[4]);
        $logger->info('FUCK5 5->'.$matches[5]);

        try{
            $authToken = $this->authenticationManager->authenticate($token);
            $this->securityContext->setToken($authToken);
            $logger->info('FUCK5 OK');
            return;
        } catch(AuthenticationException $failed){
            $logger->info('FUCK5 AUTH EXC');
            //TODO: log something here
            //return;
        }

        $response = new Response();
        $logger->info('FUCK5 RESP');
        $response->setStatusCode(Response::HTTP_FORBIDDEN);
        $logger->info('FUCK5 RESP CODE');
        $event->setResponse($response);
        $logger->info('FUCK5 END');
    }
}