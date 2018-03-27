<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Exception;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\Offer;
use Telepay\FinancialApiBundle\Entity\User;
use Telepay\FinancialApiBundle\Entity\UserGroup;

class OfferController extends RestApiController{

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

        $em = $this->getDoctrine()->getManager();
        $offer = new Offer();
        $offer->setCompany($group);
        $offer->setStart($params['start']);
        $offer->setEnd($params['end']);
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
        $em->flush();
        return $this->restV2(204, 'ok', 'Offer updated successfully');
    }
}