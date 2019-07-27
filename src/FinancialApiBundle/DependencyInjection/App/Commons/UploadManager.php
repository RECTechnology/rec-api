<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/8/16
 * Time: 7:56 PM
 */

namespace App\FinancialApiBundle\DependencyInjection\App\Commons;

use JMS\Serializer\Exception\ValidationFailedException;
use LogicException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraints\UrlValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UploadManager {

    public static $FILTER_IMAGES = [
        "image/png",
        "image/jpg",
        "image/jpeg",
        "image/svg",
        "image/gif"
    ];
    public static $FILTER_DOCUMENTS = [
        "image/png",
        "image/jpg",
        "image/jpeg",
        "image/svg",
        "image/gif",
        'application/pdf',
        "text/plain",
        "text/csv",
        "application/xml",
    ];

    public static function allMimeTypes(){
        return array_merge(static::$FILTER_IMAGES, static::$FILTER_DOCUMENTS);
    }

    private $container;

    /**
     * FileUtils constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return string
     */
    public function getUploadsDir(){
        return $this->container->getParameter('uploads_dir');
    }

    /**
     * @return string
     */
    public function getFilesPath(){
        return $this->container->getParameter('files_path');
    }

    /**
     * @return string
     */
    public function getHash(){
        return uniqid();
    }

    /**
     * @param $contents
     * @param array $mime_types
     * @return string
     */
    public function saveFile($contents, $mime_types = []) {
        if ($mime_types == []) $mime_types = static::allMimeTypes();

        $tmpFileName = $this->getUploadsDir() . "/" . $this->getHash();
        file_put_contents($tmpFileName, $contents);
        $tmpFile = new File($tmpFileName);
        if (in_array($tmpFile->getMimeType(), $mime_types)) {
            $newFileName = $this->getHash() . "." . $tmpFile->guessExtension();
            $tmpFile->move($this->getUploadsDir(), $newFileName);
            return $this->getFilesPath() . "/" . $newFileName;
        }
        unlink($tmpFileName);
        throw new LogicException("FileType not allowed");
    }

    /**
     * @param $path
     * @return mixed
     */
    public function readFileUrl($path){

        $validator = new UrlValidator();
        $constraint = new Url();
        $constraint->protocols = ["http", "https"];
        try {
            $validator->validate($path, $constraint);
        } catch (\Throwable $t){
            throw new LogicException("Invalid url");
        }
        return file_get_contents($path);
    }

}