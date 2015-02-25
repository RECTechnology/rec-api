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
class ServicesController extends RestApiController
{
    /**
     * @Rest\View()
     */
    public function index() {
        $servicesRepo = $this->get('net.telepay.service_provider');

        $services = $servicesRepo->findAll();
        //die($this->print_array($services, 1,1));
        return $this->rest(
            200,
            "Services got successfully",
            $services
        );
    }
    private function print_array($array,$depth=1,$indentation=0){
        $str = "";
        if (is_array($array)){
            $str .= "Array(\n";
            foreach ($array as $key=>$value){
                if(is_array($value)){
                    if($depth){
                        $str .=  "max depth reached.";
                    }
                    else{
                        for($i=0;$i<$indentation;$i++){
                            $str .=  "&nbsp;&nbsp;&nbsp;&nbsp;";
                        }
                        $str .=  $key."=Array(";
                        $this->print_array($value,$depth-1,$indentation+1);
                        for($i=0;$i<$indentation;$i++){
                            echo "&nbsp;&nbsp;&nbsp;&nbsp;";
                        }
                        $str .=  ");";
                    }
                }
                else{
                    for($i=0;$i<$indentation;$i++){
                        $str .=  "&nbsp;&nbsp;&nbsp;&nbsp;";
                    }
                    $str .=  $key."=>".$value."\n";
                }
            }
            $str .=  ");\n";
        }
        else{
            $str .=  "It is not an array\n";
        }
        return $str;
    }

}
