<?php

namespace App\FinancialApiBundle\Controller\Management\System;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use App\FinancialApiBundle\Entity\DbWallet;

/**
 * Class DbWalletController
 * @package App\FinancialApiBundle\Controller\Management\System
 */
class DbWalletController extends BaseApiController
{

    function getRepositoryName()
    {
        return "FinancialApiBundle:DbWallet";
    }

    function getNewEntity()
    {
        return new DbWallet();
    }

    /**
     * @Rest\View()
     */
    public function indexAction(Request $request) {

        return parent::indexAction($request);
    }

    /**
     * @Rest\View()
     */

    public function createAction(Request $request){

        if(!$request->request->has('credentials')) throw new HttpException(404, 'Parameter credentials not found');

        $credentials = $request->request->get('credentials');

        $encrypted_credentials = $this->encryptCredentials($credentials);

        $request->request->remove('credentials');
        $request->request->add(array('credentials'  =>  $encrypted_credentials));

        return parent::createAction($request);
    }

    /**
     * @Rest\View()
     */
    public function updateAction(Request $request, $id){

        if($request->request->has('credentials')){
            $credentials = $request->request->get('credentials');

            $encrypted_credentials = $this->encryptCredentials($credentials);

            $request->request->remove('credentials');
            $request->request->add(array('credentials'  =>  $encrypted_credentials));

        }

        return parent::updateAction($request, $id);
    }

    /**
     * @Rest\View()
     */
    public function deleteAction($id){

        return parent::deleteAction($id);
    }

    private function encryptCredentials($credentials){

        $encrypted_credentials = array();

        foreach($credentials as $credential => $value){

            $enc = MCRYPT_RIJNDAEL_128;
            $key = $this->container->getParameter('encryption_secret');
            $mode = MCRYPT_MODE_CBC;
            $iv = mcrypt_create_iv(mcrypt_get_iv_size($enc, $mode), MCRYPT_DEV_URANDOM);
            $crypt = mcrypt_encrypt($enc, $key, $value, $mode, $iv);

            $encrypted_credentials[$credential] = base64_encode($crypt);

        }

        return $encrypted_credentials;


    }
}
