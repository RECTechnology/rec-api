<?php

/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/19/14
 * Time: 6:33 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Symfony\Component\HttpFoundation\File\File;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\UploadManager;
use Telepay\FinancialApiBundle\Entity\Group;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;

class AccountController extends BaseApiController{

    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:Group";
    }

    function getNewEntity()
    {
        return new Group();
    }


    /**
     * @Rest\View
     */
    public function setAdmin(Request $request, $id){

        $user = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        $company = $em->getRepository($this->getRepositoryName())->find($id);

        if(!$company) throw new HttpException(404, 'Company not found');

        $userGroup = $em->getRepository('TelepayFinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $user->getId(),
            'group' =>  $company->getId()
        ));

        if(!$userGroup) throw new HttpException(404, 'Change not allowed');

        if(!$userGroup->hasRole('ROLE_ADMIN')) throw new HttpException('You don\'t have the necessary permissions');

        $company->setKycManager($user);
        $em->flush();

        return $this->rest(204, 'Manager changed successfully');

    }

    /**
     * @Rest\View
     */
    public function setImage(Request $request, $group){

        $paramNames = array(
            'company_image'
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
        $user = $this->getUser();
        $company = $em->getRepository($this->getRepositoryName())->find($group);

        if(!$company) throw new HttpException('Company not found');

        $userGroup = $em->getRepository('TelepayFinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $user->getId(),
            'group' =>  $company->getId()
        ));
        if(!$userGroup->hasRole('ROLE_ADMIN')) throw new HttpException('You don\'t have the necessary permissions');

        $fileManager = $this->get('file_manager');

        $fileSrc = $params['company_image'];
        $fileContents = $fileManager->readFileUrl($fileSrc);

        //if has image overwrite...if not create filename
        if($company->getCompanyImage() == ''){
            $hash = $fileManager->getHash();
            $explodedFileSrc = explode('.', $fileSrc);
            $ext = $explodedFileSrc[count($explodedFileSrc) - 1];
            $filename = $hash . '.' . $ext;
        }else{
            $filename = str_replace($this->container->getParameter('files_path') . '/', '', $company->getCompanyImage());
        }

        file_put_contents($fileManager->getUploadsDir() . '/' . $filename, $fileContents);

        $tmpFile = new File($fileManager->getUploadsDir() . '/' . $filename);
        if (!in_array($tmpFile->getMimeType(), UploadManager::$ALLOWED_MIMETYPES))
            throw new HttpException(400, "Bad file type");

        $company->setCompanyImage($fileManager->getFilesPath().'/'.$filename);
        $em->flush();

        return $this->rest(204, 'Company image updated successfully');
    }

    /**
     * @Rest\View
     */
    public function setCategoriesAction(Request $request, $group){
        $paramNames = array(
            'list_categories'
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
        $user = $this->getUser();
        $company = $em->getRepository($this->getRepositoryName())->find($group);

        if(!$company) throw new HttpException('Company not found');
        $userGroup = $em->getRepository('TelepayFinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $user->getId(),
            'group' =>  $company->getId()
        ));
        if(!$userGroup->hasRole('ROLE_ADMIN')) throw new HttpException('You don\'t have the necessary permissions');

        $list = json_decode($params['list_categories']);
        foreach($list as $category){

        }

        $company->setCompanyImage();
        $em->flush();

        return $this->rest(204, 'Company image updated successfully');

    }

    /**
     * @Rest\View
     * Permissions: ROLE_ADMIN (all)
     */
    public function updateAction(Request $request, $account_id){

        $admin = $this->get('security.context')->getToken()->getUser();
        $adminGroup = $this->getRepository($this->getRepositoryName())->find($account_id);

        $adminRoles = $this->getDoctrine()->getRepository('TelepayFinancialApiBundle:UserGroup')->findOneBy(array(
            'user'  =>  $admin->getId(),
            'group' =>  $adminGroup->getId()
        ));

        if(!$adminRoles){
            throw new HttpException(403, 'You are not in this account');
        }

        if(!$adminRoles->hasRole('ROLE_ADMIN')) throw new HttpException(403, 'You don\'t have the necessary permissions');

        if($request->request->has('company_image') && $request->request->get('company_image')!='') {
            $em = $this->getDoctrine()->getManager();
            $fileManager = $this->get('file_manager');
            $fileSrc = $request->request->get('company_image');
            $fileContents = $fileManager->readFileUrl($fileSrc);
            $company = $em->getRepository($this->getRepositoryName())->find($account_id);
            //if has image overwrite...if not create filename
            if ($company->getCompanyImage() == '') {
                $hash = $fileManager->getHash();
                $explodedFileSrc = explode('.', $fileSrc);
                $ext = $explodedFileSrc[count($explodedFileSrc) - 1];
                $filename = $hash . '.' . $ext;
            } else {
                $filename = str_replace($this->container->getParameter('files_path') . '/', '', $company->getCompanyImage());
            }
            file_put_contents($fileManager->getUploadsDir() . '/' . $filename, $fileContents);
            $tmpFile = new File($fileManager->getUploadsDir() . '/' . $filename);
            if (!in_array($tmpFile->getMimeType(), UploadManager::$ALLOWED_MIMETYPES))
                throw new HttpException(400, "Bad file type");
            $company->setCompanyImage($fileManager->getFilesPath() . '/' . $filename);
            $em->flush();
        }
        $request->request->remove('company_image');

        //check some params that can't be modified from here
        $invalid_params = array(
            'creator_id',
            'group_creator_id',
            'access_key',
            'access_secret',
            'active',
            'tier'
        );

        $all = $request->request->all();
        foreach ($all as $key=>$value){
            if(in_array($key,$invalid_params)) throw new HttpException(403, 'You don\'t have the necessary permissions to change this params. Please check documentation');
        }

        return parent::updateAction($request, $account_id);

    }
}