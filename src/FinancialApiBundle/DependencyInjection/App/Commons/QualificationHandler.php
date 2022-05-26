<?php

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use App\FinancialApiBundle\DependencyInjection\App\Interfaces\QualificationHandlerInterface;
use App\FinancialApiBundle\Document\Transaction;
use App\FinancialApiBundle\Entity\Badge;
use App\FinancialApiBundle\Entity\Campaign;
use App\FinancialApiBundle\Entity\ConfigurationSetting;
use App\FinancialApiBundle\Entity\Group;
use App\FinancialApiBundle\Entity\PaymentOrder;
use App\FinancialApiBundle\Entity\Qualification;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class QualificationHandler implements QualificationHandlerInterface
{

    private $doctrine;

    /** @var ContainerInterface $container */
    private $container;

    public function __construct($doctrine, ContainerInterface $container)
    {
        $this->doctrine = $doctrine;
        $this->container = $container;
    }

    public function createQualificationBattery(Transaction $tx)
    {
        $em = $this->getEntityManager();

        $reviewer = $this->getReviewerFromTx($tx);
        $receiver = $this->getReceiverFromTx($tx);

        if($this->isTxQualificable($tx)){
            $badges = $this->getRandomBadges(9);
            foreach ($badges as $badge){
                $qualification = new Qualification();
                $qualification->setStatus(Qualification::STATUS_PENDING);
                $qualification->setValue(null);
                $qualification->setReviewer($reviewer);
                $qualification->setAccount($receiver);
                $qualification->setBadge($badge);

                $em->persist($qualification);
            }
            $em->flush();
        }


    }

    private function getAllBadges(){
        $em = $this->getEntityManager();
        return $em->getRepository(Badge::class)->findAll();
    }

    private function getRandomBadges($limit){
        $badges = $this->getAllBadges();

        shuffle($badges);

        return array_slice($badges, 0, $limit);

    }

    private function getEntityManager(){
        return $this->doctrine->getManager();
    }

    private function getReviewerFromTx(Transaction $tx){
        $em = $this->getEntityManager();
        return $em->getRepository(Group::class)->find($tx->getGroup());
    }

    private function getReceiverFromTx(Transaction $tx){
        $em = $this->getEntityManager();
        $receiver = $em->getRepository(Group::class)->findOneBy(array('rec_address' => $tx->getPayOutInfo()['address']));

        if(!$receiver){
            //buscar en paymentOrders
            $paymentOrder = $em->getRepository(PaymentOrder::class)->findOneBy(array(
                'payment_address' => $tx->getPayOutInfo()['address']
            ));

            $receiver = $paymentOrder->getPos()->getAccount();
        }

        return $receiver;
    }

    private function isTxQualificable(Transaction $tx){

        //check if system es enabled
        if(!$this->isQualificationSystemEnabled()) return false;

        //is receiver a shop?
        /** @var Group $receiver */
        $receiver = $this->getReceiverFromTx($tx);
        if($receiver->getType() === Group::ACCOUNT_TYPE_PRIVATE) return false;

        /** @var CampaignChecker $campaignChecker */
        $campaignChecker = $this->container->get('net.app.commons.campaign_checker');
        try {
            $isCultureCampaign = $campaignChecker->isReceiverInCampaign($tx, Campaign::CULTURE_CAMPAIGN_NAME);
            if($isCultureCampaign) return false;
        }catch (HttpException $e){
            return false;
        }
        //is receiver/reviewer in some campign not acumulable? REC Cultural
        return true;
    }

    private function isQualificationSystemEnabled(){
        $em = $this->getEntityManager();
        $qualificationSystemStatus = $em->getRepository(ConfigurationSetting::class)->findOneBy(array(
            'scope' => ConfigurationSetting::QUALIFICATIONS_SCOPE,
            'name' => ConfigurationSetting::SETTING_QUALIFICATIONS_SYSTEM_STATUS
        ));

        if(!$qualificationSystemStatus) return false;

        if($qualificationSystemStatus === 'disabled') return false;

        return true;
    }
}