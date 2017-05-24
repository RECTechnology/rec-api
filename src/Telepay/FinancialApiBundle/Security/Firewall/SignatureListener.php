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
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Telepay\FinancialApiBundle\Entity\User;


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
    public function handle(GetResponseEvent $event){

        $logger = $this->container->get('logger');
        $request = $event->getRequest();
        $logger->info('SIGNATURE_LISTENER r->' . $request);
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

        if($request->getMethod() == 'GET' && strlen($matches[1])==10){
            $logger->info('SIGNATURE_LISTENER IS GET');
            $em = $this->container->get('doctrine')->getManager();
            $usersRepo = $em->getRepository("TelepayFinancialApiBundle:User");
            $key_data = explode("A", $matches[1]);
            $id = $key_data[0];
            $logger->info('SIGNATURE_LISTENER id->' . $id);
            $user = $usersRepo->findOneBy(array('id'=>$id));
            $matches[1] = (string)$user->getAccessKey();

            try{
                $logger->info('SIGNATURE_LISTENER AUTH ID');
                $authToken = $this->authenticationManager->authenticateGET($user);
                $logger->info('SIGNATURE_LISTENER token ' . $authToken);
                $this->securityContext->setToken($authToken);
                $logger->info('SIGNATURE_LISTENER OK');
                return;
            } catch(AuthenticationException $failed){
                $logger->info('SIGNATURE_LISTENER AUTH ID EXC');
            }
        }

        $token = new SignatureToken();
        $token->setUser($matches[1]);
        $token->nonce = $matches[2];
        $token->timestamp = $matches[3];
        $token->version = $matches[4];
        $token->signature = $matches[5];

        $logger->info('SIGNATURE_LISTENER 1->'.$matches[1]);
        $logger->info('SIGNATURE_LISTENER 2->'.$matches[2]);
        $logger->info('SIGNATURE_LISTENER 3->'.$matches[3]);
        $logger->info('SIGNATURE_LISTENER 4->'.$matches[4]);
        $logger->info('SIGNATURE_LISTENER 5->'.$matches[5]);

        try{
            $logger->info('SIGNATURE_LISTENER try');
            $authToken = $this->authenticationManager->authenticate($token);
            $logger->info('SIGNATURE_LISTENER token ' . $authToken);
            $this->securityContext->setToken($authToken);
            $logger->info('SIGNATURE_LISTENER OK');
            return;
        } catch(AuthenticationException $failed){
            $logger->info('SIGNATURE_LISTENER AUTH EXC');
            //TODO: log something here
            //return;
        }

        $response = new Response();
        $logger->info('SIGNATURE_LISTENER RESP');
        $response->setStatusCode(Response::HTTP_FORBIDDEN);
        $logger->info('SIGNATURE_LISTENER RESP CODE');
        $event->setResponse($response);
        $logger->info('SIGNATURE_LISTENER END');
    }
}