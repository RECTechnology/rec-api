<?php

namespace App\FinancialApiBundle\Controller\Management\Company;

use Symfony\Component\HttpFoundation\File\File;
use App\FinancialApiBundle\DependencyInjection\App\Commons\UploadManager;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Exception;
use Rhumsaa\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Entity\Offer;
use App\FinancialApiBundle\Controller\BaseApiController;

class OfferController extends BaseApiController{
    function getRepositoryName()
    {
        return "FinancialApiBundle:Offer";
    }

    function getNewEntity()
    {
        return new Offer();
    }

    /**
     * @Rest\View
     */
    public function indexOffersV4(Request $request){
        $user = $this->getUser();
        $company = $user->getActiveGroup();
        $em = $this->getDoctrine()->getManager();
        $offers = $em->getRepository('FinancialApiBundle:Offer')->findBy(array(
            'company'   =>  $company
        ),[
            'created' => 'DESC'
        ]);
        return $this->restV2(200, 'ok', 'Request successfull', $offers);
    }

    /**
     * @Rest\View
     */
    public function updateOfferFromCompanyV4(Request $request, $offer_id){
        $em = $this->getDoctrine()->getManager();
        /** @var Offer $offer */
        $offer = $em->getRepository('FinancialApiBundle:Offer')->find($offer_id);
        if($offer->getCompany()->getId() != $this->getUser()->getActiveGroup()->getId() )
            throw new HttpException(403, 'You don\'t have the necessary permissions');

        if(!$offer) throw new HttpException(404, 'Offer not found');

        if($request->request->has('end')){
            $end = date_create($request->request->get('end'));
            $offer->setEnd($end);
        }

        if($request->request->has('description')){
            $offer->setDescription($request->request->get('description'));
        }

        if($request->request->has('type')){
            $this->checkValidOfferType($request->request->get('type'));
            $offer->setType($request->request->get('type'));
        }

        if($offer->getType() == Offer::OFFER_TYPE_CLASSIC){
            $initial_price = $request->request->get('initial_price');
            $offer_price = $request->request->get('offer_price');
            if($initial_price == 0 || $initial_price == null) throw new HttpException(400, 'Param initial price cannot be null or 0');
            if($offer_price == 0 || $offer_price == null) throw new HttpException(400, 'Param offer price cannot be null or 0');
            $offer->setInitialPrice($initial_price);
            $offer->setOfferPrice($offer_price);
            $offer->setDiscount($this->calculateDiscount($initial_price, $offer_price));
        }elseif ($offer->getType() == Offer::OFFER_TYPE_PERCENTAGE){
            $offer->setDiscount($request->request->get('discount'));
            $offer->setInitialPrice(null);
            $offer->setOfferPrice(null);
        }elseif ($offer->getType() == Offer::OFFER_TYPE_FREE){
            $offer->setDiscount(null);
            $offer->setInitialPrice(null);
            $offer->setOfferPrice(null);
        }

        $em->flush();
        return $this->restV2(200, 'ok', 'Offer updated successfully');
    }

    /**
     * @Rest\View
     */
    public function deleteActionV4($offer_id){
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $offer = $em->getRepository('FinancialApiBundle:Offer')->findOneBy(array(
            'id'    =>  $offer_id,
            'company' =>  $user->getActiveGroup()
        ));

        if(!$offer) throw new HttpException(404, 'Offer not found');

        return parent::deleteAction($offer_id);

    }

    /**
     * @Rest\View
     */
    public function registerOfferV4(Request $request)
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $group = $user->getActiveGroup();

        $paramNames = array(
            'end',
            'description',
            'type'
        );

        $params = array();
        foreach ($paramNames as $paramName) {
            if ($request->request->has($paramName)) {
                $params[$paramName] = $request->request->get($paramName);
            } else {
                throw new HttpException(404, 'Param ' . $paramName . ' not found');
            }
        }

        $this->checkValidOfferType($params["type"]);
        $params = $this->checkParamsDependingOnOfferType($params, $request);

        $end = date_create($params['end']);

        $em = $this->getDoctrine()->getManager();
        $offer = new Offer();
        $offer->setCompany($group);
        $offer->setEnd($end);
        $offer->setDiscount($params['discount'] ?? null);
        $offer->setInitialPrice($params['initial_price'] ?? null);
        $offer->setOfferPrice($params['offer_price'] ?? null);
        $offer->setDescription($params['description']);
        $offer->setType($params['type']);
        $offer->setActive(true);

        if($request->request->has('image')){

            if($request->request->get('image') != null){
                $params['image'] = $request->request->get('image');
                $fileManager = $this->get('file_manager');

                $fileSrc = $params['image'];
                $fileContents = $fileManager->readFileUrl($fileSrc);
                $hash = $fileManager->getHash();
                $explodedFileSrc = explode('.', $fileSrc);
                $ext = $explodedFileSrc[count($explodedFileSrc) - 1];
                $filename = $hash . '.' . $ext;

                file_put_contents($fileManager->getUploadsDir() . '/' . $filename, $fileContents);
                $tmpFile = new File($fileManager->getUploadsDir() . '/' . $filename);

                if (!in_array($tmpFile->getMimeType(), UploadManager::$FILTER_IMAGES))
                    throw new HttpException(400, "Bad file type");

                $offer->setImage($fileManager->getFilesPath().'/'.$filename);
            }


        }

        $em->persist($offer);
        $em->flush();
        return $this->restV2(200, "ok", "Offer registered successfully", $offer);
    }

    private function checkValidOfferType($type){
        if(!in_array($type, Offer::OFFER_TYPES_ALL)){
            throw new HttpException(400, "Bad type");
        }
    }

    private function checkParamsDependingOnOfferType($params, Request $request){
        if($params['type'] == Offer::OFFER_TYPE_CLASSIC){
            $requiredParams = array(
                "initial_price",
                "offer_price"
            );

            foreach ($requiredParams as $requiredParam){
                if($request->request->has($requiredParam) && $request->request->get($requiredParam) != null){
                    $params[$requiredParam] = $request->request->get($requiredParam);
                }else{
                    throw new HttpException(404, 'Param ' . $requiredParam . ' required for type classic');
                }
            }

            $params['discount'] = $this->calculateDiscount($params['initial_price'], $params['offer_price']);

        }elseif ($params['type'] == Offer::OFFER_TYPE_PERCENTAGE){
            if($request->request->has('discount') && $request->request->get('discount') != null){
                $params['discount'] = $request->request->get('discount');
            }else{
                throw new HttpException(404, 'Param discount is required for type percentage');
            }
        }

        return $params;
    }

    private function calculateDiscount($initial_price, $offer_price){
        return ($initial_price - $offer_price)*100/$initial_price;
    }
}