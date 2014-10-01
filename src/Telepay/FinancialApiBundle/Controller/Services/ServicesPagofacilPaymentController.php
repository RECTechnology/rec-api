<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 14/08/14
 * Time: 09:55
 */
namespace Telepay\FinancialApiBundle\Controller\Services;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\BaseApiController;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Document\Transaction;

class ServicesPagofacilPaymentController extends RestApiController
{

    /**
     * This method allows client to pay services with credit card.
     *
     * @ApiDoc(
     *   section="PagoFacil payment with Credit Card",
     *   description="Makes a card transaction.",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="name",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Cardholder name."
     *      },
     *      {
     *          "name"="surname",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Cardholder surname."
     *      },
     *      {
     *          "name"="card_number",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Credit card number."
     *      },
     *      {
     *          "name"="cvv",
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
     */

    public function transaction(Request $request)
    {

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'name',
            'surname',
            'card_number',
            'cvv',
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

        $count=count($paramNames);
        $paramsMongo=array();
        for($i=0; $i<$count; $i++){
            $paramsMongo[$paramNames[$i]]=$params[$i];
        }

        $paramsMongo['card_number']=substr_replace($paramsMongo['card_number'], '************', 0, -4);

        //Concatenamos la referencia añadiendole el idusuario (0000)
        if($userid < 10){
            $params[16]='000'.$userid.$params[16];
        }elseif($userid<100){
            $params[16]='00'.$userid.$params[16];
        }elseif($userid<1000){
            $params[16]='0'.$userid.$params[16];
        }else{
            $params[16]=$userid.$params[16];
        }

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('PagoFacil')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Constructor in Test mode
            $datos=$this->get('pagofacil.service')->getPagofacilTest()->request($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6],$params[7],$params[8],$params[9],$params[10],$params[11],$params[12],$params[13],$params[14],$params[15],$params[16]);
        }elseif($mode=='P'){
            //Constructor in Production mode
            $datos=$this->get('pagofacil.service')->getPagofacil()->request($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6],$params[7],$params[8],$params[9],$params[10],$params[11],$params[12],$params[13],$params[14],$params[15],$params[16]);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong request');
        }

        //Response
        if(isset($datos['error'])){
            $transaction->setSuccessful(false);
            $respView = $this->buildRestView(
                400,
                "Bad request",
                $datos
            );
        }else{
            $transaction->setSuccessful(true);
            $respView = $this->buildRestView(
                201,
                "Reference created successfully",
                $datos
            );
        }

        //Guardamos la respuesta
        $transaction->setReceivedData(json_encode($datos));
        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction->setTimeOut(time());
        $transaction->setCompleted(true);

        $dm->persist($transaction);
        $dm->flush();

        return $this->handleView($respView);

    }
    public function transactionTest(Request $request){
        $request->request->set('mode','T');
        return $this->transaction($request);
    }

    /**
     * This method allows client to consult transactions.
     *
     * @ApiDoc(
     *   section="PagoFacil payment with Credit Card",
     *   description="Consult status transaction.",
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
     */

    public function status(Request $request){

        //Obtenemos el id de usuario para añadirlo a cada referencia única
        $userid = $this->getUser()->getId();

        static $paramNames = array(
            'transaction_id'
        );

        //Get the parameters sent by POST and put them in $params array
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->query ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->query->get($paramName, 'null');
        }

        $count=count($paramNames);
        $paramsMongo=array();
        for($i=0; $i<$count; $i++){
            $paramsMongo[$paramNames[$i]]=$params[$i];
        }

        //Comprobamos modo Test
        $mode = $request->get('mode');
        if(!isset($mode)) $mode = 'P';

        //Guardamos la request en mongo
        $transaction = new Transaction();
        $transaction->setIp($request->getClientIp());
        $transaction->setTimeIn(time());
        $transaction->setService($this->get('telepay.services')->findByName('PagoFacil')->getId());
        $transaction->setUser($this->get('security.context')->getToken()->getUser()->getId());
        $transaction->setSentData(json_encode($paramsMongo));
        $transaction->setMode($mode === 'P');

        //Check if it's a Test or Production transaction
        if($mode=='T'){
            //Constructor in Test mode
            $datos=$this->get('pagofacil.service')->getPagofacilTest()->status($params[0]);
        }elseif($mode=='P'){
            //Constructor in Production mode
            $datos=$this->get('pagofacil.service')->getPagofacil()->status($params[0]);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong request');
        }

        //Response
        if(isset($datos['WebServices_Transacciones']['verificar']['error'])){
            $transaction->setSuccessful(false);
            $resp = new ApiResponseBuilder(
                400,
                "Bad request",
                $datos
            );
        }else{
            $transaction->setSuccessful(true);
            $resp = new ApiResponseBuilder(
                201,
                "Reference created successfully",
                $datos
            );
        }

        //Guardamos la respuesta
        $transaction->setReceivedData(json_encode($datos));
        $dm = $this->get('doctrine_mongodb')->getManager();
        $transaction->setTimeOut(time());
        $transaction->setCompleted(true);

        $dm->persist($transaction);
        $dm->flush();


        $view = $this->view($resp, 201);

        return $this->handleView($view);

    }
    public function statusTest(Request $request){
        $request->request->set('mode','T');
        return $this->status($request);
    }
}