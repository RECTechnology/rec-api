<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\Annotations\HybridPropery;
use App\FinancialApiBundle\Annotations\TranslatedProperty;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Document;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\HybridPersistent;
use App\FinancialApiBundle\Entity\Iban;
use App\FinancialApiBundle\Entity\LemonDocument;
use App\FinancialApiBundle\Entity\LemonDocumentKind;
use App\FinancialApiBundle\Entity\Stateful;
use App\FinancialApiBundle\Entity\Translatable;
use App\FinancialApiBundle\Exception\AppException;
use App\FinancialApiBundle\Exception\AppLogicException;
use App\FinancialApiBundle\Exception\NoSuchTranslationException;
use App\FinancialApiBundle\Financial\Driver\LemonWayInterface;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class LemonUploadEventSubscriber
 * @package App\FinancialApiBundle\EventSubscriber\Doctrine
 */
class LemonIbanEventSubscriber implements EventSubscriber {

    /** @var ContainerInterface $container */
    private $container;

    /**
     * LemonUploadEventSubscriber constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents() {
        return [
            Events::prePersist,
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     * @throws NoSuchTranslationException
     */
    public function prePersist(LifecycleEventArgs $args){
        $iban = $args->getEntity();
        if($iban instanceof Iban){

            /** @var LemonWayInterface $lemon */
            $lemon = $this->container->get('net.app.driver.lemonway.eur');

            /** @var Group $owner */
            $owner = $iban->getAccount();

            $resp = $lemon->callService(
                'RegisterIBAN',
                [
                    'wallet' => $owner->getCif(),
                    'holder' => $iban->getHolder(),
                    'bic' => $iban->getBic(),
                    'dom1' => $iban->getBankName(),
                    'dom2' => $iban->getBankAddress()
                ]
            );

            if(is_array($resp))
                throw new AppException(
                    400,
                    "LW error",
                    [
                        'property' => 'lemonway_error - REGISTERIBAN - ' . $resp['REGISTERIBAN']['ERROR'],
                        'message' => $resp['REGISTERIBAN']['MESSAGE']
                    ]
                );
            if($resp->E != null)
                throw new AppException(400, "LW error: {$resp->E}");

            if($resp->UPLOAD->ID == null)
                throw new AppException(503, "Bad LW response: " . json_encode($resp));
            $iban->setLemonReference($resp->UPLOAD->ID);
        }
    }
}