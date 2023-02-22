<?php

namespace App\EventListener;

use FOS\OAuthServerBundle\Event\OAuthEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OAuthEventListener
{
    protected $container;
    protected $logger;

    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    public function onPreAuthorizationProcess(OAuthEvent $event)
    {
        $this->logger->info('PreAuthorization');
        if ($user = $this->getUser($event)) {
            $event->setAuthorizedClient(
                $user->isAuthorizedClient($event->getClient())
            );
        }
    }

    public function onPostAuthorizationProcess(OAuthEvent $event)
    {
        $this->logger->info('PostAuthorization');
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