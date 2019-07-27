<?php

namespace App\FinancialApiBundle\Controller\Management\System;

use Symfony\Component\HttpFoundation\Request;
use App\FinancialApiBundle\Controller\RestApiController;
use FOS\RestBundle\Controller\Annotations as Rest;

class Resp{
    public $avg1;
    public $avg5;
    public $avg15;
}
/**
 * Class ServicesController
 * @package App\FinancialApiBundle\Controller\Management\Admin
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
                'version'  => $this->container->getParameter('version')
            )
        );
    }


    /**
     * @Rest\View
     */
    public function financial(Request $request){

        $nodes = $this->get('net.app.wallets')->findAll();
        $nodesArray = array();
        foreach ($nodes as $node){
            $balance = $node->getBalance();
            $nodeArray['name'] = $node->getType().' '.$node->getCurrency();
            $nodeArray['type'] = $node->getType();
            $nodeArray['currency'] = $node->getCurrency();
            $nodeArray['available'] = $balance;
            $nodesArray[] = $nodeArray;
        }

        $response = array(
            'nodes' =>  $nodesArray
        );

        return $this->rest(
            200,
            "Financial status",
            $response
        );

    }

}
