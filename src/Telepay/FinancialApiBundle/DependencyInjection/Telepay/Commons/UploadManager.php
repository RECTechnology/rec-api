<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/8/16
 * Time: 7:56 PM
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons;

use Symfony\Component\DependencyInjection\ContainerInterface;

class UploadManager {

    private $container;

    public static $ALLOWED_MIMETYPES = array('image/png', 'image/jpeg', 'application/pdf');
//    const ALLOWED_MIMETYPES = array('image/png', 'image/jpeg', 'application/pdf');

    /**
     * FileUtils constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getUploadsDir(){
        return $this->container->getParameter('uploads_dir');
    }

    public function getFilesPath(){
        return $this->container->getParameter('files_path');
    }

    public function getHash(){
        return uniqid();
    }

    public function readFileUrl($path){
        $ctxOptions=array(
            "ssl"=>array(
                "cafile" => $this->container->get('kernel')->getRootDir() . "/Resources/config/curl/cacert.pem",
                "verify_peer"=> false, //TODO: check security implications
                //"verify_peer_name"=> true,
            )
        );
        return file_get_contents($path, null, stream_context_create($ctxOptions));

    }

}