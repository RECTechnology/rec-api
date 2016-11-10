<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 9/11/16
 * Time: 12:33
 */
// src/AppBundle/EventListener/SearchIndexer.php
namespace Telepay\FinancialApiBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Telepay\FinancialApiBundle\Entity\AccessToken;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\User;

class KycListener
{
    protected $container;
    protected $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $this->container->get('logger');
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $this->logger->error('POST-UPDATE');

        $entityManager = $args->getEntityManager();

        // only act on some "Product" entity
        if ($entity instanceof User) {
            $this->checkUserKYC($entity);
        }

        if ($entity instanceof Group) {
            $this->checkCompanyKYC($entity);
        }

        // only act on some "Product" entity
        if ($entity instanceof Accesstoken) {
            $user = $entity->getUser();
            $user->setLastLogin(new \DateTime);
            $entityManager->persist($user);
            $entityManager->flush();
            return;
        }


    }

    public function preUpdate(LifecycleEventArgs $args){

    }

    private function checkUserKYC(User $user)
    {
        if ($user->getEmail() || $user->getName()) {
            $this->logger->info('PRE-UPDATE - changing susceptible fields');
            //TODO send change email
            $body = array(
                'message'   =>  'El Usuario '.$user->getUsername().' ha cambiado algunos parametros susceptibles del kyc del usuario'
            );
            $to = array(
                'pere@chip-chap.com',
                'cto@chip-chap.com'
            );
            $action = 'user_kyc';
            $this->_sendEmail('KYC Alert change', $body, $to, $action);
        }
        if ($user->getEmail()) {
            $this->logger->info('PRE-UPDATE - changing email');
            //TODO send change email
        }

        return;
    }

    private function checkCompanyKYC(User $company)
    {
        if ($company->getEmail()) {
            $this->logger->info('PRE-UPDATE - changing susceptible fields');
            //TODO send email
            return;
        }

        // ...
    }

    private function _sendEmail($subject, $body, $to, $action){
        $from = 'no-reply@chip-chap.com';
        $mailer = 'mailer';

        if($action == 'user_kyc'){
            $template = 'TelepayFinancialApiBundle:Email:registerconfirm.html.twig';
        }

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo(array(
                $to
            ))
            ->setBody(
                $this->container->get('templating')
                    ->render($template,
                        array(
                            'message'        =>  $body
                        )
                    )
            )
            ->setContentType('text/html');

        $this->container->get($mailer)->send($message);
    }


}