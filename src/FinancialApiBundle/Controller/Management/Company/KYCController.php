<?php

namespace App\FinancialApiBundle\Controller\Management\Company;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Controller\BaseApiController;
use App\FinancialApiBundle\Entity\KYC;
use App\FinancialApiBundle\Entity\TierValidations;
use App\FinancialApiBundle\Entity\UserFiles;

class KYCController extends BaseApiController{

    public function getRepositoryName(){
        return 'FinancialApiBundle:KYC';
    }

    public function getNewEntity(){
        new KYC();
    }

    /**
     * @Rest\View
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function uploadFile(Request $request){
        $paramNames = array(
            'url',
            'tag'
        );

        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request->has($paramName)){
                throw new HttpException(400, 'Param '.$paramName.' is required');
            }
        }

        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();
        $tier = $em->getRepository('FinancialApiBundle:TierValidations')->findOneBy(array(
            'user'  =>  $user->getId()
        ));

        $kyc = $em->getRepository('FinancialApiBundle:KYC')->findOneBy(array(
            'user'  =>  $user->getId()
        ));

        $company = $user->getActiveGroup();

        if(!$tier){
            $tier = new TierValidations();
            $tier->setUser($user);
        }

        if(!$kyc){
            $kyc = new KYC();
            $kyc->setUser($user);
        }

        //get tier
        $file = new UserFiles();
        $file->setUrl($request->request->get('url'));
        $file->setStatus('pending');
        $file->setUser($company->getKycManager());
        $url_exploded = explode(".", $request->request->get('url'));
        $file->setExtension($url_exploded[count($url_exploded) - 1]);
        $file->setTag($params['tag']);
        $em->persist($file);
        $em->flush();

        return $this->rest(200, 'Tier updated successfully');
    }

    /**
     * @Rest\View
     */
    public function getKyc(){

        //TODO get company
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $company = $user->getActiveGroup();
        $current_tier = $company->getTier();
        //TODO return all kyc data
        $em = $this->getDoctrine()->getManager();
        $user_kyc = $em->getRepository('FinancialApiBundle:KYC')->findOneBy(array(
            'user'  =>  $user->getId()
        ));

        $user_files = $em->getRepository('FinancialApiBundle:UserFiles')->findBy(array(
            'user'  =>  $user->getId()
        ));

        $response = array(
            'current_tier'  =>  $current_tier,
            'tier0' =>  array(
                'account'   =>  true,
                'email'    =>  $user_kyc->getEmailValidated(),
                'sign_up'   =>  true,
                'verified'  =>  $user_kyc->getEmailValidated()
            ),
            'tier1' =>  array(
                'full_name' =>  $user_kyc->getFullNameValidated(),
                'birth' =>  $user_kyc->getDateBirthValidated(),
                'country'   =>  $user_kyc->getCountryValidated(),
                'phone' =>  $user_kyc->getPhoneValidated(),
                'verified'  =>  $user_kyc->getTier1Status()
            ),
            'tier2' =>  array(
                'address'   =>  $user_kyc->getAddressValidated(),
                'proof_of_residence'    =>  $user_kyc->getProofOfResidence(),
                'verified'  =>  $user_kyc->getTier2Status()
            ),
            'tier3' =>  array(
                'contact'   =>  false,
                'verified'  =>  false
            ),
            'user_files'    =>  $user_files

        );

        return $this->restV2(200, 'ok', 'Obtained data successfully', $response);
    }

    /**
     * @Rest\View
     */
    public function getUploadedFiles(){

        $user = $this->getUser();
        $company = $user->getActiveGroup();

        //TODO get all files
        $em = $this->getDoctrine()->getManager();

        $user_files = $em->getRepository('FinancialApiBundle:UserFiles')->findBy(array(
            'user'  =>  $company->getKycManager()
        ));

        return $this->rest(
            200,
            "Request successful",
            array(
                'total' => count($user_files),
                'start' => 0,
                'end' => count($user_files),
                'elements' => $user_files
            )
        );
    }


    /**
     * @Rest\View
     */
    public function requestValidation(Request $request){

        if(!$request->request->has('tier')) throw new HttpException(404, 'Param tier not found');
        $user = $this->getUser();
        $company = $user->getActiveGroup();
        $kycManager = $company->getKycManager();

        if(!$company->getKycManager()) throw new HttpException(403, 'This company has not KYC Manager');

        $em = $this->getDoctrine()->getManager();
        $kyc = $em->getRepository($this->getRepositoryName())->findOneBy(array(
            'user'  =>  $kycManager
        ));

        if(!$kyc) throw new HttpException(404, 'KYC not found');

        $tier = $request->request->get('tier');

        if($kyc->getTier1Status() == 'pending' || $kyc->getTier2Status() == 'pending') throw new HttpException(403,' You has a pending validation request. Please enhance your calm');
        if($tier == 1){
            if($kyc->getTier1Status() == 'approved') throw new HttpException(403, 'Tier validated yet');
            $kyc->setTier1Status('pending');
            $kyc->setTier1StatusRequest(new \DateTime());
        }elseif($tier == 2){
            if($kyc->getTier2Status() == 'approved') throw new HttpException(403, 'Tier validated yet');
            if($company->getTier() != 1) throw new HttpException(403, 'You have to be Tier 1 to request Tier 2 validation');
            $kyc->setTier2Status('pending');
            $kyc->setTier2StatusRequest(new \DateTime());
        }
        $em->flush();

        return $this->rest(204, 'Request successfully');

    }

}