<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\Offer;

class MapController extends BaseApiController{

    function getRepositoryName()
    {
        return "TelepayFinancialApiBundle:Group";
    }

    function getNewEntity()
    {
        return new Group();
    }

    public function ListAction(Request $request){
        $total = 0;
        $all = array();
        $em = $this->getDoctrine()->getManager();

        $min_lat = -90.0;
        if($request->query->has('min_lat') && $request->query->get('min_lat')!='') {
            $min_lat = $request->query->get('min_lat');
        }

        $max_lat = 90.0;
        if($request->query->has('max_lat') && $request->query->get('max_lat')!='') {
            $max_lat = $request->query->get('max_lat');
        }

        $min_lon = -90.0;
        if($request->query->has('min_lon') && $request->query->get('min_lon')!='') {
            $min_lon = $request->query->get('min_lon');
        }

        $max_lon = 90.0;
        if($request->query->has('max_lon') && $request->query->get('max_lon')!='') {
            $max_lon = $request->query->get('max_lon');
        }

        $list_companies = $em->getRepository('TelepayFinancialApiBundle:Group')->findBy(array(
            'type'  =>  'COMPANY'
        ));

        foreach ($list_companies as $company){
            $lat = $company->getLatitude();
            $lon = $company->getLongitude();
            if($lat == 0 && $lon == 0) {
                //No han definido su ubicacion
            }
            elseif($lat > $min_lat && $lat < $max_lat && $lon > $min_lon && $lon < $max_lon){
                //check offers
                $list_offers = $em->getRepository('TelepayFinancialApiBundle:Offer')->findBy(array(
                    'company'  =>  $company
                ));
                $now = strtotime("now");
                $offers = array();
                $total_offers = 0;
                foreach($list_offers as $offer){
                    $start = date_timestamp_get($offer->getStart());
                    if($start < $now){
                        $end = date_timestamp_get($offer->getEnd());
                        if($now < $end){
                            $offers[]=$offer;
                            $total_offers+=1;
                        }
                    }
                }

                $total+=1;
                $all[] = array(
                    'name' => $company->getName(),
                    'company_image' => $company->getCompanyImage(),
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'country' => $company->getCountry(),
                    'city' => $company->getCity(),
                    'zip' => $company->getZip(),
                    'street' => $company->getStreet(),
                    'address_number' => $company->getAddressNumber(),
                    'phone' => $company->getPhone(),
                    'prefix' => $company->getPrefix(),
                    'type' => $company->getType(),
                    'subtype' => $company->getSubtype(),
                    'description' => $company->getDescription(),
                    'schedule' => $company->getSchedule(),
                    'public_image' => $company->getPublicImage(),
                    'offers' => $offers,
                    'total_offers' => $total_offers
                );
            }
        }


        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'elements' => $all
            )
        );
    }

    public function SearchAction(Request $request){
        $total = 0;
        $all = array();
        $em = $this->getDoctrine()->getManager();

        $list_companies = $em->getRepository('TelepayFinancialApiBundle:Group')->findBy(array(
            'type'  =>  'COMPANY'
        ));

        if($request->query->has('search') && $request->query->get('search')!='') {
            $search = $request->query->get('search');
        }
        else{
            return $this->restV2(
                200,
                "ok",
                "Request successful",
                array(
                    'total' => $total,
                    'elements' => $all
                )
            );
        }

        foreach ($list_companies as $company){
            $name = $company->getName();
            if (strpos($name, $search) !== false) {
                //check offers
                $list_offers = $em->getRepository('TelepayFinancialApiBundle:Offer')->findBy(array(
                    'company'  =>  $company
                ));
                $now = strtotime("now");
                $offers = array();
                $total_offers = 0;
                foreach($list_offers as $offer){
                    $start = date_timestamp_get($offer->getStart());
                    if($start <= $now){
                        $end = date_timestamp_get($offer->getEnd());
                        if($now <= $end){
                            $offers[]=$offer;
                            $total_offers+=1;
                        }
                    }
                }
                $total+=1;
                $all[] = array(
                    'name' => $company->getName(),
                    'company_image' => $company->getCompanyImage(),
                    'latitude' => $company->getLatitude(),
                    'longitude' => $company->getLongitude(),
                    'country' => $company->getCountry(),
                    'city' => $company->getCity(),
                    'zip' => $company->getZip(),
                    'street' => $company->getStreet(),
                    'address_number' => $company->getAddressNumber(),
                    'phone' => $company->getPhone(),
                    'prefix' => $company->getPrefix(),
                    'type' => $company->getType(),
                    'subtype' => $company->getSubtype(),
                    'description' => $company->getDescription(),
                    'schedule' => $company->getSchedule(),
                    'public_image' => $company->getPublicImage(),
                    'offers' => $offers,
                    'total_offers' => $total_offers
                );
            }
        }


        return $this->restV2(
            200,
            "ok",
            "Request successful",
            array(
                'total' => $total,
                'elements' => $all
            )
        );
    }
}