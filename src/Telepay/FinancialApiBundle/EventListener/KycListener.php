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
        $this->logger->info('POST-UPDATE Kyc_Listener');

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
        $this->logger->info('POST-INSERT');

        $entityManager = $args->getEntityManager();

        if ($entity instanceof Group) {
            $entity->setTier(0);
            return;
        }


    }

    private function checkKYC($changeset, KYC $kyc)
    {
        if (isset($changeset['phone']) ||
            isset($changeset['lastname']) ||
            isset($changeset['dateBirth'])) {
            $this->logger->info('POST-UPDATE - changing susceptible fields in kyc');
            //send change email
            $body = array(
                'message'   =>  'El Usuario '.$kyc->getUser()->getUsername().' ha cambiado algunos parametros susceptibles del kyc del usuario en la tabla kyc'
            );
            $to = array(
                'pere@chip-chap.com',
                'cto@chip-chap.com'
            );
            $action = 'user_kyc';
            $this->_sendEmail('KYC Alert change', $body, $to, $action);
        }elseif(isset($changeset['tier1_status'])){
            // send email to know qhich user is up
            $body = array(
                'message'   =>  'El Usuario '.$kyc->getUser()->getUsername().' ahora es TIER 1 VALIDADO'
            );
            $to = array(
                'pere@chip-chap.com',
                'cto@chip-chap.com'
            );
            $action = 'user_kyc';
            $this->_sendEmail('KYC Alert promote TIER', $body, $to, $action);
        }elseif(isset($changeset['tier2_status'])){
            $body = array(
                'message'   =>  'El Usuario '.$kyc->getUser()->getUsername().' ahora es TIER 2 VALIDADO'
            );
            $to = array(
                'pere@chip-chap.com',
                'cto@chip-chap.com'
            );
            $action = 'user_kyc';
            $this->_sendEmail('KYC Alert promote TIER', $body, $to, $action);
        }

        if(isset($changeset['full_name_validated']) ||
            isset($changeset['dateBirth_validated'])||
            isset($changeset['country_validated'])||
            isset($changeset['address_validated'])||
            isset($changeset['proof_of_residence'])||
            isset($changeset['document_validated'])
        ){
            $this->_checkTier($kyc);
        }

        return;
    }

    private function checkUserKYC($changeset, User $user)
    {
        if (isset($changeset['email']) || isset($changeset['name'])) {
            if((isset($changeset['email']) && $changeset['email'] != $user->getEmail()) ||
                (isset($changeset['name']) && $changeset['name'] != $user->getName())){
                $this->logger->info('POST-UPDATE - changing susceptible fields in user');
                //send change email
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

        }

        return;
    }

    private function checkCompanyKYC($changeset, Group $company)
    {

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

    private function _checkTier(KYC $kyc){

        $this->logger->info('KYC Checking Tier');
        $user = $kyc->getUser();
        //search all companies that this is the responsible
        $em = $this->container->get('doctrine')->getManager();

        $companies = $em->getRepository('TelepayFinancialApiBundle:Group')->findBy(array(
            'kyc_manager'   =>  $user->getId()
        ));
        $this->logger->info('KYC updating '.count($companies).' companies');

        $tier = 0;
        if($kyc->getEmailValidated() == true){
            $tier = 0;
        }

        if($kyc->getFullNameValidated() == true &&
            $kyc->getDateBirth() == true &&
            $kyc->getPhoneValidated() == true &&
            $kyc->getCountryValidated() == true){

            if($kyc->getTier1Status() != 'success'){
                $kyc->setTier1Status('success');
            }
            $tier = 1;
        }

        if($kyc->getAddressValidated() == true && $kyc->getProofOfResidence() == true){
            $tier = 2;
            $kyc->setTier2Status('success');
        }

        $em->flush();

        foreach($companies as $company){
            $this->logger->info('KYC updating '.$company->getName().' with TIER '.$tier.' previous TIER =>'.$company->getTier());
            $company->setTier($tier);
            $em->flush();
        }

    }


}