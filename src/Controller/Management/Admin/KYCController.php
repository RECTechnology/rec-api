<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace App\Controller\Management\Admin;

use FOS\OAuthServerBundle\Propel\RefreshTokenQuery;
use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use App\Controller\BaseApiController;
use App\Entity\KYC;
use App\Entity\TierValidations;
use App\Entity\UserFiles;

class KYCController extends BaseApiController{

    public function getRepositoryName(){
        return 'FinancialApiBundle:KYC';
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

        return $this->rest(201, 'success', 'List of pending Kyc successfully', $response);
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
            $companyKyc = $em->getRepository('FinancialApiBundle:KYCCompanyValidations')->find($id);
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

            return $this->rest(204, 'Done', 'Validations updated successfully');

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

        return $this->rest(204,"ok", "Updated successfully");

    }

    /**
     * @Rest\View
     */
    public function getUploadedFiles($id){
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new HttpException(403, 'You have not the necessary permissions');
        }

        $em = $this->getDoctrine()->getManager();
        $user=$em->getRepository('FinancialApiBundle:User')->findOneBy(array(
            'id' => $id
        ));

        if(!$user){
            throw new HttpException(404, 'User not found');
        }

        $user_files = $em->getRepository('FinancialApiBundle:UserFiles')->findBy(array(
            'user'  =>  $user->getId(),
            'deleted'  =>  false
        ));
        $kyc = $em->getRepository('FinancialApiBundle:KYC')->findOneBy(array(
            'user'  =>  $user->getId()
        ));

        if($kyc->getDocumentFront()){
            $user_files[] = array(
                'tag' => 'document_front',
                'url'=> $kyc->getDocumentFront(),
                'status' => $kyc->getDocumentFrontStatus(),
                'deleted' => false
            );
        }
        if($kyc->getDocumentRear()){
            $user_files[] = array(
                'tag' => 'document_rear',
                'url'=> $kyc->getDocumentRear(),
                'status' => $kyc->getDocumentRearStatus(),
                'deleted' => false
            );
        }

        $data =  [
            'total' => count($user_files),
            'start' => 0,
            'end' => count($user_files),
            'files' => $user_files,
            'kyc' => $kyc
        ];

        $ctx = new SerializationContext();
        $ctx->enableMaxDepthChecks();
        $resp = $this->get('jms_serializer')->toArray($data, $ctx);

        return $this->rest(
            200,
            "ok",
            "Request successful",
            $resp
        );
    }

    /**
     * @Rest\View
     */
    public function deleteFile($tag, $id){
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new HttpException(403, 'You have not the necessary permissions');
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('FinancialApiBundle:User')->find($id);
        if(!$user){
            throw new HttpException(404, 'User not found');
        }

        $file=$em->getRepository('FinancialApiBundle:UserFiles')->findOneBy(array(
            'user' => $user->getId(),
            'deleted' => false,
            'tag' => $tag
        ));

        if($file){
            $file->setStatus('deleted');
            $file->setDeleted(true);
            $em->persist($file);
            $em->flush();
            return $this->rest(204, "ok", 'File deleted successfully');
        }
        else{
            throw new HttpException(404, 'File not found');
        }

    }

    /**
     * @param Request $request
     * @param $tag
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     * @Rest\View
     */
    public function uploadFile(Request $request, $tag, $id){
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new HttpException(403, 'You have not the necessary permissions');
        }

        $paramNames = ['url'];

        foreach($paramNames as $paramName){
            if(!$request->request->has($paramName)){
                throw new HttpException(404, 'Param '.$paramName.' not found');
            }
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('FinancialApiBundle:User')->find($id);
        if(!$user){
            throw new HttpException(404, 'User not found');
        }

        $file=$em->getRepository('FinancialApiBundle:UserFiles')->findOneBy(array(
            'user' => $user->getId(),
            'deleted' => false,
            'tag' => $tag
        ));
        if($file){
            throw new HttpException(404, 'One file is saved already with this tag');
        }

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

        if($tag==='document_front'){
            $kyc->setDocumentFront($request->request->get('url'));
            $kyc->setDocumentFrontStatus('pending');
            $em->persist($kyc);
        }
        elseif($tag==='document_rear'){
            $kyc->setDocumentRear($request->request->get('url'));
            $kyc->setDocumentRearStatus('pending');
            $em->persist($kyc);
        }
        else{
            $file = new UserFiles();
            $file->setUrl($request->request->get('url'));
            $file->setStatus('pending');
            $file->setUser($company->getKycManager());
            $exploded = explode('.', $request->request->get('url'));
            $file->setExtension($exploded[count($exploded) - 1]);
            $file->setTag($tag);
            $em->persist($file);
        }
        $em->flush();
        return $this->rest(200, "ok",'File updated successfully');
    }

    /**
     * @Rest\View
     */
    public function createLemonAccountAction(Request $request, $id){

        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new HttpException(403, 'You have not the necessary permissions');
        }

        $em = $this->getDoctrine()->getManager();
        $company=$em->getRepository('FinancialApiBundle:Group')->findOneBy(array(
            'kyc_manager' => $id
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

        $user=$em->getRepository('FinancialApiBundle:User')->findOneBy(array(
            'id' => $company->getKycManager()
        ));

        $KYC=$em->getRepository('FinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user->getId()
        ));

        $email = $company->getEmail();
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
            $moneyProvider = $this->get('net.app.in.lemonway.v1');
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
            return $this->rest(204, "ok", 'Account created properly');
        }
        else{
            return $this->rest(204, "ok", 'All data checked properly');
        }
    }

    public function uploadLemonDocumentationAction(Request $request, $id){

        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new HttpException(403, 'You have not the necessary permissions');
        }

        $em = $this->getDoctrine()->getManager();
        $moneyProvider = $this->get('net.app.in.lemonway.v1');
        $uploads_dir = $this->container->getParameter('uploads_dir');

        $upload = false;
        if($request->request->has('upload') && $request->request->get('upload')=='1'){
            $upload = true;
        }

        $company=$em->getRepository('FinancialApiBundle:Group')->findOneBy(array(
            'kyc_manager' => $id
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

        $user=$em->getRepository('FinancialApiBundle:User')->findOneBy(array(
            'id' => $company->getKycManager()
        ));

        $KYC=$em->getRepository('FinancialApiBundle:KYC')->findOneBy(array(
            'user' => $user->getId()
        ));

        $doc_front_status = $KYC->getDocumentFrontStatus();
        if($doc_front_status !== 'upload'){
            $doc_front = $KYC->getDocumentFront();
            if($doc_front === ''){
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
                if($this->checkLemonUpload($up_file)){
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
        if($doc_rear_status !== 'upload'){
            $doc_rear = $KYC->getDocumentRear();
            if($doc_rear == ''){
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
                if($this->checkLemonUpload($up_file)){
                    $KYC->setDocumentRearStatus('upload');
                    $em->persist($KYC);
                    $em->flush();
                }
                else{
                    throw new HttpException(400, "Error uploading document rear file");
                }
            }
        }

        $files=$em->getRepository('FinancialApiBundle:UserFiles')->findBy(array(
            'user' => $user->getId(),
            'deleted' => false
        ));

        if($individual){
            $list_must_tags = array("banco","autonomo","modelo03x");
            $list_optional_tags = array("pasaporte");
            $all_tags = array_merge($list_must_tags, $list_optional_tags);
        }
        else{
            $list_must_tags = array("banco","cif","modelo200_o_titularidad","estatutos");
            $list_optional_tags = array("pasaporte", "otroDNI_front", "otroDNI2_front", "otroDNI_rear", "otroDNI2_rear", "poderes");
            $all_tags = array_merge($list_must_tags, $list_optional_tags);
        }

        $list_files = array();
        foreach($files as $file){
            $list_files[$file->getTag()]=$file;
        }
        foreach($all_tags as $tag){
            if(in_array($tag, $list_must_tags) && !array_key_exists($tag, $list_files)){
                throw new HttpException(400, "Document " . $tag . " not uploaded");
            }
            else{
                $file=$list_files[$tag];
                if($file->getStatus() === 'pending'){
                    $type = $file->getType();
                    $file_name = $file->getUrl();
                    $datos = explode("/", $file_name);
                    $file = $datos[3];
                    $lemon_filename = $tag . "." . $file->getExtension();
                    $buffer = base64_encode(file_get_contents($uploads_dir . $file, true));
                    $up_file = $moneyProvider->UploadFile($lemon_username, $lemon_filename, $type, $buffer);
                    if($this->checkLemonUpload($up_file)){
                        $file->setStatus('upload');
                        $em->persist($file);
                        $em->flush();
                    }
                    else{
                        throw new HttpException(400, "Error uploading " . $tag . " file");
                    }
                }
            }
        }

        if($upload){
            return $this->rest(204, "ok", 'All data upload properly');
        }
        else{
            return $this->rest(204, "ok", 'All data checked properly');
        }
    }

    public function newLemonIdAction(Request $request){
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_SUPER_ADMIN')) {
            throw new HttpException(403, 'You have not the necessary permissions');
        }

        $paramNames = array(
            'old_id',
            'new_id'
        );

        foreach($paramNames as $paramName){
            if(!$request->request->has($paramName)){
                throw new HttpException(404, 'Param '.$paramName.' not found');
            }
        }

        $moneyProvider = $this->get('net.app.in.lemonway.v1');
        $response = $moneyProvider->UpdateIdentification($request->request->get('old_id'), $request->request->get('old_id'));
        return $this->rest(204, "ok", "Done", $response);
    }

    private function checkLemonUpload($data){
        $logger = $this->get('manager.logger');
        $logger->info('Lemon update log: '. json_encode($data));
        if(!is_object($data)){
            if(isset($data['UPDATE']['MESSAGE'])){
                $logger->info('Lemon update error 1: '. $data['UPDATE']['MESSAGE']);
                return false;
            }
            else{
                $logger->info('Lemon update error 2: message is not defined');
                return false;
            }
        }
        if(!isset($data->UPDATE->ID)){
            $logger->info("Lemon update error 3: Id is not defined");
            return false;
        }
        return true;
    }

}