<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/6/14
 * Time: 9:11 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Services;
use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\TelepayResponse;
use Telepay\FinancialApiBundle\Document\Transaction;

/**
 * Class TestResponse
 * @package Telepay\FinancialApiBundle\Controller\Services
 */
class SampleResponse implements TelepayResponse {

    /**
     * @var boolean
     */
    private $mode;

    /**
     * @var \DateTime
     */
    private $server_time;

    public function __construct($testing, $server_time){
        $this->mode=$testing;
        $this->server_time=$server_time;
    }

    public function __toString(){
        return json_encode(array(
            'mode' => $this->mode,
            'server_time' => $this->server_time
        ));
    }

    public function getTransaction(Transaction $baseTransaction) {
        $outData = array(
            'mode' => $this->mode,
            'server_time' => $this->server_time
        );
        $baseTransaction->setData(json_encode($outData));
        return $baseTransaction;
    }
}