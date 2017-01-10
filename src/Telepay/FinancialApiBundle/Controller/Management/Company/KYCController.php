<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\UploadManager;
use Telepay\FinancialApiBundle\Entity\KYC;
use Telepay\FinancialApiBundle\Entity\KYCCompanyValidations;
use Telepay\FinancialApiBundle\Entity\TierValidations;
use Telepay\FinancialApiBundle\Entity\UserFiles;

class KYCController extends BaseApiController{

    public function getRepositoryName(){
        return 'TelepayFinancialApiBundle:KYC';
    }

    public function getNewEntity(){
        new KYC();
    }
    /**
     * @Rest\View
     */
    public function uploadFile(Request $request){

        $paramNames = array(
            'url',
            'description',
            'tier'
        );

        $params = array();
        foreach($paramNames as $paramName){
            if($request->request->has($paramName)){
                $params[$paramName] = $request->request->get($paramName);
            }else{
                throw new HttpException(404, 'Param '.$paramName.' not found');
            }
        }

        $user = $this->getUser();
        $fileManager = $this->get('file_manager');

        $fileSrc = $params['url'];
        $fileContents = $fileManager->readFileUrl($fileSrc);
        $hash = $fileManager->getHash();
        $explodedFileSrc = explode('.', $fileSrc);
        $ext = $explodedFileSrc[count($explodedFileSrc) - 1];
        $filename = $hash . '.' . $ext;

        file_put_contents($fileManager->getUploadsDir() . '/' . $filename, $fileContents);

        $tmpFile = new File($fileManager->getUploadsDir() . '/' . $filename);
        if (!in_array($tmpFile->getMimeType(), UploadManager::$ALLOWED_MIMETYPES))
            throw new HttpException(400, "Bad file type");

        $em = $this->getDoctrine()->getManager();
        $tier = $em->getRepository('TelepayFinancialApiBundle:TierValidations')->findOneBy(array(
            'user'  =>  $user->getId()
        ));

        $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
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
        if($params['tier'] == 1){
            $file = new UserFiles();
            $file->setUrl($fileManager->getFilesPath().'/'.$filename);
            $file->setStatus('pending');
            $file->setUser($company->getKycManager());
            $file->setDescription($params['description']);
            $file->setExtension($ext);

            $em->persist($file);
            $em->flush();
        }elseif($params['tier'] == 2){

            $file = new UserFiles();
            $file->setUrl($fileManager->getFilesPath().'/'.$filename);
            $file->setStatus('pending');
            $file->setUser($company->getKycManager());
            $file->setDescription($params['description']);
            $file->setExtension($ext);

            $em->persist($file);
            $em->flush();

        }else{
            throw new HttpException(404, 'Bad value for tier');
        }

        return $this->rest(204, 'Tier updated successfully');

    }

    /**
     * @Rest\View
     */
    public function getKyc(){

        //TODO get company
        $user = $this->get('security.context')->getToken()->getUser();
        $company = $user->getActiveGroup();
        $current_tier = $company->getTier();
        //TODO return all kyc data
        $em = $this->getDoctrine()->getManager();
        $user_kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
            'user'  =>  $user->getId()
        ));

        $user_files = $em->getRepository('TelepayFinancialApiBundle:UserFiles')->findBy(array(
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

        $user_files = $em->getRepository('TelepayFinancialApiBundle:UserFiles')->findBy(array(
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

        $em = $this->getDoctrine()->getManager();
        $kyc = $em->getRepository($this->getRepositoryName())->findOneBy(array(
            'user'  =>  $kycManager
        ));

        if(!$kyc) throw new HttpException(404, 'KYC not found');

        $tier = $request->request->get('tier');

        if($kyc->getTier1Status() == 'pending' || $kyc->getTier2Status() == 'pending'){
            throw new HttpException(403,' You has a pending validation request. Please enhance your calm');
        }
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