<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 24/07/14
 * Time: 08:54
 */
namespace Telepay\FinancialApiBundle\Controller;

use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use PaynetService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ServicesPaynetPaymentController extends FosRestController
{
    //This parameters are unique for us. Don't give to the client
    //For Test are 7 , 1 , 1, 1 , 1
    private $testArray =array(
        'group_id'   =>  7,
        'chain_id'  =>  1,
        'shop_id'  =>  1,
        'pos_id'     =>  1,
        'cashier_id'  =>  1
    );

    //Para producción no los tenemos--de momento he puesto los mismos pero habrá que cambiarlos
    private $prodArray =array(
        'group_id'   =>  7,
        'chain_id'  =>  1,
        'shop_id'  =>  1,
        'pos_id'     =>  1,
        'cashier_id'  =>  1
    );

    /**
     * This method allows client to obtain info for the payment services.
     *
     * @ApiDoc(
     *   section="Paynet Payment for Services",
     *   description="Returns a JSON with the Info for the payment. Some fields are required for the payment confirm.",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="local_date",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="dd/mm/yyyy",
     *          "description"="Transaction Date."
     *      },
     *      {
     *          "name"="local_hour",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="HH:mm:ss",
     *          "description"="Transaction Hour."
     *      },
     *      {
     *          "name"="transaction_id",
     *          "dataType"="long",
     *          "required"="true",
     *          "description"="This id must be unique along the day."
     *      },
     *      {
     *          "name"="sku",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This is a sku reference for the payment. Every service has a unique sku reference."
     *      },
     *      {
     *          "name"="reference",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This is a reference for the payment. Every service has a unique reference."
     *      },
     *      {
     *          "name"="mode",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="T -> Test , P -> Production"
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function infoAction(){

        //$user = $this->get('security.context')->getToken()->getUser();

        static $paramNames = array(
            'local_date',
            'local_hour',
            'transaction_id',
            'sku',
            'reference',
            'mode'
        );

        //Get the parameters sent by POST and put them in $params array
        $request=$this->get('request_stack')->getCurrentRequest();
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->get($paramName, 'null');
        }

        //throw new HttpException(400,system("ls ../"));

        //Include the class
        include("../vendor/paynet-services/PaynetService.php");

        //Check if it's a Test or Production transaction
        if($params[5]=='T'){
            //Constructor in Test mode
            $constructor=new PaynetService($this->testArray['group_id'],$this->testArray['chain_id'],$this->testArray['shop_id'],$this->testArray['pos_id'],$this->testArray['cashier_id']);
        }elseif($params[5]=='P'){
            //Constructor in Production mode
            $constructor=new PaynetService($this->prodArray['group_id'],$this->prodArray['chain_id'],$this->prodArray['shop_id'],$this->prodArray['pos_id'],$this->prodArray['cashier_id']);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong require->Test with T or P');
        }

        //Function Info
        $datos=$constructor -> info($params[0],$params[1],$params[2],$params[3],$params[4]);

        //Response
        $resp = new ApiResponseBuilder(
            201,
            "Reference created successfully",
            $datos
        );

        $view = $this->view($resp, 201);

        return $this->handleView($view);

    }

    /**
     * This method allows client to pay services.
     *
     * @ApiDoc(
     *   section="Paynet Payment for Services",
     *   description="Confirm the payment and returns an array. Some fields are required for the reversa method.",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="local_date",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="dd/mm/yyyy",
     *          "description"="Transaction Date."
     *      },
     *      {
     *          "name"="local_hour",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="HH:mm:ss",
     *          "description"="Transaction Hour."
     *      },
     *      {
     *          "name"="transaction_id",
     *          "dataType"="long",
     *          "required"="true",
     *          "description"="This id must be the same that the info method."
     *      },
     *      {
     *          "name"="sku",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This is a sku reference for the payment. Every service has a unique sku reference."
     *      },
     *      {
     *          "name"="fee",
     *          "dataType"="double",
     *          "required"="true",
     *          "description"="This comission value is obtained in the info method response"
     *      },
     *      {
     *          "name"="reference",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This reference is returned in the info method array."
     *      },
     *      {
     *          "name"="amount",
     *          "dataType"="double",
     *          "required"="true",
     *          "description"="Amount value must be the same value that appears in the table 1.x."
     *      },
     *      {
     *          "name"="mode",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="T -> Test , P -> Production"
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function ejecutaAction(){

        static $paramNames = array(
            'local_date',
            'local_hour',
            'transaction_id',
            'sku',
            'fee',
            'reference',
            'amount',
            'mode'
        );

        //Get the parameters sent by POST and put them in $params array
        $request=$this->get('request_stack')->getCurrentRequest();
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->get($paramName, 'null');
        }

        //Include the class
        include("../vendor/paynet-services/PaynetService.php");

        //Check if it's a Test or Production transaction
        if($params['mode']=='T'){
            //Constructor in Test mode
            $constructor=new PaynetService($this->testArray['group_id'],$this->testArray['chain_id'],$this->testArray['shop_id'],$this->testArray['pos_id'],$this->testArray['cashier_id']);
        }elseif($params['mode']=='P'){
            //Constructor in Production mode
            $constructor=new PaynetService($this->prodArray['group_id'],$this->prodArray['chain_id'],$this->prodArray['shop_id'],$this->prodArray['pos_id'],$this->prodArray['cashier_id']);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong require->Test with T or P');
        }

        //Function ejecuta
        $datos=$constructor -> ejecuta($params[0],$params[1],$params[2],$params[3],$params[4],$params[5],$params[6]);

        //Response
        $resp = new ApiResponseBuilder(
            201,
            "Reference created successfully",
            $datos
        );

        $view = $this->view($resp, 201);

        return $this->handleView($view);

    }

    /**
     * This method allows client to obtain reverse for the payment.
     *
     * @ApiDoc(
     *   section="Paynet Payment for Services",
     *   description="Returns an array with transaction information payment.",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="local_date",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="dd/mm/yyyy",
     *          "description"="Transaction Date."
     *      },
     *      {
     *          "name"="local_hour",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="HH:mm:ss",
     *          "description"="Transaction Hour."
     *      },
     *      {
     *          "name"="transaction_id",
     *          "dataType"="long",
     *          "required"="true",
     *          "description"="This id must be the same that the ejecuta method."
     *      },
     *      {
     *          "name"="sku",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This is a sku reference for the payment. Every service has a unique sku reference."
     *      },
     *      {
     *          "name"="reference",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This is a reference for the payment. Every service has a unique reference."
     *      },
     *      {
     *          "name"="amount",
     *          "dataType"="double",
     *          "required"="true",
     *          "description"="Amount value must be the same value that appears in the table 1.x."
     *      },
     *      {
     *          "name"="mode",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="T -> Test , P -> Production"
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function reversaAction(){

        static $paramNames = array(
            'local_date',
            'local_hour',
            'transaction_id',
            'sku',
            'reference',
            'amount',
            'mode'
        );

        //Get the parameters sent by POST and put them in $params array
        $request=$this->get('request_stack')->getCurrentRequest();
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->get($paramName, 'null');
        }

        //Include the class
        include("../vendor/paynet-services/PaynetService.php");

        //Check if it's a Test or Production transaction
        if($params['mode']=='T'){
            //Constructor in Test mode
            $constructor=new PaynetService($this->testArray['group_id'],$this->testArray['chain_id'],$this->testArray['shop_id'],$this->testArray['pos_id'],$this->testArray['cashier_id']);
        }elseif($params['mode']=='P'){
            //Constructor in Production mode
            $constructor=new PaynetService($this->prodArray['group_id'],$this->prodArray['chain_id'],$this->prodArray['shop_id'],$this->prodArray['pos_id'],$this->prodArray['cashier_id']);
        }else{
            //If is not one of the first shows an error message.
            throw new HttpException(400,'Wrong require->Test with T or P');
        }
        //Function reversa
        $datos=$constructor -> reversa($params[0],$params[1],$params[2],$params[3],$params[4],$params[5]);

        //response
        $resp = new ApiResponseBuilder(
            201,
            "Reference created successfully",
            $datos
        );

        $view = $this->view($resp, 201);

        return $this->handleView($view);

    }
}