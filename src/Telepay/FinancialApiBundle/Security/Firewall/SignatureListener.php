<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 6/26/14
 * Time: 12:55 PM
 */


namespace Telepay\FinancialApiBundle\Security\Firewall;


use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Telepay\FinancialApiBundle\Security\Authentication\Token\SignatureToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Telepay\FinancialApiBundle\Entity\User;


class SignatureListener implements ListenerInterface {

    protected $tokenStorage;
    protected $authenticationManager;
    protected $container;

    public function __construct(TokenStorageInterface $tokenStorage, AuthenticationManagerInterface $authenticationManager, ContainerInterface $container){
        $this->tokenStorage = $tokenStorage;
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

        if(!$request->headers->has($authHeaderName)){
            $logger->info('SIGNATURE_LISTENER error1-> NO ' . $authHeaderName . " HEADER");
            return;
        }
        $signature = $request->headers->get($authHeaderName);
        $logger->info('SIGNATURE_LISTENER signature->' . $signature);
        if(1!=preg_match($authRequestRegex, $signature, $matches)){
            $logger->info('SIGNATURE_LISTENER error1-> NO REGEX FORMAT');
            return;
        }

        if($request->getMethod() == 'GET' && strlen($matches[1])==10){
            $logger->info('SIGNATURE_LISTENER IS GET');
            $em = $this->container->get('doctrine')->getManager();
            $usersRepo = $em->getRepository("TelepayFinancialApiBundle:User");
            $key_data = explode("A", $matches[1]);
            $id = $key_data[0];
            $logger->info('SIGNATURE_LISTENER id->' . $id);
            $user = $usersRepo->findOneBy(array('id'=>$id));
            if(!$this->validateSignature($matches[1], $matches[2], $matches[3], $matches[4], $matches[5], substr($user->getAccessSecret(), 0, 10)))throw new AuthenticationException('Bad signature');
            $matches[1] = (string)$user->getAccessKey();

            try{
                $logger->info('SIGNATURE_LISTENER AUTH ID');
                $authToken = new SignatureToken($user->getRoles());
                $authToken->setUser($user);
                if($user->isLocked()) throw new AuthenticationException('User is locked');
                if(!$user->isEnabled()) throw new AuthenticationException('User is disabled');
                $logger->info('SIGNATURE_LISTENER token ' . $authToken);
                $this->tokenStorage->setToken($authToken);
                $logger->info('SIGNATURE_LISTENER OK');
                return;
            } catch(AuthenticationException $failed){
                $logger->info('SIGNATURE_LISTENER AUTH ID EXC');
            }
        }

        $logger->info('SIGNATURE_LISTENER NOT GET VERSION2');

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
            $this->tokenStorage->setToken($authToken);
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

    protected function validateSignature($accessKey, $nonce, $timestamp, $version, $signature, $secret){
        $logger = $this->container->get('logger');
        // Check created time is not in the future
        $algorithm = 'SHA256';
        if($version!='2') {
            $logger->info('SIGNATURE_LISTENER_V Version');
            return false;
        }

        if ($timestamp - 30 > time()) {
            $logger->info('SIGNATURE_LISTENER_V Timestamp');
            return false;
        }

        // Expire timestamp after 5 minutes
        if (time() - $timestamp > 300) {
            $logger->info('SIGNATURE_LISTENER_V Expired');
            return false;
        }

        // Validate Secret
        $expected = hash_hmac($algorithm, $accessKey.$nonce.$timestamp, base64_decode($secret));
        $logger->info('SIGNATURE_LISTENER_V Expected ' . $expected);
        $logger->info('SIGNATURE_LISTENER_V Siganture ' . $signature);
        return $signature === $expected;
    }
}