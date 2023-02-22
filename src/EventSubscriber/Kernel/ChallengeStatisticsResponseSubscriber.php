<?php

namespace App\EventSubscriber\Kernel;

use App\DependencyInjection\Commons\ChallengeHandler;
use App\Entity\AccountChallenge;
use App\Entity\Challenge;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ChallengeStatisticsResponseSubscriber implements EventSubscriberInterface
{
    /** @var EntityManagerInterface $em */
    private $em;

    private $container;

    /** @var TokenStorageInterface $storage */
    private $storage;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container, TokenStorageInterface $storage){
        $this->em = $em;
        $this->container = $container;
        $this->storage = $storage;
    }
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        // TODO: Implement getSubscribedEvents() method.
        return [
            KernelEvents::RESPONSE => [
                ['onResponse', 10]
            ]
        ];
    }

    public function onResponse(FilterResponseEvent $event){
        $logger = $this->container->get('challenge.logger');
        if(!$event->isMasterRequest()){
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        if($request->getPathInfo() === '/user/v3/challenges' && $request->getMethod() === 'GET' && $response->getStatusCode() === 200){
            $user = $this->storage->getToken()->getUser();
            $account = $user->getActiveGroup();
            $content = json_decode($response->getContent(), true);
            $elements = $content['data']['elements'];

            $logger->info("CHALLENGE_STATISTICS_RESPONSE -> start hidrate");
            $hidrated_elements = [];
            foreach ($elements as $element){
                //check if challenge is achieved, if achieved ignore
                $challenge = $this->em->getRepository(Challenge::class)->find($element['id']);
                $account_challenge = $this->em->getRepository(AccountChallenge::class)->findOneBy(array(
                    'account' => $account,
                    'challenge' => $challenge
                ));

                if(!$account_challenge){
                    $logger->info("CHALLENGE_STATISTICS_RESPONSE -> account NO has challenge");
                    /** @var ChallengeHandler $challenge_handler */
                    $challenge_handler = $this->container->get('net.app.commons.challenge_handler');
                    //we need to convert the challenge array in object to pass it to chalenge handler

                    [$totalAmount, $totalTransactions] = $challenge_handler->getChallengeTotals($account, $challenge);

                    $statistics = array(
                        'total_tx' => $totalTransactions,
                        'total_amount' => $totalAmount
                    );

                    $element['statistics'] = $statistics;
                    $hidrated_elements[] = $element;
                }
            }
            $content['data']['elements'] = $hidrated_elements;
            $response->setContent(json_encode($content));
        }
    }

}