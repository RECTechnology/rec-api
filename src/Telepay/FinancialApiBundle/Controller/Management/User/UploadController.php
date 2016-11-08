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
}