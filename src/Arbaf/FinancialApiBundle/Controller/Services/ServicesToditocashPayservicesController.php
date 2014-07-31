<?php

namespace Arbaf\FinancialApiBundle\Controller;

use Arbaf\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use ToditoCash;

        class A{
            private $a='a';
        }


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
     *          "name"="badge",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="ISO-4217. f.e->MXN."
     *      },
     *      {
     *          "name"="production_flag",
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

        static $paramNames = array(
            'transaction_id',
            'date',
            'hour',
            'card_number',
            'nip',
            'amount',
            'concept',
            'badge',
            'production_flag'
        );

        //Get the parameters sent by POST and put them in a $params array
        $request=$this->get('request_stack')->getCurrentRequest();
        $params = array();
        foreach($paramNames as $paramName){
            $params[]=$request->get($paramName, 'null');
        }

        //Include the class
        include("../vendor/toditocash/ToditoCash.php");

        //Constructor
        $constructor=new ToditoCash($this->contract_id,$this->branch_id);

        //Info method
        $datos=$constructor -> request($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6],$params[7],$params[8]);
        var_dump($datos);
       //print_r(json_encode($datos));
        $resp = new ApiResponseBuilder(
            201,
            "Reference created successfully",
            new A()
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
     *          "name"="banProd",
     *          "dataType"="int",
     *          "required"="true",
     *          "description"="0 - Production , 1 - Test."
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

    public function reversoAction(){

        static $paramNames = array(
            'tc_number_transaction',
            'transaction_id',
            'date',
            'hour',
            'card_number',
            'amount',
            'production_flag'
        );

        //Get the parameters and put them in a $params array
        $request=$this->get('request_stack')->getCurrentRequest();
        $params = array();
        foreach($paramNames as $paramName){
            $params[]=$request->get($paramName, 'null');
        }

        //Include the class
        include("../vendor/toditocash/ToditoCash.php");

        //Constructor
        $constructor=new ToditoCash($this->contract_id,$this->branch_id);

        //Reverso method
        $datos=$constructor -> reverso($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6]);

        $resp = new ApiResponseBuilder(
            201,
            "Reference created successfully",
            $datos
        );

        $view = $this->view($resp, 201);

        return $this->handleView($view);
    }

}