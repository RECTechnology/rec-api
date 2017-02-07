<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 9/11/16
 * Time: 12:33
 */
// src/AppBundle/EventListener/SearchIndexer.php
namespace Telepay\FinancialApiBundle\EventListener;

use Blockchain\Exception\HttpError;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
            $this->logger->info('POST-UPDATE Kyc_Listener access_token');
            $user->setLastLogin(new \DateTime);
            $entityManager->persist($user);
            $entityManager->flush();
            return;
        }

        if ($entity instanceof KYC) {
            $changeset = $uow->getEntityChangeSet($entity);
            $this->_notifyKYCChanges($changeset, $entity);
            return;
        }

        if ($entity instanceof Group) {
            $changeset = $uow->getEntityChangeSet($entity);
            if(isset($changeset['kyc_manager'])){
                //update this group with this tier
                $em = $this->container->get('doctrine')->getManager();
                $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
                    'user'   =>  $entity->getKycManager()
                ));
                if($kyc->getTier1Status() == 'approved' && $entity->getTier() < 1){
                    $entity->setTier(1);
                    //notify update tier
                    $this->_sendEmail('Update KYC ', $entity->getKycManager()->getEmail(), $entity, $entity->getKycManager(), 1, 'approved_single' );
                }

                if($kyc->getTier2Status() == 'approved' && $entity->getTier() < 2){
                    $entity->setTier(2);
                    //notify update tier
                    $this->_sendEmail('Update KYC ', $entity->getKycManager()->getEmail(), $entity, $entity->getKycManager(), 2, 'approved_single' );
                }

                $em->flush();
            }
            return;
        }

    }

    public function preUpdate(LifecycleEventArgs $args){

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

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $this->logger->info('PRE-INSERT');

        $entityManager = $args->getEntityManager();

        if ($entity instanceof AccessToken) {

            //checkear que la company del client esta activa si no fuera
            if($entity->getClient()->getGroup()->getActive() == false){
                throw new HttpException(403, 'This company is disabled, please contact support.');
            }
            //si la company esta activa y grant_type = password -> si la company del user no esta activa fuera si esta activa pa dentro
            $user = $entity->getUser();

            if($user && !$user->isKYC()){
                $this->logger->info('user id : '.$user->getId().' '.$user->getRoles()[0]);
                $this->logger->info('user roles : ' . $user->showKYC());
                //if user is authenticated with password
                $activeCompany = $user->getActiveGroup();
                $this->logger->info('pre-insert check locked company');
                if(!$activeCompany->getActive()){
                    $companies = $user->getGroups();
                    $changed = 0;
                    foreach ($companies as $company){
                        if($company->getId() != $activeCompany->getId() && $company->getActive()){
                            $user->setActiveGroup($company);
                            $entityManager->flush();
                            $changed = 1;
                            break;
                        }
                    }
                    if($changed == 0) throw new HttpException(403, 'This company is disabled, please contact support.');
                }

            }

            return;
        }

    }

    private function _notifyKYCChanges($changeset, KYC $kyc){

        if(isset($changeset['tier1_status'])){
            $this->logger->info('TIER 1 STATUS :'.$changeset['tier1_status']);
            switch ($kyc->getTier1Status()){
                case 'approved':
                    //DO something
                    //subir de tier a todas las companies
                    $this->_uploadTierCompanies($kyc, 1);
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

        if(isset($changeset['tier2_status'])){
            $this->logger->info('TIER 2 STATUS :'.$changeset['tier2_status']);
            switch ($kyc->getTier2Status()){
                case 'approved':
                    //DO something
                    //subir de tier a todas las companies
                    $this->_uploadTierCompanies($kyc, 2);
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

    private function _uploadTierCompanies(KYC $kyc, $tier){

        //search all comanies with this kyc_manager
        $em = $this->container->get('doctrine')->getManager();
        $companies = $em->getRepository('TelepayFinancialApiBundle:Group')->findBy(array(
            'kyc_manager'   =>  $kyc->getUser()
        ));

        $this->logger->info('TIER 1 : update '.count($companies).' companies');
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