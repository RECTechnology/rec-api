<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 14/08/14
 * Time: 09:55
 */
namespace Telepay\FinancialApiBundle\Controller\Services;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use PagofacilService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ServicesPagofacilPaymentController extends FOSRestController
{

    //This parameters are unique for us. Don't give to the client
    private $testArray =array(
        'id_sucursal'   =>  '42ee3b415f4cebd37dffe881b929c0a0bac8a72c',
        'id_usuario'  =>  '12a27c9c912ec6b4175c3bb316365965a19f6d31',
        'id_servicio'  =>  '3'
    );

    //Para producción
    private $prodArray =array(
        'id_sucursal'   =>  '77cd297945a1b75979f742f183544e4867935777',
        'id_usuario'  =>  'd65a8ff620762e81c026f10b3d76752a7f32d46d',
        'id_servicio'  =>  '3'
    );

    /**
     * This method allows client to obtain info for the payment services.
     *
     * @ApiDoc(
     *   section="PagoFacil payment with Credit Card",
     *   description="Return a JSON with the response.",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="name",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Credit Card name."
     *      },
     *      {
     *          "name"="surname",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Credit Card surname."
     *      },
     *      {
     *          "name"="card_number",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Credit card number."
     *      },
     *      {
     *          "name"="cvt",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="3 digits in the reverse of credit card (usually)."
     *      },
     *      {
     *          "name"="cp",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Titular of credit card Postal Code."
     *      },
     *      {
     *          "name"="expiration_month",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="10",
     *          "description"="Expiration month for the credit card. 2 numbers"
     *      },
     *      {
     *          "name"="expiration_year",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="15",
     *          "description"="Expiration year for the credit card. Only last 2 numbers"
     *      },
     *      {
     *          "name"="amount",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Transaction amount. Ex:100.00"
     *      },
     *      {
     *          "name"="mail",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Mail."
     *      },
     *      {
     *          "name"="phone",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Phone number."
     *      },
     *      {
     *          "name"="mobile_phone",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Mobile phone number."
     *      },
     *      {
     *          "name"="street_number",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="The street and the number. Ex: C/ Geminis, 8"
     *      },
     *      {
     *          "name"="colony",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="District name. Ex: Polanco"
     *      },
     *      {
     *          "name"="city",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Village name. Ex: Miguel Hidalgo"
     *      },
     *      {
     *          "name"="quarter",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="State name. Ex: Distrito Federal"
     *      },
     *      {
     *          "name"="country",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Country name. Ex:Mexico"
     *      },
     *      {
     *          "name"="transaction_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Transaction ID, this number must be unique. It's responsability for the client."
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function transaction(Request $request){
        static $paramNames = array(
            'name',
            'surname',
            'card_number',
            'cvt',
            'cp',
            'expiration_month',
            'expiration_year',
            'amount',
            'mail',
            'phone',
            'mobile_phone',
            'street_number',
            'colony',
            'city',
            'quarter',
            'country',
            'transaction_id'
        );

        //Get the parameters sent by POST and put them in $params array
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->get($paramName, 'null');
        }
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Include the class
        include("../vendor/pagofacil/PagofacilService.php");

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Constructor in Test mode
            $constructor=new PagofacilService($this->testArray['id_sucursal'],$this->testArray['id_usuario'],$this->testArray['id_servicio']);
        }elseif($mode=='P'){
            //Constructor in Production mode
            $constructor=new PagofacilService($this->prodArray['id_sucursal'],$this->prodArray['id_usuario'],$this->prodArray['id_servicio']);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong request');
        }

        //Function Info
        $datos=$constructor -> request($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6],$params[7],$params[8],$params[9],$params[10],$params[11],$params[12],$params[13],$params[14],$params[15],$params[16]);

        //Response
        if(isset($datos['error_code'])){
            $resp = new ApiResponseBuilder(
                400,
                "Bad request",
                $datos
            );
        }else{
            $resp = new ApiResponseBuilder(
                201,
                "Reference created successfully",
                $datos
            );
        }

        $view = $this->view($resp, 201);

        return $this->handleView($view);

    }
    public function transactionTest(Request $request){
        $request->request->set('mode','T');
        return $this->transaction($request);
    }

    /**
     * This method allows client to obtain info for the payment services.
     *
     * @ApiDoc(
     *   section="PagoFacil payment with Credit Card",
     *   description="Return a JSON with the response.",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="transaction_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Transaction ID we want consult."
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function status(Request $request){
        static $paramNames = array(
            'transaction_id'
        );

        //Get the parameters sent by POST and put them in $params array
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->get($paramName, 'null');
        }
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Include the class
        include("../vendor/pagofacil/PagofacilService.php");

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Constructor in Test mode
            $constructor=new PagofacilService($this->testArray['id_sucursal'],$this->testArray['id_usuario'],$this->testArray['id_servicio']);
        }elseif($mode=='P'){
            //Constructor in Production mode
            $constructor=new PagofacilService($this->prodArray['id_sucursal'],$this->prodArray['id_usuario'],$this->prodArray['id_servicio']);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong request');
        }

        //Function Info
        $datos=$constructor -> status($params[0]);

        //Response
        if(isset($datos['error_code'])){
            $resp = new ApiResponseBuilder(
                400,
                "Bad request",
                $datos
            );
        }else{
            $resp = new ApiResponseBuilder(
                201,
                "Reference created successfully",
                $datos
            );
        }

        $view = $this->view($resp, 201);

        return $this->handleView($view);

    }
    public function statusTest(Request $request){
        $request->request->set('mode','T');
        return $this->status($request);
    }
}