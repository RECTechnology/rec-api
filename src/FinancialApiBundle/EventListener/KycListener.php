<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 9/11/16
 * Time: 12:33
 */
namespace App\FinancialApiBundle\EventListener;

use Blockchain\Exception\HttpError;
use Doctrine\ORM\Event\LifecycleEventArgs;
use OAuth2\OAuth2;
use OAuth2\OAuth2ServerException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\Google2FA;
use App\FinancialApiBundle\Entity\AccessToken;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\KYC;
use App\FinancialApiBundle\Entity\KYCCompanyValidations;
use App\FinancialApiBundle\Entity\User;

class KycListener {
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


        $entityManager = $args->getEntityManager();
        $uow = $entityManager->getUnitOfWork();

        if ($entity instanceof Accesstoken) {
            $this->logger->info('POST-UPDATE Kyc_Listener LOGIN');
            $user = $entity->getUser();
            $this->logger->info('POST-UPDATE Kyc_Listener access_token');
            $user->setLastLogin(new \DateTime);
            $entityManager->persist($user);
            $entityManager->flush();
            return;
        }

        if ($entity instanceof KYC) {
            $this->logger->info('POST-UPDATE Kyc_Listener CHANGES');
            $changeset = $uow->getEntityChangeSet($entity);
            $this->_notifyKYCChanges($changeset, $entity);
            return;
        }

        if ($entity instanceof Group) {
            $this->logger->info('POST-UPDATE Kyc_Listener TIER');
            $changeset = $uow->getEntityChangeSet($entity);
            if(isset($changeset['kyc_manager'])){
                //update this group with this tier
                $em = $this->container->get('doctrine')->getManager();
                $kyc = $em->getRepository('FinancialApiBundle:KYC')->findOneBy(array(
                    'user'   =>  $entity->getKycManager()
                ));
                if($kyc->getTier1Status() == 'approved' && $entity->getTier() < 1){
                    $entity->setTier(1);
                    //notify update tier
//                    $this->_sendEmail('Update KYC ', $entity->getKycManager()->getEmail(), $entity, $entity->getKycManager(), 1, 'approved_single' );
                }

                if($kyc->getTier2Status() == 'approved' && $entity->getTier() < 2){
                    $entity->setTier(2);
                    //notify update tier
//                    $this->_sendEmail('Update KYC ', $entity->getKycManager()->getEmail(), $entity, $entity->getKycManager(), 2, 'approved_single' );
                }

                $em->flush();
            }

            if(isset($changeset['tier'])){
                if($entity->getTier() > 0){
                    //$this->_sendEmail('Update KYC ', $entity->getKycManager()->getEmail(), $entity, $entity->getKycManager(), $entity->getTier(), 'approved_single' , $reseller);
                }
            }
            return;
        }

    }

    public function preUpdate(LifecycleEventArgs $args){
        $entity = $args->getEntity();

        $entityManager = $args->getEntityManager();
        $uow = $entityManager->getUnitOfWork();
        $whiteList = $this->container->getParameter('authorized_admins');

        if ($entity instanceof User) {
            $changes = $uow->getEntityChangeSet($entity);
            if(isset($changes['roles'])){
                $this->logger->info('PRE-UPDATE Kyc_Listener user CHANGING ROLES for '.$entity->getId().'-'.$entity->getUsername());
                $newRoles = $changes['roles'][1];
                if(in_array('ROLE_SUPER_ADMIN', $newRoles)){
                    if(!in_array($entity->getId(), $whiteList)){
                        $this->logger->info('PRE-UPDATE Kyc_Listener user CHANGING ROLES to SUPERADMIN for '.$entity->getId().'-'.$entity->getUsername());
                        throw new HttpException(403, 'Error');
                    }
                }
            }
            return;
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        $entityManager = $args->getEntityManager();

        if ($entity instanceof Group) {
            $entity->setTier(0);
            return;
        }
    }

    public function prePersist(LifecycleEventArgs $args){
        $entity = $args->getEntity();
        $this->logger->info('PRE-INSERT');

        $entityManager = $args->getEntityManager();

        if ($entity instanceof AccessToken) {

            $user = $entity->getUser();
            //checkear que la company del client esta activa si no fuera
            if($entity->getClient()->getGroup()->getActive() == false){
                throw new HttpException(403, 'This company is disabled, please contact support.');
            }
            //si la company esta activa y grant_type = password -> si la company del user no esta activa fuera si esta activa pa dentro

            if($user && !$user->isKYC()){
                $this->logger->info('user id : '.$user->getId().' '.$user->getRoles()[0]);
                $companies = $user->getGroups();
                //if user is authenticated with password
                $activeCompany = $user->getActiveGroup();
                if(!$activeCompany){
                    foreach ($companies as $company){
                        $user->setActiveGroup($company);
                        $entityManager->flush();
                        break;
                    }

                }
                $this->logger->info('pre-insert check locked company');
                if(!$activeCompany->getActive()){

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
/*
        $this->logger->info('_notifyKYCChanges');
        $user = $kyc->getUser();
        $active_group = $user->getActiveGroup();
        if(!$active_group){
            $em = $this->container->get('doctrine')->getManager();
            $reseller = $em->getRepository('FinancialApiBundle:Group')->find($this->container->getParameter('id_group_root'));
        }else{
            $this->logger->info('_notifyKYCChanges '.$active_group->getId());
            $reseller = $active_group->getGroupCreator();
            $this->logger->info('_notifyKYCChanges '.$reseller->getId());
        }

        if(isset($changeset['tier1_status'])){
            $this->logger->info('TIER 1 from :'.$changeset['tier1_status'][0].' to '.$changeset['tier1_status'][1]);
            switch ($kyc->getTier1Status()){
                case 'approved':
                    //DO something
                    //subir de tier a todas las companies
                    $this->_uploadTierCompanies($kyc, 1, $reseller);
                    $this->logger->info('TIER 1 : uploadTierCompanies');
                    break;
                case 'denied':
                    $this->_sendEmail('Update KYC denied', $kyc->getUser()->getEmail(), '', $kyc, 0, 'denied', $reseller );
                    $this->logger->info('TIER 1 : send email to user: '.$kyc->getUser()->getEmail());
                    break;
                case 'pending':
                    //notify admins
                    $this->logger->info('TIER 1 : notify pending request');
                    $this->_sendEmail('Update KYC required', 'kyc@robotunion.org', '', $kyc, 1 , 'pending', $reseller);

            }
        }

        if(isset($changeset['tier2_status'])){
            $this->logger->info('TIER 2 FROM :'.$changeset['tier2_status'][0].' TO '.$changeset['tier2_status'][0]);
            switch ($kyc->getTier2Status()){
                case 'approved':
                    //DO something
                    //subir de tier a todas las companies
                    $this->_uploadTierCompanies($kyc, 2, $reseller);
                    break;
                case 'denied':
                    $this->_sendEmail('Update KYC denied', $kyc->getUser()->getEmail(), '', $kyc, 1, 'denied', $reseller );
                    break;
                case 'pending':
                    //TODO notify admins
                    $this->_sendEmail('Update KYC required', 'kyc@robotunion.org', '', $kyc, 2 , 'pending', $reseller);
            }
        }
    */
    }

    private function _uploadTierCompanies(KYC $kyc, $tier, Group $reseller){

        //search all comanies with this kyc_manager
        $em = $this->container->get('doctrine')->getManager();
        $companies = $em->getRepository('FinancialApiBundle:Group')->findBy(array(
            'kyc_manager'   =>  $kyc->getUser()
        ));

        $this->logger->info('TIER '.$tier.' : update '.count($companies).' companies');
        foreach($companies as $company){
            $company->setTier($tier);
            $em->flush();
        }

        //notify to this kyc manager all companies updated
        //$this->_sendEmail('Update KYC accepted', $kyc->getUser()->getEmail(), $companies, $kyc, $tier, 'accepted', $reseller );
        //notify admin all companies updated
        //$this->_sendEmail('Update KYC accepted', 'kyc@robotunion.org', $companies, $kyc, $tier , 'accepted', $reseller);
    }
}