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

        $only_offers = false;
        if($request->query->has('only_offers') && $request->query->get('only_offers')=='1') {
            $only_offers = true;
        }

        $where = array('type'  =>  'COMPANY');
        if($request->query->has('retailer') && $request->query->get('retailer')=='1') {
            $where['subtype'] = 'RETAILER';
        }
        if($request->query->has('wholesale') && $request->query->get('wholesale')=='1') {
            if(isset($where['subtype'])){
                unset($where['subtype']);
            }
            else {
                $where['subtype'] = 'WHOLESALE';
            }
        }

        if($request->query->get('retailer')=='0' && $request->query->get('wholesale')=='0') {
            throw new HttpException(400, "Filters options are incorrect");
        }

        $list_cat_ids = array();
        $search_defined = false;
        if($request->query->has('search') && $request->query->get('search')!='') {
            $search = strtoupper($request->query->get('search'));
            $search_defined = true;

            $list_categories = $em->getRepository('TelepayFinancialApiBundle:Category')->findAll();
            foreach ($list_categories as $category) {
                if (strpos($category->getCat(), $search) !== false || strpos($category->getEsp(), $search) !== false || strpos($category->getEng(), $search) !== false) {
                    $list_cat_ids[] = $category->getId();
                }
            }
        }

        $list_companies = $em->getRepository('TelepayFinancialApiBundle:Group')->findBy($where);

        foreach ($list_companies as $company){
            $lat = $company->getLatitude();
            $lon = $company->getLongitude();
            $name = strtoupper($company->getName());
            $category_id = strtoupper($company->getCategory()->getId());
            if(intval($lat) == 0 && intval($lon) == 0) {
                //No han definido su ubicacion
            }
            elseif ($search_defined && (strpos($name, $search) !== true || in_array($category_id, $list_cat_ids))) {
                //No cumple el search
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
                if(!$only_offers || $total_offers>0){
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
                        'street_type' => $company->getStreetType(),
                        'address_number' => $company->getAddressNumber(),
                        'phone' => $company->getPhone(),
                        'prefix' => $company->getPrefix(),
                        'type' => $company->getType(),
                        'subtype' => $company->getSubtype(),
                        'description' => $company->getDescription(),
                        'schedule' => $company->getSchedule(),
                        'public_image' => $company->getPublicImage(),
                        'category' => $company->getCategory(),
                        'offers' => $offers,
                        'total_offers' => $total_offers
                    );
                }
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

        $where = array('type'  =>  'COMPANY');
        if($request->query->has('retailer') && $request->query->get('retailer')=='1') {
            $where['subtype'] = 'RETAILER';
        }
        if($request->query->has('wholesale') && $request->query->get('wholesale')=='1') {
            if(isset($where['subtype'])){
                unset($where['subtype']);
            }
            else {
                $where['subtype'] = 'WHOLESALE';
            }
        }
        if($request->query->get('retailer')=='0' && $request->query->get('wholesale')=='0') {
            throw new HttpException(400, "Filters options are incorrect");
        }

        $list_companies = $em->getRepository('TelepayFinancialApiBundle:Group')->findBy($where);

        if($request->query->has('search') && $request->query->get('search')!='') {
            $search = strtoupper($request->query->get('search'));
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

        $list_categories = $em->getRepository('TelepayFinancialApiBundle:Category')->findAll();
        $list_cat_ids = array();
        foreach ($list_categories as $category) {
            if (strpos($category->getCat(), $search) !== false || strpos($category->getEsp(), $search) !== false || strpos($category->getEng(), $search) !== false) {
                $list_cat_ids[] = $category->getId();
            }
        }

        $only_offers = false;
        if($request->query->has('only_offers') && $request->query->get('only_offers')=='1') {
            $only_offers = true;
        }

        foreach ($list_companies as $company){
            $lat = $company->getLatitude();
            $lon = $company->getLongitude();
            if(intval($lat) == 0 && intval($lon) == 0) {
                //No han definido su ubicacion
            }
            else{
                $name = strtoupper($company->getName());
                $category_id = strtoupper($company->getCategory()->getId());
                if (strpos($name, $search) !== false || in_array($category_id, $list_cat_ids)) {
                    //check offers
                    $list_offers = $em->getRepository('TelepayFinancialApiBundle:Offer')->findBy(array(
                        'company'  =>  $company
                    ));
                    $now = strtotime("now");
                    $offers_info = array();
                    $total_offers = 0;
                    foreach($list_offers as $offer){
                        $start = date_timestamp_get($offer->getStart());
                        if($start <= $now){
                            $end = date_timestamp_get($offer->getEnd());
                            if($now <= $end){
                                $offers_info[]=$offer;
                                $total_offers+=1;
                            }
                        }
                    }
                    if(!$only_offers || $total_offers>0){
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
                            'street_type' => $company->getStreetType(),
                            'address_number' => $company->getAddressNumber(),
                            'phone' => $company->getPhone(),
                            'prefix' => $company->getPrefix(),
                            'type' => $company->getType(),
                            'subtype' => $company->getSubtype(),
                            'description' => $company->getDescription(),
                            'schedule' => $company->getSchedule(),
                            'public_image' => $company->getPublicImage(),
                            'category' => $company->getCategory(),
                            'offers' => $offers_info,
                            'total_offers' => $total_offers
                        );
                    }
                }
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