<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use FOS\OAuthServerBundle\Propel\RefreshTokenQuery;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\UploadManager;
use Telepay\FinancialApiBundle\Entity\KYC;

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
    public function listPendingIssues(Request $request){

        //only superadmin can access here
        $em = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository($this->getRepositoryName());

        $query = $repository->createQueryBuilder('k')
            ->where('k.tier1_status = :status')
            ->orWhere('k.tier2_status = :status')
            ->setParameter('status', 'pending')
            ->getQuery();

        $list = $query->getResult();

        $response = array(
            'user_kyc'  =>  $list,
            'company_kyc'   =>  ''
        );

        return $this->restV2(201, 'success', 'List of pending Kyc successfully', $response);
    }

    /**
     * @Rest\View
     */
    public function updateAction(Request $request, $id){

        //check values that can be changed from here

        if(!$request->request->has('tier')) throw new HttpException(404, 'Param tier not found');

        $tier = $request->request->get('tier');
        $request->request->remove('tier');
        if($tier == 1){
            $validParams = array(
                'email_validated',
                'phone_validated',
                'full_name_validated',
                'date_birth_validated',
                'country_validated',
                'address_validated',
                'proof_of_residence',
                'document_validated'
            );
        }else{
            $validParams = array(
                'email',
                'phone',
                'cif',
                'zip',
                'city',
                'country',
                'address',
                'town',
                'document'
            );
        }

        $params = $request->request->all();
        foreach($params as $key => $value){
            if(!in_array($key, $validParams)) throw new HttpException(404, 'Invalid param '.$key);
        }

        if($tier == 1){
            return parent::updateAction($request, $id);
        }else{
            //get Tier company validations
            $em = $this->getDoctrine()->getManager();
            $companyKyc = $em->getRepository('TelepayFinancialApiBundle:KYCCompanyValidations')->find($id);
            //actualizar el kyc validations del group
            if(!$companyKyc) throw new HttpException(404, 'Company KYC not found');

            if($params['email'] && $params['email'] == 1) $companyKyc->setEmail(true);
            if($params['phone'] && $params['phone'] == 1) $companyKyc->setPhone(true);
            if($params['cif'] && $params['cif'] == 1) $companyKyc->setCif(true);
            if($params['zip'] && $params['zip'] == 1) $companyKyc->setZip(true);
            if($params['city'] && $params['city'] == 1) $companyKyc->setCity(true);
            if($params['country'] && $params['country'] == 1) $companyKyc->setCountry(true);
            if($params['address'] && $params['address'] == 1) $companyKyc->setAddress(true);
            if($params['town'] && $params['town'] == 1) $companyKyc->setTown(true);

            $em->flush();

            return $this->restV2(204, 'Done', 'Validations updated successfully');

        }

    }

    /**
     * @Rest\View
     */
    public function updateKYCRequest(Request $request, $id, $action){

        $em = $this->getDoctrine()->getManager();
        $kyc = $em->getRepository($this->getRepositoryName())->find($id);

        if($action != 'denied' && $action != 'approved') throw new HttpException(403,'Invalid action');

        $tier = $request->request->get('tier');

        if(!$kyc) throw new HttpException(404, 'KYC ont found');

        if(!$tier) throw new HttpException(404, 'Param tier not found');

        if($tier == 1){
            $kyc->setTier1Status($action);
        }elseif($tier == 2){
            $kyc->setTier2Status($action);
        }else{
            throw new HttpException(403, 'Invalid field tier');
        }

        $em->flush();

        return $this->restV2(204,"ok", "Updated successfully");

    }

    /**
     * @Rest\View
     */
    public function getUploadedFiles($id){

        //TODO get all files for this user
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($id);

        $user_files = $em->getRepository('TelepayFinancialApiBundle:UserFiles')->findBy(array(
            'user'  =>  $user
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
    public function uploadFile(Request $request, $id){
        $paramNames = array(
            'url',
            'tag'
        );

        $params = array();
        foreach($paramNames as $paramName){
            if($request->request->has($paramName)){
                $params[$paramName] = $request->request->get($paramName);
            }else{
                throw new HttpException(404, 'Param '.$paramName.' not found');
            }
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('TelepayFinancialApiBundle:User')->find($id);
        $fileManager = $this->get('file_manager');

        $fileSrc = $params['url'];
        $fileContents = $fileManager->readFileUrl($fileSrc);
        $hash = $fileManager->getHash();
        $explodedFileSrc = explode('.', $fileSrc);
        $ext = $explodedFileSrc[count($explodedFileSrc) - 1];
        $filename = $hash . '.' . $ext;

        file_put_contents($fileManager->getUploadsDir() . '/' . $filename, $fileContents);

        $tmpFile = new File($fileManager->getUploadsDir() . '/' . $filename);
        if (!in_array($tmpFile->getMimeType(), UploadManager::$ALLOWED_MIMETYPES)) {
            throw new HttpException(400, "Bad file type");
        }

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
        $file = new UserFiles();
        $file->setUrl($fileManager->getFilesPath().'/'.$filename);
        $file->setStatus('pending');
        $file->setUser($company->getKycManager());
        $file->setExtension($ext);
        $file->setTag($params['tag']);
        $em->persist($file);
        $em->flush();

        return $this->rest(204, 'Tier updated successfully');
    }
}