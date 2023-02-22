<?php

namespace App\EventSubscriber\Doctrine;

use App\Entity\Document;
use App\Entity\LemonDocument;
use App\Entity\LemonDocumentKind;
use App\Exception\AppException;
use App\Exception\NoSuchTranslationException;
use App\Financial\Driver\LemonWayInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LemonUploadEventSubscriber
 * @package App\EventSubscriber\Doctrine
 */
class LemonUploadEventSubscriber implements EventSubscriber {

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
        $document = $args->getEntity();
        if($document instanceof Document){
            $kind = $document->getKind();
            if($document instanceof LemonDocument) {
                if ($document->getLemonReference() == null) {

                    /** @var LemonWayInterface $lemon */
                    $lemon = $this->container->get('net.app.driver.lemonway.eur');

                    $resp = $lemon->callService(
                        'UploadFile',
                        [
                            'wallet' => $document->getAccount()->getCif(),
                            'fileName' => sprintf("doctype_%d.jpg", $kind->getLemonDoctype()),
                            'type' => sprintf("%d", $kind->getLemonDoctype()),
                            'buffer' => base64_encode(file_get_contents($document->getContent()))
                        ]
                    );

                    if(is_array($resp))
                        throw new AppException(
                            400,
                            "LW error",
                            [
                                'property' => 'lemonway_error - UPLOADFILE - ' . $resp['UPLOADFILE']['ERROR'],
                                'message' => $resp['UPLOADFILE']['MESSAGE']
                            ]
                        );
                    if($resp->E != null)
                        throw new AppException(400, "LW error: {$resp->E}");

                    if($resp->UPLOAD->ID == null)
                        throw new AppException(503, "Bad LW response: " . json_encode($resp));
                    $document->setLemonReference($resp->UPLOAD->ID);
                }
            }
            elseif ($kind instanceof LemonDocumentKind) {
                throw new AppException(400, "Cannot assign a LemonDocumentKind to Document.kind, use lemon_documents instead");
            }
        }
    }
}