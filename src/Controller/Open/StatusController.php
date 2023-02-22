<?php

namespace App\Controller\Open;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use App\Controller\RestApiController;
use App\Document\Transaction;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class StatusController extends RestApiController {

    /**
     * @Rest\View
     * @param Request $request
     * @return Response
     *
     * This function will return an encoded version of system health, according with the following bitmask:
     *   - RELATIONAL_DB = 0x1
     *   - NOT_RELATIONAL_DB = 0x2
     *   - BLOCKCHAIN_NODE = 0x4 # Deprecated, returns always WORKING
     * so, a fully working system will return "system_status": 7, and a fully down "system_status": 0
     */
    public function status(Request $request){

        $status = 0x7; // all online (111)
        $exceptions = [];

        try {
            /** @var EntityManagerInterface $em */
            $em = $this->getDoctrine()->getManager();
            if(!$em->getConnection()->isConnected())
                $em->getConnection()->connect();
        } catch (\Exception $e){
            $status ^= 0x1; // change lsb (001)
            $exceptions []= $e->getMessage();
        }

        try {
            /** @var DocumentManager $odm */
            $odm = $this->get('doctrine_mongodb')->getManager();
            if(!$odm->getConnection()->isConnected())
                $odm->getConnection()->connect();
        } catch (\Exception $e){
            $status ^= 0x2; // change middle-bit (010)
            $exceptions []= $e->getMessage();
        }

        return $this->rest(
            200,
            "ok",
            "Request successful",
            ["system_status" => $status, "exceptions" => $exceptions]
        );
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function info(Request $request){

        $path = $this->getParameter('kernel.project_dir') . "/composer.json";
        $composer_json = json_decode(file_get_contents($path));
        $version = $this->getParameter('version');
        $projectInfo = [
            'name' => $composer_json->name,
            'license' => $composer_json->license,
            'description' => $composer_json->description,
            'version' => $version,
        ];

        return $this->rest(
            200,
            "ok",
            "Request successful",
            $projectInfo
        );
    }

}