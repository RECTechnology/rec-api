<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 8/6/14
 * Time: 9:11 PM
 */

namespace Telepay\FinancialApiBundle\Controller\Services;

/**
 * Class TestResponse
 * @package Telepay\FinancialApiBundle\Controller\Services
 */
class SampleResponse{

    /**
     * @var boolean
     */
    private $testing;

    /**
     * @var \DateTime
     */
    private $server_time;

    public function __construct($testing, $server_time){
        $this->testing=$testing;
        $this->server_time=$server_time;
    }

    public function __toString(){
        return json_encode(array(
            'testing'=>$this->testing,
            'server_time'=>$this->server_time
        ));
    }
}