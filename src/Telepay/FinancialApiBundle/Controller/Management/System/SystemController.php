<?php

namespace Telepay\FinancialApiBundle\Controller\Management\System;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Resp{
    public $avg1;
    public $avg5;
    public $avg15;
}
/**
 * Class ServicesController
 * @package Telepay\FinancialApiBundle\Controller\Management\Admin
 */
class SystemController extends RestApiController
{

    /**
     * @Rest\View()
     */
    public function load(Request $request) {
        $loadArray = sys_getloadavg();
        $resp = new Resp();
        $resp->avg1 = $loadArray[0];
        $resp->avg5 = $loadArray[1];
        $resp->avg15 = $loadArray[2];
        return $this->handleView($this->buildRestView(
            200,
            "Load average",
            $resp
        ));
    }

    /**
     * @Rest\View()
     */
    public function cores(Request $request) {

        return $this->handleView($this->buildRestView(
            200,
            "Number of CPU Cores",
            array('num_cpus' => intval(`nproc`))
        ));
    }


    /**
     * @Rest\View()
     */
    public function mem(Request $request) {
        $out = `free -m | grep Mem: | awk '{print $3"/"$2}'`;
        $all = explode("/", $out);
        $resp = array(
            'total' => intval($all[1]),
            'used' => intval($all[0])
        );
        return $this->handleView($this->buildRestView(
            200,
            "Memory information",
            $resp
        ));
    }


    /**
     * @Rest\View()
     */
    public function net(Request $request) {
        $out = `ifstat -i eth0 1 1 | tail -1 | awk '{print $1"/"$2}'`;
        $all = explode("/", $out);
        $resp = array(
            'up' => floatval($all[1]),
            'down' => floatval($all[0])
        );
        return $this->handleView($this->buildRestView(
            200,
            "Network information",
            $resp
        ));
    }


    /**
     * @Rest\View
     */
    public function version(Request $request){
        return $this->rest(
            200,
            "Version information",
            array(
                'build_id'  => $this->container->getParameter('build_id'),
                'version'  => $this->container->getParameter('version')
            )
        );
    }


    /**
     * @Rest\View
     */
    public function financial(Request $request){
        return $this->rest(
            200,
            "Financial status",
            array(
                'nodes'  => array(
                    array(
                        'id' => 1,
                        'name' => 'Caixa Coves',
                        'driver' => 'net.telepay.driver.ruralvia',
                        'type' => 'Bank',
                        'currency' => 'EUR',
                        'available' => 532.00,
                    ),
                    array(
                        'id' => 2,
                        'name' => 'Abanca Entropy',
                        'driver' => 'net.telepay.driver.abanca',
                        'type' => 'Bank',
                        'currency' => 'EUR',
                        'available' => 0.00,
                    ),
                    array(
                        'id' => 3,
                        'name' => 'Kraken EUR',
                        'driver' => 'net.telepay.driver.kraken',
                        'type' => 'Exchange',
                        'currency' => 'EUR',
                        'available' => 121.22,
                    ),
                    array(
                        'id' => 4,
                        'name' => 'Kraken BTC',
                        'driver' => 'net.telepay.driver.kraken',
                        'type' => 'Exchange',
                        'currency' => 'BTC',
                        'available' => 3.24123,
                    ),
                    array(
                        'id' => 5,
                        'name' => 'Bittrex BTC',
                        'driver' => 'net.telepay.driver.bittrex',
                        'type' => 'Exchange',
                        'currency' => 'BTC',
                        'available' => 0.24123,
                    ),
                    array(
                        'id' => 6,
                        'name' => 'Bittrex FAIR',
                        'driver' => 'net.telepay.driver.bittrex',
                        'type' => 'Exchange',
                        'currency' => 'FAIR',
                        'available' => 33823.24123,
                    ),
                    array(
                        'id' => 7,
                        'name' => 'ChipChap BTC',
                        'driver' => 'net.telepay.driver.fullnode',
                        'type' => 'Exchange',
                        'currency' => 'BTC',
                        'available' => 33823.24123,
                    ),
                    array(
                        'id' => 8,
                        'name' => 'Telepay Hal',
                        'driver' => 'net.telepay.driver.manual',
                        'type' => 'Bank',
                        'currency' => 'EUR',
                        'available' => 1034.22,
                    ),
                    array(
                        'id' => 9,
                        'name' => 'ChipChap FAIR',
                        'driver' => 'net.telepay.driver.fullnode',
                        'type' => 'Crypto Node',
                        'currency' => 'FAIR',
                        'available' => 12310.233422,
                    ),
                    array(
                        'id' => 10,
                        'name' => 'Bitstamp BTC',
                        'driver' => 'net.telepay.driver.bitstamp',
                        'type' => 'Exchange',
                        'currency' => 'BTC',
                        'available' => 0.233422,
                    ),
                ),
                'connections'  => array(
                    1 => array(2,8,3,10),
                    7 => array(4,5,10),
                    6 => array(9,5),
                    4 => array(5,3),
                ),
                'routes'  => array(),
            )
        );
    }



}
