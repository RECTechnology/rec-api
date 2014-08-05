<?php

namespace Telepay\FinancialApiBundle\Controller\Services;

use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use PaynetGetBarcode;
use PaynetGetStatus;
use Symfony\Component\HttpKernel\Exception\HttpException;



class ServicesPaynetReferenceController extends FosRestController
{
    /**
     * This method allows client to get a barcode with the reference for the payment.
     *
     * @ApiDoc(
     *   section="Paynet Reference",
     *   description="Returns a pdf file with the barcode and the instructions",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="client_reference",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Pay/Product reference/identifier. (max 12 chars).Ex: '000000000000'"
     *      },
     *      {
     *          "name"="amount",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Allowed mount. Ex: '00001000'"
     *      },
     *      {
     *          "name"="description",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Product description. Ex: 'television'"
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function generateAction(){

        static $paramNames = array(
            'client_reference',
            'amount',
            'description'
        );

        //Get the parameters sent by POST
        $request=$this->get('request_stack')->getCurrentRequest();
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->get($paramName, 'null');
        }

        //Include the class
        include("../vendor/paynet-barcode/PaynetGetBarcode.php");

        //Constructor
        $constructor=new PaynetGetBarcode($params[0],$params[1],$params[2]);

        //Request method
        $datos=$constructor -> request();

        $resp = new ApiResponseBuilder(
            201,
            "Reference created successfully",
            $datos
        );

        $view = $this->view($resp, 201);

        return $this->handleView($view);
    }

    /**
     * This method allows client to know the status reference.
     *
     * @ApiDoc(
     *   section="Paynet Reference",
     *   description="Returns an array with the status",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="client_reference",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Pay/Product reference/identifier. (max 12 chars).Ex: '000000000000'"
     *      }
     *   },
     *   output="Telepay\FinancialApiBundle\Controller\Response"
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function statusAction(){

        static $paramNames = array(
            'client_reference'
        );

        //Get the parameters sent by POST
        $request=$this->get('request_stack')->getCurrentRequest();
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->get($paramName, 'null');
        }

        //Include the class
        include("../vendor/paynet-barcode/PaynetGetStatus.php");

        //Constructor
        $constructor=new PaynetGetStatus($params[0]);

        //Status method
        $datos=$constructor -> status();

        //Response
        $resp = new ApiResponseBuilder(
            201,
            "Reference created successfully",
            $datos
        );

        $view = $this->view($resp, 201);

        return $this->handleView($view);

    }

} 