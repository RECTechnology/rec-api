<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\TreasureWithdrawal;
use App\FinancialApiBundle\Entity\TreasureWithdrawalValidation;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Swift_Mailer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Templating\EngineInterface;

/**
 * Class TreasureWithdrawalValidationSubscriber
 * @package App\FinancialApiBundle\EventSubscriber\Doctrine
 */
class TreasureWithdrawalValidationSubscriber implements EventSubscriber {

    /** @var Swift_Mailer */
    private $mailer;

    /** @var EngineInterface */
    private $templating;

    /** @var ContainerInterface */
    private $container;


    /**
     * TreasureWithdrawalSubscriber constructor.
     * @param Swift_Mailer $mailer
     * @param EngineInterface $templating
     * @param ContainerInterface $container
     */
    public function __construct(Swift_Mailer $mailer, EngineInterface $templating, ContainerInterface $container)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->container = $container;
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
            $message = new \Swift_Message(
                "Treasure Withdrawal Confirmation",
                $this->templating->render(
                    'FinancialApiBundle:Email:central_withdrawal.html.twig',
                    ['link' => $link ]
                ),
                'text/html'
            );
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
                $this->container
                    ->get('app.incoming_controller')
                    ->createTransaction(
                        $params,
                        1,
                        'in',
                        'rec',
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