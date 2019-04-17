<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/18
 * Time: 4:38 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\User;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\UploadManager;

/**
 * Class UploadController
 * @package Telepay\FinancialApiBundle\Controller\Management\User
 */
class UploadController extends RestApiController{

    public function uploadFile(Request $request){
        if(!$request->files->has('file'))
            throw new HttpException(400, "'file' parameter required to be a file");


        /** @var UploadedFile $file */
        $file = $request->files->get('file');

        $fileData = $this->file_uploader($file);
        return $this->rest(
            201,
            "Temporal file uploaded",
            [
                'src' => $fileData['path'] . '/' . $fileData['name'],
                'type' => $fileData['mime'],
                'expires_in' => 600
            ]
        );
    }

    public function uploadFileBase64(Request $request){
        $base64_image = $request->request->get('base64_image');

        //search for extension
        if(strpos($base64_image, 'data:image/jpeg;base64') !== false){
            //is jpg file
            $base64 = str_replace('data:image/jpeg;base64,', '', $base64_image);
            $ext = '.jpg';
        }elseif(strpos($base64_image, 'data:image/png;base64') !== false){
            //is png file
            $base64 = str_replace('data:image/png;base64,', '', $base64_image);
            $ext = '.png';
        }elseif(strpos($base64_image, 'data:application/pdf;base64') !== false){
            //is pdff file
            $base64 = str_replace('data:application/pdf;base64,', '', $base64_image);
            $ext = '.pdf';
        }else{
            throw new HttpException(404, 'Bad request, extension not allowed');
        }

        $name = uniqid('kyc_');
        $fs = new Filesystem();
        $fs->dumpFile($this->container->getParameter('uploads_dir').'/' . $name .'.tmp'. $ext, base64_decode($base64));

        return $this->rest(
            201,
            "Temporal file uploaded",
            [
                'src' => $this->container->getParameter('files_path') . '/' . $name.'.tmp'.$ext,
                'type' => $ext,
                'expires_in' => 600
            ]
        );
    }

    /**
     * @param UploadedFile $file
     * @return array
     */
    public function file_uploader($file){

        if(!$file->isValid()) throw new HttpException(400, "Invalid file");

        $mimeType = $file->getMimeType();
        if(!in_array($mimeType, UploadManager::allMimeTypes()))
            throw new HttpException(400, "Bad mime type, '" . $mimeType . "' is not a valid file");


        $fileManager = $this->get('file_manager');
        $hash = $fileManager->getHash();
        $ext = $file->guessExtension();
        $fileName = $hash . '.tmp.' . $ext;
        $file->move($fileManager->getUploadsDir(), $fileName);

        return  ['path'=>$fileManager->getFilesPath(), 'name'=>$fileName, 'mime'=>$mimeType];
}
}