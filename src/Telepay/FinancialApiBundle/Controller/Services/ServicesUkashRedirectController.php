<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 01/08/14
 * Time: 10:55
 */

namespace Arbaf\FinancialApiBundle\Controller;

use Arbaf\FinancialApiBundle\Response\ApiResponseBuilder;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;
use UkashRedirect;

class ServicesUkashRedirectController extends FosRestController
{

    /**
     * This method redirect to Ukash site for finish the payment.
     *
     * @ApiDoc(
     *   section="Ukash",
     *   description="Returns a JSON with the response",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="total",
     *          "dataType"="dbl",
     *          "required"="true",
     *          "description"="Transaction amount"
     *      },
     *      {
     *          "name"="url_succes",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="URL if the process has gone fine."
     *      },
     *      {
     *          "name"="url_fail",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="URL if the process has failed."
     *      },
     *      {
     *          "name"="url_notification",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="URL for notification de payment"
     *      },
     *      {
     *          "name"="mode",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="T = Test , P = Production"
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function requestAction(){

        static $paramNames = array(
            'total',
            'url_succes',
            'url_fail',
            'url_notification',
            'mode'
        );

        //Get the parameters sent by POST and put them in a $params array
        $request=$this->get('request_stack')->getCurrentRequest();
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->get($paramName, 'null');
        }

        //Include the class
        include("../vendor/ukash/UkashRedirect.php");

        //Constructor
        $constructor=new UkashRedirect($params[4]);

        //Request method
        $datos=$constructor -> request($params[0],$params[1],$params[2],$params[3]);
        //print_r(json_encode($datos));
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

    /**
     * This method obtain a payment status.
     *
     * @ApiDoc(
     *   section="Ukash",
     *   description="Returns a JSON with the response",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="mode",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="T = Test , P = Production."
     *      },
     *      {
     *          "name"="utid",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Unique transaction id from the payment we want consult."
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */

    public function statusAction(){

        static $paramNames = array(
            'utid',
            'mode'
        );

        //Get the parameters sent by POST and put them in a $params array
        $request=$this->get('request_stack')->getCurrentRequest();
        $params = array();
        foreach($paramNames as $paramName){
            if(!$request->request ->has($paramName)){
                throw new HttpException(400,"Missing parameter '$paramName'");
            }
            $params[]=$request->get($paramName, 'null');
        }

        //Include the class
        include("../vendor/ukash/UkashRedirect.php");

        //Constructor
        $constructor=new UkashRedirect($params[1]);

        //Request method
        $datos=$constructor -> status($params[0]);
        //print_r(json_encode($datos));

        if (isset($datos['error_code'])){
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
}