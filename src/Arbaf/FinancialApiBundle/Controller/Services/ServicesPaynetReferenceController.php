<?php

namespace Arbaf\FinancialApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;


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
     *          "name"="issuerCod",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="issuer Code for the reference generation. Ex: '000aaaa0-0000-0000-aa00-aaa0a00000aa'"
     *      },
     *      {
     *          "name"="clientReference",
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
     *          "name"="dueDate",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="YYYY-MM-DD",
     *          "description"="Expiration date for the payment."
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
     *          "name"="issuerCod",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="issuer Code for the reference generation. Ex:'000aaaa0-0000-0000-aa00-aaa0a00000aa'"
     *      },
     *      {
     *          "name"="clientReference",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Pay/Product reference/identifier. (max 12 chars).Ex: '000000000000'"
     *      }
     *   },
     *   output="Arbaf\FinancialApiBundle\Controller\Response"
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function statusAction(){

    }

} 