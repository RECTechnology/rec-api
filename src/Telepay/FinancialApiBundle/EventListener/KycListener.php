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
use Telepay\FinancialApiBundle\Entity\KYC;
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
        $uow = $entityManager->getUnitOfWork();

        // only act on some "User" entity
        if ($entity instanceof User) {
            $changeset = $uow->getEntityChangeSet($entity);
            $this->checkUserKYC($changeset, $entity);
        }

        if ($entity instanceof KYC) {
            $changeset = $uow->getEntityChangeSet($entity);
            $this->checkKYC($changeset, $entity);
        }

        if ($entity instanceof Group) {
            $changeset = $uow->getEntityChangeSet($entity);
            $this->checkCompanyKYC($changeset, $entity);
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

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $this->logger->error('POST-INSERT');

        $entityManager = $args->getEntityManager();

        if ($entity instanceof Group) {
            $entity->setTier(0);
            return;
        }


    }

    private function checkKYC($changeset, KYC $kyc)
    {
        if (isset($changeset['phone']) || isset($changeset['lastname']) || isset($changeset['dateBirth'])) {
            $this->logger->info('POST-UPDATE - changing susceptible fields in kyc');
            //TODO send change email
            $body = array(
                'message'   =>  'El Usuario '.$kyc->getUser()->getUsername().' ha cambiado algunos parametros susceptibles del kyc del usuario en la tabla kyc'
            );
            $to = array(
                'pere@chip-chap.com',
                'cto@chip-chap.com'
            );
            $action = 'user_kyc';
            $this->_sendEmail('KYC Alert change', $body, $to, $action);
        }

        return;
    }

    private function checkUserKYC($changeset, User $user)
    {
        if (isset($changeset['email']) || isset($changeset['name'])) {
            $this->logger->info('POST-UPDATE - changing susceptible fields in user');
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

        return;
    }

    private function checkCompanyKYC($changeset, Group $company)
    {
        if ($company->getEmail()) {
            $this->logger->info('POST-UPDATE - changing susceptible fields in company');
            //TODO send email
            return;
        }

        // ...
    }

    private function _sendEmail($subject, $body, $to, $action){
        $from = 'no-reply@chip-chap.com';
        $mailer = 'mailer';

        if($action == 'user_kyc'){
            $template = 'TelepayFinancialApiBundle:Email:KYCUpdate.html.twig';
        }elseif($action == 'company_kyc'){}else{
            $template = 'TelepayFinancialApiBundle:Email:KYCUpdate.html.twig';
        }

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody(
                $this->container->get('templating')
                    ->render($template,
                        $body
                    )
            )
            ->setContentType('text/html');

        $this->container->get($mailer)->send($message);
    }


}