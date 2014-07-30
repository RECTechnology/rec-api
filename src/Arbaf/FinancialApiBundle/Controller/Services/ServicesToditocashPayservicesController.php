<?php

namespace Arbaf\FinancialApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;


class ServicesToditocashPayservicesController extends FosRestController
{
    /**
     * This method allows client to pay services with his own code.
     *
     * @ApiDoc(
     *   section="Todito Cash",
     *   description="Returns an array with the response",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="idContrato",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="ID Cliente"
     *      },
     *      {
     *          "name"="idTrans",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="ID Transacción"
     *      },
     *      {
     *          "name"="idSucursal",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="ID del punto de venta"
     *      },
     *      {
     *          "name"="fecha",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="YYYY-MM-DD",
     *          "description"="Transaction date."
     *      },
     *      {
     *          "name"="hora",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="HH:MM:SS",
     *          "description"="Transaction hour."
     *      },
     *      {
     *          "name"="numTarjeta",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Card Number"
     *      },
     *      {
     *          "name"="nip",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="nip."
     *      },
     *      {
     *          "name"="monto",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="Monto de la compra."
     *      },
     *      {
     *          "name"="concepto",
     *          "dataType"="string",
     *          "required"="false",
     *          "description"="Optional transaction description."
     *      },
     *      {
     *          "name"="divisa",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="MXN."
     *      },
     *      {
     *          "name"="banProd",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="0 - Production , 1 - Test."
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function requestAction(){


    }

    /**
     * This method allows client to know the status reference.
     *
     * @ApiDoc(
     *   section="Todito Cash",
     *   description="Returns an array with the status",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="idContrato",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="ID Cliente"
     *      },
     *      {
     *          "name"="idTrans",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="ID Transacción"
     *      },
     *      {
     *          "name"="idSucursal",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="ID del punto de venta"
     *      },
     *      {
     *          "name"="noTransaccionTC",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="TC Transaction number"
     *      },
     *      {
     *          "name"="fecha",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="YYYY-MM-DD",
     *          "description"="Transaction date."
     *      },
     *      {
     *          "name"="hora",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="HH:MM:SS",
     *          "description"="Transaction hour."
     *      },
     *      {
     *          "name"="numTarjeta",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Card Number"
     *      },
     *      {
     *          "name"="banProd",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="0 - Production , 1 - Test."
     *      },
     *      {
     *          "name"="monto",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="Monto de la compra."
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function reversoAction(){

    }

}