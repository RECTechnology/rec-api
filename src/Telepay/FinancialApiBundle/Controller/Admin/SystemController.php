<?php

namespace Telepay\FinancialApiBundle\Controller\Admin;

use Telepay\FinancialApiBundle\Controller\RestApiController;
use Telepay\FinancialApiBundle\DependencyInjection\ServicesRepository;
use Telepay\FinancialApiBundle\Entity\Group;
use Telepay\FinancialApiBundle\Entity\User;
use Telepay\FinancialApiBundle\Response\ApiResponseBuilder;
use Doctrine\DBAL\DBALException;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ServicesController
 * @package Telepay\FinancialApiBundle\Controller\Admin
 */
class SystemController extends RestApiController
{
    /**
     * @Rest\View()
     */
    public function load() {
        return $this->handleView($this->buildRestView(
            200,
            "Load got successfully",
            sys_getloadavg()
        ));
    }

    /**
     * @Rest\View()
     */
    public function cores() {
        return $this->handleView($this->buildRestView(
            200,
            "Number of CPU Cores got successfully",
            array('num_cpus' => intval(system("nproc")))
        ));
    }


    /**
     * @Rest\View()
     */
    public function mem() {
        $out = system("free -m | grep Mem: | awk '{print $3\"/\"$2}'");
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
    public function net() {
        $out = system("ifstat -q -i eth0 -S 0.1 1 | perl -n -e '/(\\d+\\.\\d+).*(\\d+\\.\\d+)/ && print \"$1/$2\n\"'");
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

}
