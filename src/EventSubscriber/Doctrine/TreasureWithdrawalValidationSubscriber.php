<?php

namespace App\EventSubscriber\Doctrine;

use App\Entity\Group;
use App\Entity\TreasureWithdrawal;
use App\Entity\TreasureWithdrawalValidation;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Templating\EngineInterface;
use DateTimeZone;

/**
 * Class TreasureWithdrawalValidationSubscriber
 * @package App\EventSubscriber\Doctrine
 */
class TreasureWithdrawalValidationSubscriber implements EventSubscriber {

    /** @var MailerInterface */
    private $mailer;

    /** @var EngineInterface */
    private $templating;

    /** @var ContainerInterface */
    private $container;

    /** @var TokenStorageInterface */
    private $tokenStorage;


    /**
     * TreasureWithdrawalSubscriber constructor.
     * @param MailerInterface $mailer
     * @param EngineInterface $templating
     * @param ContainerInterface $container
     */
    public function __construct(MailerInterface $mailer, EngineInterface $templating, ContainerInterface $container, TokenStorageInterface $tokenStorage)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->container = $container;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents() {
        return [
            Events::postPersist,
            Events::postUpdate
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args) {
        $validation = $args->getEntity();

        if($validation instanceof TreasureWithdrawalValidation){
            $panelUrl = $this->container->getParameter('base_panel_url');
            $link = $panelUrl . "/validate_withdrawal/" . $validation->getId() . "?token=" . $validation->getToken();
            $amount = $validation->getWithdrawal()->getAmount();
            $date = $validation->getWithdrawal()->getCreated();
            $date = $date->setTimezone(new DateTimeZone('Europe/Madrid'));
            $name = $this->tokenStorage->getToken()->getUsername();
            $message = new Email();
            $message->subject("Treasure Withdrawal Confirmation");
            $message->html(
                $this->templating->render(
                    'Email/central_withdrawal.html.twig',
                    ['link' => $link, 'name' => $name, 'amount' => $amount / 1e8, 'day' => $date->format("d"),
                        'month' => $date->format("m"), 'year' => $date->format("Y"), 'hour' => $date->format("H"),
                        'minutes' => $date->format("i"), 'seconds' => $date->format("s") ]
                )
            );

            $message->addFrom($this->container->getParameter('no_reply_email'));
            $message->addTo($validation->getEmail());
            $this->mailer->send($message);
            $validation->setStatus(TreasureWithdrawalValidation::STATUS_SENT);
            $args->getObjectManager()->flush();
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args) {
        $validation = $args->getEntity();

        if($validation instanceof TreasureWithdrawalValidation){
            /** @var TreasureWithdrawal $withdrawal */
            $withdrawal = $validation->getWithdrawal();
            if($validation->isApproved() && $withdrawal->isApproved()){
                $em = $args->getObjectManager();

                $id_group_root = $this->container->getParameter('id_group_root');
                $destination = $em
                    ->getRepository(Group::class)
                    ->find($id_group_root);
                $id_user_root = $destination->getKycManager()->getId();

                $params = [
                    'amount' => $withdrawal->getAmount(),
                    'concept' => "Treasure withdrawal",
                    'address' => $destination->getRecAddress(),
                    'sender' => '0'
                ];
                $currency = $this->container->getParameter("crypto_currency");
                $method_cname = strtolower($currency);
                $this->container
                    ->get('app.incoming_controller')
                    ->createTransaction(
                        $params,
                        1,
                        'in',
                        $method_cname,
                        $id_user_root,
                        $destination,
                        '127.0.0.1'
                    );

                $withdrawal->setStatus(TreasureWithdrawal::STATUS_APPROVED);
                $args->getObjectManager()->flush();
            }
        }
    }

}