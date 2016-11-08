<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\UploadManager;
use Telepay\FinancialApiBundle\Entity\KYCUserValidations;

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
        //TODO get tier
        if($params['tier'] == 1){
            //user document
            $tier = $em->getRepository('TelepayFinancialApiBundle:KYCUserValidations')->findOneBy(array(
                'user'  =>  $user->getId()
            ));

            if(!$tier){
                $tier = new KYCUserValidations();
                $tier->setUser($user);
            }

            $tier->setTier1File($fileManager->getFilesPath().'/'.$filename);

            $em->persist($tier);
            $em->flush();
        }elseif($params['tier'] == 2){

        }else{
            throw new HttpException(404, 'Bad value for tier');
        }

        return $this->rest(204, 'Tier updated successfully');

    }

}