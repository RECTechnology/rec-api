<?php

namespace App\Controller\Management\User;

use App\Controller\RestApiController;
use App\DependencyInjection\Commons\DiscourseApiManager;
use App\DependencyInjection\Commons\UploadManager;
use App\Entity\Group;
use App\Entity\User;
use App\Exception\AppException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use This;

class DiscourseController extends RestApiController {

    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     */
    public function bridgeDiscourseAction(Request $request, $discourse_endpoint){
        /** @var User $user */
        $user = $this->get('security.token_storage')->getToken()->getUser();
        /** @var Group $account */
        $account = $this->get('security.token_storage')->getToken()->getUser()->getActiveGroup();
        if(!$account->getActive()) throw new AppException(412, "Default account is not active");

        //check if account is b2b_rezero
        if($account->getRezeroB2bAccess() !== Group::ACCESS_STATE_GRANTED) throw new HttpException(403, 'Account not granted');

        if(!$account->getRezeroB2bApiKey() || !$account->getRezeroB2bUserId()) throw new HttpException(403, 'Account not configured yet');

        $params = $request->request->all();
        $urlParams = $request->query->all();
        $fileData = null;
        if($request->files->has('file')){
            $file = $request->files->get('file');
            $fileData = $this->file_uploader($file);
        }

        //TODO call discourse
        /** @var DiscourseApiManager $discourseManager */
        $discourseManager = $this->container->get('net.app.commons.discourse.api_manager');
        try{
            $discourseResponse = $discourseManager->bridgeCall($account, $discourse_endpoint, $request->getMethod(), $params, $urlParams, $fileData);
        }catch (HttpException $e){
            throw new HttpException($e->getStatusCode(), $e->getMessage());
        }catch (\Exception $e){
            throw new HttpException(500, $e->getMessage());
        }

        switch ($request->getMethod()){
            case 'PUT':
                $statusCode = 204;
                break;
            case 'POST':
                $statusCode = 201;
                break;
            default:
                $statusCode = 200;
                break;
        }

        return $this->rest($statusCode, 'success', 'Request successful', $discourseResponse);
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
