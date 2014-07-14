<?php

namespace Arbaf\FinancialApiBundle\Controller;

use Arbaf\FinancialApiBundle\Entity\Group;
use Arbaf\FinancialApiBundle\Entity\User;
use Arbaf\FinancialApiBundle\Response\ApiResponseBuilder;
use Doctrine\DBAL\DBALException;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class UsersController
 * @package Arbaf\FinancialApiBundle\Controller
 */
class ServicesNetpayPosController extends FosRestController
{


    /**
     * This method allows card tokenization, and then make payments without sending the card number again.
     *
     * @ApiDoc(
     *   section="Netpay POS",
     *   description="Returns a token for the card number",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="trans_type",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Ex: 'Auth'"
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */
    public function registerAction() {
        return array();
    }



    /**
     * This method makes a request for Online payment to Bank Cards,
     * with bank cards like VISA, MasterCard, American Express, and Private Cards.
     *
     * @ApiDoc(
     *   section="Netpay POS",
     *   description="Makes a card transaction",
     *   statusCodes={
     *       201="Returned when the request was successful",
     *   },
     *   parameters={
     *      {
     *          "name"="trans_type",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Ex: 'Auth'"
     *      },
     *      {
     *          "name"="store_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Ex: '12444'"
     *      },
     *      {
     *          "name"="username",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Ex: 'adm0n2'"
     *      },
     *      {
     *          "name"="password",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Ex: '0000000001'"
     *      },
     *      {
     *          "name"="terminal_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Ex: '87123222'"
     *      },
     *      {
     *          "name"="promotion",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Ex: '87123222'"
     *      },
     *      {
     *          "name"="amount",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Ex: '87123222'"
     *      },
     *      {
     *          "name"="order_id",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Ex: '87123222'"
     *      },
     *      {
     *          "name"="mode",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="[TRADP]",
     *          "description"="T=Testing
     *              R=Testing Random
     *              A=Testing Approved
     *              D=Testing Declined
     *              P=Production"
     *      },
     *      {
     *          "name"="card_number",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This is the number printed in the card."
     *      },
     *      {
     *          "name"="cvv2",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="This is the number printed in the back of the card."
     *      },
     *      {
     *          "name"="exp_date",
     *          "dataType"="string",
     *          "required"="true",
     *          "format"="MM/YY",
     *          "description"="Expiration date of the Card."
     *      },
     *      {
     *          "name"="emv_tags",
     *          "dataType"="string",
     *          "required"="false",
     *          "description"="EMVTags in hex format TLV (Tag Length Vale) as a ASCII string encoded in Base64"
     *      },
     *      {
     *          "name"="track2",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Track 2 obtained from the Card, in the format: 5063401596559875=150920100000898"
     *      },
     *      {
     *          "name"="cardholder_present_code",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Card Holder Present Code for the type of card transaction authentication"
     *      },
     *      {
     *          "name"="card_token",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Card Token if the store is configured to operate with Card
     *                         Tokenization, the first time the switch receives the Card
     *                         Number of the transaction in a CustomerRegistration
     *                         request, it will generate a Card Token associated to the
     *                         card number, for future transactions it is only required to
     *                         send the Card Token to authorize a transaction"
     *      },
     *      {
     *          "name"="attribute1",
     *          "dataType"="string",
     *          "required"="true",
     *          "description"="Helper Attribute for the transaction_id to Refund"
     *      }
     *   }
     * )
     *
     * @Rest\View(statusCode=201)
     */
    public function transactionAction() {

//build $params
        $params = array(
            "Auth",
            "6501",
            "POS",
            "adm0n2",
            "43620001",
            "null",
            "123.12",
            "sdftesst",
            "A",
            "5454545454545454",
            "123",
            "12/16",
            "null",
            "null",
            "1",
            "null",
            "null",
            "null",
            "null"
        );

        $javaBin = "java";

        $paths = array(
            "vendor/netpay-pos/out/production/NetpayPOS/",
            "vendor/netpay-pos/lib/NetPayJSONConnector.jar",
            "vendor/netpay-pos/lib/gson-2.2.4.jar"
        );
        $class = "net.telepay.api.services.NetpayTransaction";

        $command = $javaBin." -classpath";
        $command .= " ".implode(":",$paths);
        $command .= " ".$class;
        $command .= " ".implode(" ",$params);

        passthru($command, $output);

        $netpayResponse = json_decode($output);

        $view = $this->view($netpayResponse, 201);

        return $this->handleView($view);

    }


}
