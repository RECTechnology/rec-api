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
        $em = $this->getDoctrine()->getManager();
        $user=$em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
            'id' => $id
        ));

        if(!$user){
            throw new HttpException(404, 'User not found');
        }

        $user_files = $em->getRepository('TelepayFinancialApiBundle:UserFiles')->findBy(array(
            'user'  =>  $user->getId(),
            'deleted'  =>  false
        ));
        $kyc = $em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
            'user'  =>  $user->getId()
        ));
        return $this->rest(
            200,
            "Request successful",
            array(
                'total' => count($user_files),
                'start' => 0,
                'end' => count($user_files),
                'files' => $user_files,
                'kyc' => $kyc
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
        if(!$user){
            throw new HttpException(404, 'User not found');
        }

        $fileManager = $this->get('file_manager');
        $fileSrc = $params['url'];
        $fileContents = $fileManager->readFileUrl($fileSrc);
        $hash = $fileManager->getHash();
        $explodedFileSrc = explode('.', $fileSrc);
        $ext = $explodedFileSrc[count($explodedFileSrc) - 1];
        $filename = $hash . '.' . $ext;

        file_put_contents($fileManager->getUploadsDir() . '/' . $filename, $fileContents);

        $tmpFile = new File($fileManager->getUploadsDir() . '/' . $filename);
        if (!in_array($tmpFile->getMimeType(), UploadManager::$FILTER_DOCUMENTS)) {
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

        if($params['tag']==='document_front'){
            $kyc->setDocumentFront($fileManager->getFilesPath() . '/' . $filename);
            $kyc->setDocumentFrontStatus('pending');
            $em->persist($kyc);
        }
        elseif($params['tag']==='document_rear'){
            $kyc->setDocumentRear($fileManager->getFilesPath() . '/' . $filename);
            $kyc->setDocumentRearStatus('pending');
            $em->persist($kyc);
        }
        else{
            $file = new UserFiles();
            $file->setUrl($fileManager->getFilesPath() . '/' . $filename);
            $file->setStatus('pending');
            $file->setUser($company->getKycManager());
            $file->setExtension($ext);
            $file->setTag($params['tag']);
            $em->persist($file);
        }
        $em->flush();
        return $this->rest(204, 'Tier updated successfully');
    }

    /**
     * @Rest\View
     */
    public function createLemonAccountAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $company=$em->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
            'id' => $id
        ));

        $individual = false;
        $enterprise = false;
        if($request->request->has('independent') && $request->request->get('independent')=='1') {
            $individual = true;
        }
        elseif($request->request->has('company') && $request->request->get('company')=='1'){
            $enterprise = true;
        }
        else{
            throw new HttpException(400, "Account type must be defined");
        }

        if(!($individual xor $enterprise)){
            throw new HttpException(400, "Only one account type must be defined");
        }

        $user=$em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
            'id' => $company->getKycManager()
        ));

        $KYC=$em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user->getId()
        ));

        $email = $user->getEmail();
        if($email == ''){
            throw new HttpException(400, "User email is empty");
        }
        if($enterprise) {
            $company_name = $company->getName();
            if ($company_name == '') {
                throw new HttpException(400, "Company name is empty");
            }
        }
        if($enterprise) {
            $company_web = 'rec.barcelona';
            $description = $company->getDescription();
            if($description == ''){
                throw new HttpException(400, "Company description is empty");
            }
        }
        $name = $KYC->getName();
        if($name == ''){
            throw new HttpException(400, "User name is empty");
        }
        $lastName = $KYC->getLastName();
        if($lastName == ''){
            throw new HttpException(400, "User lastname is empty");
        }
        $date_birth = $KYC->getDateBirth();
        if($date_birth == ''){
            throw new HttpException(400, "User birthdate is empty");
        }
        $nationality = $KYC->getNationality();
        if($nationality == ''){
            throw new HttpException(400, "User nationality is empty");
        }
        $gender = $KYC->getGender();
        if($gender == ''){
            throw new HttpException(400, "User gender is empty");
        }
        $address = $company->getStreetType() . " " . $company->getStreet() . " " . $company->getAddressNumber();
        if(str_replace(' ', '', $address) == ''){
            throw new HttpException(400, "Account address is empty.");
        }
        $zip = $company->getZip();
        if($zip == ''){
            throw new HttpException(400, "Account zip is empty");
        }
        $city = $company->getCity();
        if($city == ''){
            throw new HttpException(400, "Account city is empty");
        }
        $country = $company->getCountry();
        if($country == ''){
            throw new HttpException(400, "Account country is empty");
        }
        if($request->request->has('create') && $request->request->get('create')=='1'){
            if($company->getLemonId()!='' && $company->getLemonId()>0){
                throw new HttpException(400, "Error, account already registered");
            }
            $moneyProvider = $this->get('net.telepay.in.lemonway.v1');
            $new_account = array();
            if($individual){
                $new_account = $moneyProvider->RegisterWalletIndividual($company->getCIF(), $email, $name, $lastName, $date_birth, $nationality, $gender, $address, $zip, $city, $country);
            }
            elseif($enterprise) {
                $new_account = $moneyProvider->RegisterWalletCompany($company->getCIF(), $email, $company_name, $company_web, $description, $name, $lastName, $date_birth, $nationality, $gender, $address, $zip, $city, $country);
            }

            if(!is_object($new_account) && isset($new_account['REGISTERWALLET']) && isset($new_account['REGISTERWALLET']['STATUS']) && $new_account['REGISTERWALLET']['STATUS'] == '-1'){
                $logger = $this->get('manager.logger');
                $logger->info('Lemon error: '. $new_account['REGISTERWALLET']['MESSAGE']);
                throw new HttpException(400, "Error creating the account");
            }
            if(!isset($new_account->WALLET->LWID)){
                throw new HttpException(400, "Error, lemonWay service is down");
            }

            $lemon_id = $new_account->WALLET->LWID;
            $company->setLemonId($lemon_id);
            $em->persist($company);
            $em->flush();
            return $this->rest(204, 'Account created properly');
        }
        else{
            return $this->rest(204, 'All data checked properly');
        }
    }

    public function uploadLemonDocumentationAction(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $moneyProvider = $this->get('net.telepay.in.lemonway.v1');
        $uploads_dir = $this->container->getParameter('uploads_dir');

        $upload = false;
        if($request->request->has('upload') && $request->request->get('upload')=='1'){
            $upload = true;
        }

        $company=$em->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
            'id' => $id
        ));
        $lemon_username = $company->getCIF();

        $individual = false;
        $enterprise = false;
        if($request->request->has('independent') && $request->request->get('independent')=='1') {
            $individual = true;
        }
        elseif($request->request->has('company') && $request->request->get('company')=='1'){
            $enterprise = true;
        }
        else{
            throw new HttpException(400, "Account type must be defined");
        }

        if(!($individual xor $enterprise)){
            throw new HttpException(400, "Only one account type must be defined");
        }

        $user=$em->getRepository('TelepayFinancialApiBundle:User')->findOneBy(array(
            'id' => $company->getKycManager()
        ));

        $KYC=$em->getRepository('TelepayFinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user->getId()
        ));

        $doc_front_status = $KYC->getDocumentFrontStatus();
        if($doc_front_status!=='upload'){
            $doc_front = $KYC->getDocumentFront();
            if($doc_front ==''){
                throw new HttpException(400, "Document front not upload");
            }
            if($upload) {
                $type = 0;
                $file_name = $KYC->getDocumentFront();
                $datos = explode("/", $file_name);
                $file = $datos[3];
                $details = explode(".", $file);
                $lemon_filename = "id_front." . $details[1];
                $buffer = base64_encode(file_get_contents($uploads_dir . $file, true));
                $up_file = $moneyProvider->UploadFile($lemon_username, $lemon_filename, $type, $buffer);
                if($up_file['']['']){
                    $KYC->setDocumentFrontStatus('upload');
                    $em->persist($KYC);
                    $em->flush();
                }
                else{
                    throw new HttpException(400, "Error uploading document front file");
                }
            }
        }

        $doc_rear_status = $KYC->getDocumentRearStatus();
        if($doc_rear_status!=='upload'){
            $doc_rear = $KYC->getDocumentRear();
            if($doc_rear ==''){
                throw new HttpException(400, "Document rear not upload");
            }
            if($upload) {
                $type = 1;
                $file_name = $KYC->getDocumentRear();
                $datos = explode("/", $file_name);
                $file = $datos[3];
                $details = explode(".", $file);
                $lemon_filename = "id_back." . $details[1];
                $buffer = base64_encode(file_get_contents($uploads_dir . $file, true));
                $up_file = $moneyProvider->UploadFile($lemon_username, $lemon_filename, $type, $buffer);
                if($up_file['']['']){
                    $KYC->setDocumentRearStatus('upload');
                    $em->persist($KYC);
                    $em->flush();
                }
                else{
                    throw new HttpException(400, "Error uploading document rear file");
                }
            }
        }

        $files=$em->getRepository('TelepayFinancialApiBundle:UserFiles')->findBy(array(
            'user' => $user->getId(),
            'deleted' => false
        ));

        if($individual){
            $list_tags = array("banco","autonomo","modelo03x");
        }
        else{
            $list_tags = array("banco","cif","modelo200","titularidad","estatutos");
        }

        $list_files = array();
        foreach($files as $file){
            $list_files[]=$file->getTag();
        }
        foreach($list_tags as $tag){
            if(!in_array($tag, $list_files)){
                throw new HttpException(400, "Document " . $tag . " not upload");
            }
            //si uno está repetido?
            //el 200 y la titularidad es uno o el otro?
            //estatutos es obligatorio?
        }

        if($upload){
            return $this->rest(204, 'All data upload properly');
        }
        else{
            return $this->rest(204, 'All data checked properly');
        }
    }

}