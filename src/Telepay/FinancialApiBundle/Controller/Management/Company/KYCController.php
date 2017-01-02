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

class KYCController extends BaseApiController{

    public function getRepositoryName(){
        return '';
    }

    public function getNewEntity(){

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

        $company_kyc = $em->getRepository('TelepayFinancialApiBundle:KYCCompanyValidations')->findOneBy(array(
            'company'  =>  $company
        ));

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
            //user document
            if($params['description'] == 'front'){
                $kyc->setImageFront($fileManager->getFilesPath().'/'.$filename);
            }else{
                $kyc->setImageBack($fileManager->getFilesPath().'/'.$filename);
            }

            $kyc->setTier1Status('pending');


            $em->persist($tier);
            $em->persist($kyc);
            $em->flush();
        }elseif($params['tier'] == 2){
            if(!$company_kyc){
                $company_kyc = new KYCCompanyValidations();
                $company_kyc->setCompany($company);
            }
            $company_kyc->setTier2File($fileManager->getFilesPath().'/'.$filename);
            $company_kyc->setTier2FileDescription($params['description']);
            $company_kyc->setTier2Status('pending');

            $em->persist($company_kyc);
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
            )

        );

        return $this->restV2(200, 'ok', 'Obtained data successfully', $response);
    }

}