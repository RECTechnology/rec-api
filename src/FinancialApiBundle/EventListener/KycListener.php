<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 9/11/16
 * Time: 12:33
 */
namespace App\FinancialApiBundle\EventListener;

use App\FinancialApiBundle\Entity\Client;
use App\FinancialApiBundle\Entity\UserGroup;
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

            //chequear que la company del client esta activa si no fuera
            /** @var Client $client */
            $client = $entity->getClient();
            if($client->getGroup()->getActive() === false){
                throw new HttpException(403, 'This account is disabled, please contact support.');
            }
            //si la company esta activa y grant_type = password -> si la company del user no esta activa fuera si esta activa pa dentro

            /** @var User $user */
            $user = $entity->getUser();

            if($user && !$user->isKYC()){
                $this->logger->info('user id : '.$user->getId().' '.$user->getRoles()[0]);
                $accounts = $user->getGroups();
                //if user is authenticated with password
                $activeAccount = $user->getActiveGroup();
                if(!$activeAccount){
                    $this->logger->warn('No active account, setting active groups to all accounts... ' . count($accounts));
                    foreach ($accounts as $account){
                        $user->setActiveGroup($account);
                        $entityManager->flush();
                        break;
                    }

                }
                $this->logger->info('pre-insert check locked account');
                if(!$activeAccount->getActive()){

                    $changed = false;
                    foreach ($accounts as $account){
                        if($account->getId() != $activeAccount->getId() && $account->getActive()){
                            $user->setActiveGroup($account);
                            $entityManager->flush();
                            $changed = true;
                            break;
                        }
                    }
                    if($changed) throw new HttpException(403, 'This account is disabled, please contact support.');
                }
            }
            return;
        }
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
    }
}