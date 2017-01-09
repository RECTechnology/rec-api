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
use Telepay\FinancialApiBundle\Entity\KYCCompanyValidations;
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
        $this->logger->info('POST-UPDATE Kyc_Listener');

        $entityManager = $args->getEntityManager();
        $uow = $entityManager->getUnitOfWork();

        if ($entity instanceof Accesstoken) {
            $user = $entity->getUser();
            $user->setLastLogin(new \DateTime);
            $entityManager->persist($user);
            $entityManager->flush();
            return;
        }

    }

    public function preUpdate(LifecycleEventArgs $args){
        $entity = $args->getEntity();

        if ($entity instanceof KYC) {
            $this->_notifyKYCChanges($args, $entity);
            return;
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $this->logger->info('POST-INSERT');

        $entityManager = $args->getEntityManager();

        if ($entity instanceof Group) {
            $entity->setTier(0);
            return;
        }


    }

    private function _notifyKYCChanges($changeset, KYC $kyc){

        if($changeset->hasChangedField('tier1_status')){
            $this->logger->info('TIER 1 STATUS :'.$changeset->getNewValue('tier1_status'));
            switch ($changeset->getNewValue('tier1_status')){
                case 'approved':
                    //DO something
                    //subir de tier a todas las companies
                    $this->_uploadTierCompanies($kyc, 1, $changeset->getEntityManager());
                    $this->logger->info('TIER 1 : uploadTierCompanies');
                    break;
                case 'denied':
                    $this->_sendEmail('Update KYC denied', $kyc->getUser()->getEmail(), '', $kyc, 0, 'denied' );
                    $this->logger->info('TIER 1 : send email to user: '.$kyc->getUser()->getEmail());
                    break;
                case 'pending':
                    //notify admins
                    $this->logger->info('TIER 1 : notify pending request');
                    $this->_sendEmail('Update KYC required', 'kyc@robotunion.org', '', $kyc, 1 , 'pending');

            }
        }

        if($changeset->hasChangedField('tier2_status')){
            $this->logger->info('TIER 2 STATUS :'.$changeset->hasChangedField('tier1_status'));
            switch ($changeset->getNewValue('tier2_status')){
                case 'approved':
                    //DO something
                    //subir de tier a todas las companies
                    $this->_uploadTierCompanies($kyc, 2, $changeset->getEntityManager());
                    break;
                case 'denied':
                    $this->_sendEmail('Update KYC denied', $kyc->getUser()->getEmail(), '', $kyc, 1, 'denied' );
                    break;
                case 'pending':
                    //TODO notify admins
                    $this->_sendEmail('Update KYC required', 'kyc@robotunion.org', '', $kyc, 2 , 'pending');
            }
        }

    }

    private function _uploadTierCompanies(KYC $kyc, $tier, $em){

        //search all comanies with this kyc_manager
        $companies = $em->getRepository('TelepayFinancialApiBundle:Group')->findBy(array(
            'kyc_manager'   =>  $kyc
        ));

        foreach($companies as $company){
            $company->setTier($tier);
            $em->flush();
        }

        //notify to this kyc manager all companies updated
        $this->_sendEmail('Update KYC accepted', $kyc->getUser()->getEmail(), $companies, $kyc, $tier, 'accepted' );
        //notify admin all companies updated
        $this->_sendEmail('Update KYC accepted', 'kyc@robotunion.org', $companies, $kyc, $tier , 'accepted');
    }

    private function _sendEmail($subject, $to, $companies, $kyc, $tier, $action){
        $from = 'no-reply@chip-chap.com';
        $mailer = 'mailer';

        $template = 'TelepayFinancialApiBundle:Email:KYCUpdate.html.twig';

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody(
                $this->container->get('templating')
                    ->render($template,
                        array(
                            'companies' =>  $companies,
                            'kyc'   =>  $kyc,
                            'tier'  =>  $tier,
                            'action'    =>  $action
                        )
                    )
            )
            ->setContentType('text/html');

        $this->container->get($mailer)->send($message);
    }

}