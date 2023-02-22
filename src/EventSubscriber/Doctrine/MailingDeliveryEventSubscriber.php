<?php


namespace App\EventSubscriber\Doctrine;


use App\Controller\CRUD\AccountsController;
use App\Entity\Group;
use App\Entity\Mailing;
use App\Entity\MailingDelivery;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\ORMException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MailingDeliveryEventSubscriber implements EventSubscriber {

    /** @var MailerInterface */
    private $mailer;

    /** @var EngineInterface */
    private $templating;

    /** @var ContainerInterface */
    private $container;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param MailerInterface $mailer
     * @param EngineInterface $templating
     * @param TranslatorInterface $translator
     * @param ContainerInterface $container
     */
    public function __construct(MailerInterface $mailer, EngineInterface $templating, TranslatorInterface $translator, ContainerInterface $container)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->container = $container;
        $this->translator = $translator;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents() {
        return [Events::preUpdate];
    }

    public function preUpdate(PreUpdateEventArgs $args){

        $entity = $args->getEntity();
        if($entity instanceof MailingDelivery){
            $changedStatus = $args->hasChangedField('status');
            if ($changedStatus && $args->getNewValue('status') == MailingDelivery::STATUS_SCHEDULED) {
                /** @var Group $account */
                $account = $entity->getAccount();

                /** @var EntityManagerInterface $em */
                $em = $this->container->get('doctrine.orm.entity_manager');

                /** @var Mailing $mailing */
                $mailing = $entity->getMailing();
                $locale = $account->getKycManager()->getLocale();
                if($locale) {
                    $mailing->setLocale($locale);
                    $em->refresh($mailing);
                    $this->translator->setLocale($locale);
                }
                $content = $this->templating->render(
                    'Email/rec_empty_email.html.twig',
                    [
                        'mail' => [
                            'subject' => $mailing->getSubject(),
                            'body' => $mailing->getContent(),
                            'lang' => $locale
                        ],
                        'app' => [
                            'landing' => 'rec.barcelona'
                        ]
                    ]
                );

                $message = new Email();
                $message->subject($mailing->getSubject());
                $message->html($content);

                foreach ($mailing->getAttachments() as $filename => $attachment){
                    switch ($attachment){
                        case "b2b_report":
                            $ac = new AccountsController();
                            $ac->setContainer($this->container);
                            $content = $ac->generateClientsAndProvidersReportPdf($this->templating, $account);
                            break;
                        default:
                            $content = $attachment;
                            break;
                    }
                    $message->attach($content, $filename);
                }

                if($account->getEmail() != null || $account->getEmail() != '') {
                    $message->to($account->getEmail());
                    $message->from($this->container->getParameter('no_reply_email'));

                    $entity->setMessageRef($message->generateMessageId());
                    try {
                        $this->mailer->send($message);
                        $entity->setStatus(MailingDelivery::STATUS_SENT);
                    } catch (TransportException $e) {
                        $entity->setStatus(MailingDelivery::STATUS_ERRORED);
                    }
                }
                else{
                    $entity->setStatus(MailingDelivery::STATUS_ERRORED);
                }

                try {
                    $args->getEntityManager()->persist($entity);
                    $args->getEntityManager()->flush();
                } catch (ORMException $e) {
                }

            }
        }
    }

}