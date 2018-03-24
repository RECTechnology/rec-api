<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Company;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Entity\Group;

class MapController {

    public function ListAction(Request $request){
        $total = 0;
        $all = array();
        $em = $this->getDoctrine()->getManager();

        $min_lat = -90.0;
        if($request->request->has('min_lat') && $request->request->get('min_lat')!='') {
            $min_lat = $request->request->get('min_lat');
        }

        $max_lat = 90.0;
        if($request->request->has('max_lat') && $request->request->get('max_lat')!='') {
            $max_lat = $request->request->get('max_lat');
        }

        $min_lon = -90.0;
        if($request->request->has('min_lon') && $request->request->get('min_lon')!='') {
            $min_lon = $request->request->get('min_lon');
        }

        $max_lon = 90.0;
        if($request->request->has('max_lon') && $request->request->get('max_lon')!='') {
            $max_lon = $request->request->get('max_lon');
        }

        $list_companies = $em->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
            'type'  =>  'COMPANY'
        ));

        foreach ($list_companies as $company){
            $lat = $company->getLatitude();
            $lon = $company->getLongitude();
            if($lat == 0 && $lon == 0) {
                //No han definido su ubicacion
            }
            elseif($lat > $min_lat && $lat < $max_lat && $lon > $min_lon && $lon < $max_lon){
                $total+=1;
                $all[] = array(
                    'name' => $company->getName(),
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'country' => $company->getCountry(),
                    'city' => $company->getCity(),
                    'zip' => $company->getZip(),
                    'street' => $company->getName(),
                    'address_number' => $company->getName(),
                    'phone' => $company->getPhone(),
                    'prefix' => $company->getPrefix(),
                    'company_image' => $company->getCompanyImage(),
                    'subtype' => $company->getSubtype()
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

        $list_companies = $em->getRepository('TelepayFinancialApiBundle:Group')->findOneBy(array(
            'type'  =>  'COMPANY'
        ));

        if($request->request->has('search') && $request->request->get('search')!='') {
            $search = $request->request->get('search');
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
            if (strpos($search, $name) !== false) {
                $total+=1;
                $all[] = array(
                    'name' => $company->getName(),
                    'latitude' => $company->getLatitude(),
                    'longitude' => $company->getLongitude(),
                    'country' => $company->getCountry(),
                    'city' => $company->getCity(),
                    'zip' => $company->getZip(),
                    'street' => $company->getName(),
                    'address_number' => $company->getName(),
                    'phone' => $company->getPhone(),
                    'prefix' => $company->getPrefix(),
                    'company_image' => $company->getCompanyImage(),
                    'subtype' => $company->getSubtype()
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