<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Symfony\Component\HttpFoundation\File\File;
use Telepay\FinancialApiBundle\DependencyInjection\Telepay\Commons\UploadManager;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Exception;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\Offer;
use Telepay\FinancialApiBundle\Controller\BaseApiController;

class OfferController extends BaseApiController{
    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:Offer";
    }

    function getNewEntity()
    {
        return new Offer();
    }
    /**
     * @Rest\View
     */
    public function registerOffer(Request $request)
    {
        $user = $this->get('security.context')->getToken()->getUser();
        $group = $this->get('security.context')->getToken()->getUser()->getActiveGroup();

        $paramNames = array(
            'start',
            'end',
            'discount',
            'description',
            'image'
        );

        $params = array();
        foreach ($paramNames as $paramName) {
            if ($request->request->has($paramName)) {
                $params[$paramName] = $request->request->get($paramName);
            } else {
                throw new HttpException(404, 'Param ' . $paramName . ' not found');
            }
        }

        $fileManager = $this->get('file_manager');

        $fileSrc = $params['image'];
        $fileContents = $fileManager->readFileUrl($fileSrc);
        $hash = $fileManager->getHash();
        $explodedFileSrc = explode('.', $fileSrc);
        $ext = $explodedFileSrc[count($explodedFileSrc) - 1];
        $filename = $hash . '.' . $ext;

        file_put_contents($fileManager->getUploadsDir() . '/' . $filename, $fileContents);

        $tmpFile = new File($fileManager->getUploadsDir() . '/' . $filename);
        if (!in_array($tmpFile->getMimeType(), UploadManager::$ALLOWED_MIMETYPES))
            throw new HttpException(400, "Bad file type");

        $start = date_create($params['start']);
        $end = date_create($params['end']);

        $em = $this->getDoctrine()->getManager();
        $offer = new Offer();
        $offer->setCompany($group);
        $offer->setStart($start);
        $offer->setEnd($end);
        $offer->setDiscount($params['discount']);
        $offer->setDescription($params['description']);
        $offer->setImage($fileManager->getFilesPath().'/'.$filename);

        $em->persist($offer);
        $em->flush();
        return $this->restV2(201, "ok", "Offer registered successfully", $offer);
    }

    /**
     * @Rest\View
     */
    public function indexOffers(Request $request){
        $user = $this->getUser();
        $company = $user->getActiveGroup();
        $em = $this->getDoctrine()->getManager();
        $offers = $em->getRepository('TelepayFinancialApiBundle:Offer')->findBy(array(
            'company'   =>  $company
        ));
        return $this->restV2(200, 'ok', 'Request successfull', $offers);
    }

    /**
     * @Rest\View
     */
    public function updateOfferFromCompany(Request $request, $id){
        $em = $this->getDoctrine()->getManager();
        $offer = $em->getRepository('TelepayFinancialApiBundle:Offer')->find($id);
        if($offer->getCompany()->getId() != $this->getUser()->getActiveGroup()->getId() )
            throw new HttpException(403, 'You don\'t have the necessary permissions');

        if(!$offer) throw new HttpException(404, 'Offer not found');

        if($request->request->has('end')){
            $offer->setEnd($request->request->get('end'));
        }
        if($request->request->has('start')){
            $offer->setStart($request->request->get('start'));
        }
        if($request->request->has('discount')){
            $offer->setDiscount($request->request->get('discount'));
        }
        if($request->request->has('description')){
            $offer->setDescription($request->request->get('description'));
        }
        $em->flush();
        return $this->restV2(204, 'ok', 'Offer updated successfully');
    }

    /**
     * @Rest\View
     */
    public function deleteAction($id){
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $offer = $em->getRepository('TelepayFinancialApiBundle:Offer')->findOneBy(array(
            'id'    =>  $id,
            'company' =>  $user->getActiveGroup()
        ));

        if(!$offer) throw new HttpException(404, 'Offer not found');

        return parent::deleteAction($id);

    }
}