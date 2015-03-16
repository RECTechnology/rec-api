<?php

namespace Telepay\FinancialApiBundle\Controller\Management\Admin;

use Symfony\Component\HttpFoundation\Request;
use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\DependencyInjection\ServicesRepository;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\User;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use Doctrine\DBAL\DBALException;
use FOS\RestBundle\Controller\FOSRestController;
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
            "Load got successfully",
            $resp
        ));
    }

    /**
     * @Rest\View()
     */
    public function cores(Request $request) {

        return $this->handleView($this->buildRestView(
            200,
            "Number of CPU Cores got successfully",
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
            "Memory information got successfully",
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
            "Network information got successfully",
            $resp
        ));
    }


    /**
     * @Rest\View
     */
    public function version(Request $request){
        return $this->rest(
            200,
            "Version info got successfully",
            array(
                'build_id'  => $this->container->getParameter('build_id'),
                'version'  => $this->container->getParameter('version')
            )
        );
    }
}
