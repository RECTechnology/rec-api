<?php

namespace Telepay\FinancialApiBundle\Controller\Services\Cryptos;

use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Telepay\FinancialApiBundle\Controller\Services\Cryptos\Lib\BIP32;
use Telepay\FinancialApiBundle\Controller\Services\Cryptos\Lib\BitcoinLib;
use Telepay\FinancialApiBundle\Controller\Services\Cryptos\Lib\EasyBitcoin\EasyBitcoin;
use Telepay\FinancialApiBundle\Controller\Services\Cryptos\Lib\Electrum;
use Telepay\FinancialApiBundle\Controller\Services\Cryptos\Lib\Libbitcoin\BitcoinExplorer;
use Telepay\FinancialApiBundle\Controller\Services\TransactionCreatedResponse;
use Telepay\FinancialApiBundle\Controller\Services\UserServiceController;
use Telepay\FinancialApiBundle\DependencyInjection\ServicesRepository;
use Telepay\FinancialApiBundle\Entity\ServiceConfig;


class BitcoinResponse extends TransactionCreatedResponse{
    private $expires_in;
    private $address;
    private $satoshis;
    private $confirmations;

    public function __construct($transaction){
        parent::__construct($transaction);
        $this->expires_in = 3600;

        $receivedData = json_decode($transaction->getReceivedData(), true);
        $this->address = $receivedData['address'];
        $this->satoshis = $receivedData['satoshis'];
        $this->confirmations = $receivedData['confirmations'];

    }
}


/**
 * Class Bitcoin
 * @package Telepay\FinancialApiBundle\Controller\Services
 */
class Bitcoin extends UserServiceController {
    /**
     * Method for create a Bitcoin transaction given the amount.
     *
     * @ApiDoc(
     *   section="Bitcoin",
     *   description="Generate a Bitcoin address for one payment",
     *   https="true",
     *   statusCodes={
     *       201="Returned when the request was successfully created",
     *       400="Returned when something is wrong in the request"
     *   },
     *   output={
     *
     *      },
     *   parameters={
     *      {
     *          "name"="satoshis",
     *          "dataType"="Integer",
     *          "required"="true",
     *          "description"="The amount to pay, in Satoshis . E.g.:10934"
     *      },
     *      {
     *          "name"="confirmations",
     *          "dataType"="Integer",
     *          "required"="true",
     *          "description"="Minimum of confirmations to validate the payment . E.g.:2"
     *      }
     *   }
     * )
     * @Rest\View
     */
    public function createPayment(Request $request, $mode = true) {

        if(!$request->request->has('satoshis')) throw new HttpException(400, "Missing parameter 'satoshis'");
        if(!$request->request->has('confirmations')) throw new HttpException(400, "Missing parameter 'confirmations'");
        if(!is_numeric($request->request->get('satoshis'))) throw new HttpException(400, "Parameter 'satoshis' must be numeric");
        if(!is_numeric($request->request->get('confirmations'))) throw new HttpException(400, "Parameter 'confirmations' must be numeric");

        $satoshis = $request->request->get('satoshis');
        $confirmations = $request->request->get('confirmations');

        $electrum = new Electrum();


        //TODO: get data from ServiceConfig
        //$seed = $electrum->decode_mnemonic("worh public humble cotton virtual fit wage remind sell fox often popular");

        $seedT = $electrum->decode_mnemonic("art blue obviously tight poet sympathy palm time somewhere diamond chair worth");

        $mpkT = $electrum->generate_mpk($seedT);

        //die($mpkT);

        $configRepo = $this->getDoctrine()->getManager()->getRepository("TelepayFinancialApiBundle:ServiceConfig");
        $userConfig = $configRepo->findOneBy(array(
            'user' => $this->getUser(),
            'service_id' => $this->getService()->getId()
        ));


        $em = $this->getDoctrine()->getManager();
        if($userConfig === null) {
            $userConfig = new ServiceConfig();
            $userConfig->setServiceId($this->getService()->getId());
            $userConfig->setUser($this->getUser());
            $em->persist($userConfig);
            $em->flush();
            die(print_r($userConfig->getServiceId(), true));
        }
        $mpk = $userConfig->getParameter("mpk");

        $addr_id = $userConfig->getParameter("address_id");
        if($addr_id == "") $addr_id = 0;
        $mpk = $mpkT;
        if($mpk=="") throw new HttpException(500, "MPK not configured");
        $address = $electrum->address_from_mpk($mpk, $addr_id, BIP32::$bitcoin_mainnet_version);
        $addr_id++;
        $userConfig->setParameter("address_id", $addr_id);
        $em->persist($userConfig);
        $em->flush();

        $transaction = $this->getTransaction();
        $transaction->setCompleted(false);
        $transaction->setReceivedData(json_encode(array(
            'address' => $address,
            'expires_in' => 3600,
            'satoshis' => $satoshis,
            'confirmations' => $confirmations,
        )));

        $transaction->setTimeOut(new \MongoDate());
        $transaction->setStatus('PENDING');
        $data = array(
            'received' => 0
        );
        $transaction->setData($data);

        $this->saveTransaction();

        return $this->rest(201, "Tranasaction OK", new BitcoinResponse($transaction));
    }

    /**
     * @Rest\View
     */
    public function createPaymentTest(Request $request) {
        throw new HttpException(501, "Test method not implemented / TODO");
    }

    /**
     * Method for check status of the transaction.
     *
     * @ApiDoc(
     *   section="Bitcoin",
     *   description="Check the payment with the tx_id",
     *   https="true",
     *   statusCodes={
     *       200="Returned when the request was in progress or completed",
     *       404="Returned when the transaction was not found"
     *   }
     * )
     * @Rest\View
     */
    public function checkPayment(Request $request, $id, $mode = true) {
        if($id === null) throw new HttpException(400, "Missing parameter 'id'");

        $transaction = $this->getDM()->getRepository('TelepayFinancialApiBundle:Transaction')->findOneBy(array('id'=>$id));

        if($transaction === null) throw new HttpException(404, "Transaction not found");

        $bx = new BitcoinExplorer();

        $receivedData = json_decode($transaction->getReceivedData(), true);
        $balanceCall = $bx->getBalance($receivedData['address']);
        $confirmationsCall = $bx->getConfirmations($receivedData['address']);


        $minConfirmations = $receivedData['confirmations'];
        $confirmations = $confirmationsCall;
        $waiting = $receivedData['satoshis'];
        $received = $balanceCall['received'];
        $successful = $confirmations >= $minConfirmations && $received >= $waiting;

        if($successful)
            $transaction->setCompleted($successful);
        $transaction->setSuccessful($successful);

        return $this->rest(200, "Read OK", array(
            'completed' => $successful."/TODO",
            'success' => $successful,
            'notification' => "PENDING/TODO",
            'bitcoin-data' => array(
                'address' => $receivedData['address'],
                'waiting' => $waiting,
                'received' => $received,
                'min_confirmations' => $minConfirmations,
                'confirmations' => strval($confirmations),
            )
        ));
    }

    /**
     * @Rest\View
     */
    public function checkPaymentTest(Request $request, $id, $mode = true) {
        throw new HttpException(501, "Test method not implemented / TODO");
    }


    public function getService()
    {
        $sr = new ServicesRepository();
        return $sr->findById(17);
    }

    public function getInputData(Request $request)
    {
        // TODO: Implement getInputData() method.
    }
}
