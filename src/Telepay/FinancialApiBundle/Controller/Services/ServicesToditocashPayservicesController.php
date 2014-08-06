<?php

namespace Telepay\FinancialApiBundle\Controller\Services;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;
use ToditoCash;


class ServicesToditocashPayservicesController extends FosRestController
{

    public $contract_id=2801;
    public $branch_id='test';

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
     *          "name"="transaction_id",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="Unique transaction id"
     *      },
     *      {
     *          "name"="date",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="YYYY-MM-DD",
     *          "description"="Transaction date."
     *      },
     *      {
     *          "name"="hour",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="HH:MM:SS",
     *          "description"="Transaction hour."
     *      },
     *      {
     *          "name"="card_number",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Card Number"
     *      },
     *      {
     *          "name"="nip",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="nip->Personal number."
     *      },
     *      {
     *          "name"="amount",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="Transaction amount."
     *      },
     *      {
     *          "name"="concept",
     *          "dataType"="string",
     *          "required"="false",
     *          "description"="Optional transaction description."
     *      },
     *      {
     *          "name"="currency",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="ISO-4217. f.e->MXN."
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function request(Request $request){

        static $paramNames = array(
            'transaction_id',
            'date',
            'hour',
            'card_number',
            'nip',
            'amount',
            'concept',
            'currency'
        );

        //Get the parameters sent by POST and put them in a $params array
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->get($paramName, 'null');
        }

        //Include the class
        include("../vendor/toditocash/ToditoCash.php");

        $mode=$request->get('mode');
        if(!isset($mode))   $mode='P';

        //Constructor
        $constructor=new ToditoCash($this->contract_id,$this->branch_id);

        if($mode=='T'){
            //Request method
            $datos=$constructor -> request($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6],$params[7],'0');
        }elseif($mode=='P'){
            //Request method
            $datos=$constructor -> request($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6],$params[7],'1');
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Bad request');
        }

       //print_r(json_encode($datos));
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
     *   section="Todito Cash",
     *   description="Returns an array with the status",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="transaction_id",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="Unique transaction id"
     *      },
     *      {
     *          "name"="tc_number_transaction",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="TC Transaction number"
     *      },
     *      {
     *          "name"="date",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="YYYY-MM-DD",
     *          "description"="Transaction date."
     *      },
     *      {
     *          "name"="hour",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="HH:MM:SS",
     *          "description"="Transaction hour."
     *      },
     *      {
     *          "name"="card_number",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Card Number"
     *      },
     *      {
     *          "name"="amount",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="Transaction amount."
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function reverso(Request $request){

        static $paramNames = array(
            'tc_number_transaction',
            'transaction_id',
            'date',
            'hour',
            'card_number',
            'amount'
        );

        //Get the parameters and put them in a $params array
        $params = array();
        foreach($paramNames as $paramName){
            $params[]=$request->get($paramName, 'null');
        }

        //Include the class
        include("../vendor/toditocash/ToditoCash.php");

        $mode=$request->get('mode');
        if(!isset($mode))   $mode='P';

        //Constructor
        $constructor=new ToditoCash($this->contract_id,$this->branch_id);

        if($mode=='T'){
            //Reverso test method
            $datos=$constructor -> reverso($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],'0');
        }elseif($mode=='P'){
            //Reverso production method
            $datos=$constructor -> reverso($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],'1');
        }

        $resp = new ApiResponseBuilder(
            201,
            "Reference created successfully",
            $datos
        );

        $view = $this->view($resp, 201);

        return $this->handleView($view);
    }

}