<?php

namespace App\FinancialApiBundle\EventSubscriber\Doctrine;

use App\FinancialApiBundle\Controller\Transactions\IncomingController2;
use App\FinancialApiBundle\DependencyInjection\App\Commons\DiscourseApiManager;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\AccountAward;
use App\FinancialApiBundle\Entity\Award;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Mailing;
use App\FinancialApiBundle\Entity\MailingDelivery;
use App\FinancialApiBundle\Entity\PaymentOrder;
use App\FinancialApiBundle\Entity\Pos;
use App\FinancialApiBundle\Entity\User;
use App\FinancialApiBundle\Exception\AppException;
use App\FinancialApiBundle\Exception\AttemptToChangeStatusException;
use App\FinancialApiBundle\Financial\Driver\FakeEasyBitcoinDriver;
use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class AwardAccountEventSubscriber
 * @package App\FinancialApiBundle\EventSubscriber\Doctrine
 */
class AwardAccountEventSubscriber implements EventSubscriber {

    /** @var ContainerInterface $container */
    private $container;

    /**
     * MailingDeliveryEventSubscriber constructor.
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
            Events::preUpdate,
        ];
    }

    public function preUpdate(PreUpdateEventArgs $args){
        $accountAward = $args->getEntity();
        $em = $args->getEntityManager();
        if($accountAward instanceof AccountAward){
            if ($args->hasChangedField("score")){
                $score = $accountAward->getScore();

                /** @var Award $award */
                $award = $accountAward->getAward();
                $ranges = $award->getThresholds();

                $currentLevel = $accountAward->getLevel();

                if(isset($ranges[$currentLevel + 1])){
                    if($score >= $ranges[$currentLevel + 1]){
                        //increase level
                        $accountAward->setLevel($currentLevel +1);
                        //send an email
                        $this->sendEmail($accountAward);
                    }
                }
            }
        }
    }

    private function sendEmail(AccountAward $accountAward){

        $no_replay = $this->container->getParameter('no_reply_email');
        $body = 'La cuenta '.$accountAward->getAccount()->getId().' - '.$accountAward->getAccount()->getName().
            ' ha subido de nivel '.($accountAward->getLevel() - 1).' a nivel '.$accountAward->getLevel().
            ' en la categoria '.$accountAward->getAward()->getNameEs();

        $message = \Swift_Message::newInstance()
            ->setSubject("New Level Raised in Conecta")
            ->setFrom($no_replay)
            ->setTo(array(
                "julia.ponti@novact.org",
                "sofia@novact.org",
            ))
            ->setBody(
                $this->container->get('templating')
                    ->render('FinancialApiBundle:Email:empty_email.html.twig',
                        array(
                            'mail' => [
                                'subject' => "New level raised in Conecta",
                                'body' => $body,
                                'lang' => 'es'
                            ]
                        )
                    )
            )
            ->setContentType('text/html');

        $this->container->get('mailer')->send($message);
    }


}