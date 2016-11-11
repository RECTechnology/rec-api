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

        if(!$tier){
            $tier = new TierValidations();
            $tier->setUser($user);
        }

        if(!$kyc){
            $kyc = new KYC();
            $kyc->setUser($user);
        }

        //TODO get tier
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
            $kyc->setTier2File($fileManager->getFilesPath().'/'.$filename);
            $kyc->setTier2FileDescription($params['description']);
            $kyc->setTier2Status('pending');

            $em->persist($kyc);
            $em->flush();

        }else{
            throw new HttpException(404, 'Bad value for tier');
        }

        return $this->rest(204, 'Tier updated successfully');

    }

}