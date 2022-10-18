<?php

namespace App\FinancialApiBundle\EventSubscriber\Kernel;

use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Challenge;
use App\FinancialApiBundle\Entity\Group;
use Doctrine\ODM\MongoDB\DocumentManager;
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

    /** @var DocumentManager $dm */
    private $dm;

    private $container;

    /** @var TokenStorageInterface $storage */
    private $storage;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container, TokenStorageInterface $storage){
        $this->em = $em;
        $this->container = $container;
        $this->dm = $container->get('doctrine_mongodb')->getManager();
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

            $hidrated_elements = [];
            foreach ($elements as $element){
                //TODO find transactions between dates
                $transactions = $this->dm->getRepository(Transaction::class)->findTransactions(
                    $account,
                    $element['start_date'],
                    $element['finish_date'],
                    '',
                    'created',
                    'DESC'
                );

                $total_tx = 0;
                $total_amount = 0;

                /** @var Transaction $tx */
                foreach ($transactions as $tx){
                    if($tx->getType() === Transaction::$TYPE_OUT){
                        $pay_out_info = $tx->getPayOutInfo();
                        /** @var Group $receiver */
                        $receiver = $this->em->getRepository(Group::class)->findOneBy(array(
                           'rec_address' => $pay_out_info['address']
                        ));
                        if($element['action'] === Challenge::ACTION_TYPE_BUY){
                            if($receiver->getType() === Group::ACCOUNT_TYPE_ORGANIZATION){
                                $total_amount += $tx->getAmount();
                                ++$total_tx;
                            }
                        }elseif ($element['action'] === Challenge::ACTION_TYPE_SEND){
                            if($receiver->getType() === Group::ACCOUNT_TYPE_PRIVATE){
                                $total_amount += $tx->getAmount();
                                ++$total_tx;
                            }
                        }

                    }

                }
                $statistics = array(
                    'total_tx' => $total_tx,
                    'total_amount' => $total_amount
                );

                $element['statistics'] = $statistics;
                $hidrated_elements[] = $element;
            }

            $content['data']['elements'] = $hidrated_elements;
            $response->setContent(json_encode($content));
        }
    }


}