<?php

namespace App\FinancialApiBundle\EventListener;

use FOS\OAuthServerBundle\Event\OAuthEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OAuthEventListener
{
    protected $container;
    protected $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $this->container->get('logger');
    }

    public function onPreAuthorizationProcess(OAuthEvent $event)
    {
//        die(print_r('caca',true));
        $logger = $this->container->get('logger');
        $logger->info('PreAuthorization');
        if ($user = $this->getUser($event)) {
            $event->setAuthorizedClient(
                $user->isAuthorizedClient($event->getClient())
            );
        }
    }

    public function onPostAuthorizationProcess(OAuthEvent $event)
    {
        $logger = $this->container->get('logger');
        $logger->info('PostAuthorization');
        if ($event->isAuthorizedClient()) {
            if (null !== $client = $event->getClient()) {
                $user = $this->getUser($event);
                $user->addClient($client);
                $user->save();
            }
        }
    }

    protected function getUser(OAuthEvent $event)
    {
        return UserQuery::create()
            ->filterByUsername($event->getUser()->getUsername())
            ->findOne();
    }
}