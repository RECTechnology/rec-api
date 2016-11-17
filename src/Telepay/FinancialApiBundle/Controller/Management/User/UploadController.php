<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 3/10/15
 * Time: 4:38 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\User;
use DateInterval;
use DateTime;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\UploadManager;

/**
 * Class UploadController
 * @package Telepay\FinancialApiBundle\Controller\Management\User
 */
class UploadController extends RestApiController{

    public function uploadFile(Request $request){

        $fileManager = $this->get('file_manager');

        if(!$request->files->has('file'))
            throw new HttpException(400, "'file' parameter required to be a file");

        $file = $request->files->get('file');
        if(!$file->isValid()) throw new HttpException(400, "Invalid file");

        $mimeType = $file->getMimeType();
        if(!in_array($mimeType, UploadManager::$ALLOWED_MIMETYPES))
            throw new HttpException(400, "Bad mime type, '" . $mimeType . "' is not a valid file");

        $hash = $fileManager->getHash();
        $ext = $file->guessExtension();
        $fileName = $hash . '.tmp.' . $ext;
        $file->move($fileManager->getUploadsDir(), $fileName);

        return $this->rest(
            201,
            "Temporal file uploaded",
            [
                'src' => $fileManager->getFilesPath() . '/' . $fileName,
                'type' => $mimeType,
                'expires_in' => 600
            ]
        );
    }

    public function uploadFile2(Request $request){
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
        }else{
            throw new HttpException(404, 'Bad request, extension not allowed');
        }

        $name = uniqid('kyc_');
        $fs = new Filesystem();
        $fs->dumpFile($this->container->getParameter('uploads_dir').'/' . $name . $ext, base64_decode($base64));

        return $this->rest(
            201,
            "Temporal file uploaded",
            [
                'src' => $this->container->getParameter('files_path') . '/' . $name.$ext,
                'type' => $ext,
                'expires_in' => 600
            ]
        );
    }
}