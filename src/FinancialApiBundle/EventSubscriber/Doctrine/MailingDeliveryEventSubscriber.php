<?php


namespace App\FinancialApiBundle\EventSubscriber\Doctrine;


use App\FinancialApiBundle\Controller\CRUD\AccountsController;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Mailing;
use App\FinancialApiBundle\Entity\MailingDelivery;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\ORMException;
use Documents\Account;
use Swift_Attachment;
use Swift_Mailer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MailingDeliveryEventSubscriber implements EventSubscriber {

    /** @var Swift_Mailer */
    private $mailer;

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param Swift_Mailer $mailer
     * @param EngineInterface $templating
     * @param TranslatorInterface $translator
     * @param ContainerInterface $container
     */
    public function __construct(Swift_Mailer $mailer, EngineInterface $templating, TranslatorInterface $translator, ContainerInterface $container)
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
        if($entity instanceof Mailing){
            if($args->hasChangedField('status') && $args->getNewValue('status') == Mailing::STATUS_SCHEDULED){
                if($entity->getScheduledAt() == null){
                    throw new \LogicException("Cannot schedule Mailing without scheduled_at");
                }
            }
        }
        elseif($entity instanceof MailingDelivery)
            if ($args->hasChangedField('status') && $args->getNewValue('status') == MailingDelivery::STATUS_SCHEDULED) {
                /** @var Group $account */
                $account = $entity->getAccount();

                /** @var Mailing $mailing */
                $mailing = $entity->getMailing();

                $content = $this->templating->render(
                    'FinancialApiBundle:Email:rec_empty_email.html.twig',
                    [
                        'mail' => [
                            'subject' => $mailing->getSubject(),
                            'body' => $mailing->getContent(),
                            'lang' => 'es'
                        ],
                        'app' => [
                            'landing' => 'rec.barcelona'
                        ]
                    ]
                );

                $message = new \Swift_Message(
                    $mailing->getSubject(),
                    $content,
                    'text/html'
                );

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
                    $message->attach(Swift_Attachment::newInstance($content, $filename));
                }

                if($account->getEmail() == null || $account->getEmail() != '') {
                    $message->setTo($account->getEmail());
                    $message->setFrom('postmaster@sandbox45b8322153cb4d8898da8ad6e384be0b.mailgun.org');
                    $accepted = $this->mailer->send($message);
                    if($accepted > 0)
                        $entity->setStatus(MailingDelivery::STATUS_SENT);
                    else
                        $entity->setStatus(MailingDelivery::STATUS_ERRORED);
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