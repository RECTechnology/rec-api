<?php


namespace App\FinancialApiBundle\EventSubscriber\Doctrine;


use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\Mailing;
use App\FinancialApiBundle\Entity\MailingDelivery;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Html2Pdf;
use Swift_Attachment;
use Swift_Mailer;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Templating\EngineInterface;

class MailingDeliveryEventSubscriber implements EventSubscriber {

    /** @var Swift_Mailer */
    private $mailer;

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * MailingDeliveryEventSubscriber constructor.
     * @param Swift_Mailer $mailer
     * @param EngineInterface $templating
     */
    public function __construct(Swift_Mailer $mailer, EngineInterface $templating)
    {
        $this->mailer = $mailer;
        $this->templating = $templating;
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
        if($entity instanceof MailingDelivery)
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


                $message->attach(Swift_Attachment::newInstance(
                    $pdf->output(null, 'S'),
                    'rec_clients_and_providers_aug2019.pdf',
                    'application/pdf'
                ));

                //$message->setTo($account->getEmail());
                $message->setTo('lluis@qbitartifacts.com');
                $message->setFrom('postmaster@sandbox45b8322153cb4d8898da8ad6e384be0b.mailgun.org');

                $this->mailer->send($message);

                $entity->setStatus(MailingDelivery::STATUS_SENT);
                $args->getEntityManager()->persist($entity);
            }
    }

}