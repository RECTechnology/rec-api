<?php
/**
 * Created by PhpStorm.
 * User: pere
 * Date: 11/09/14
 * Time: 9:58
 */

namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions;

use Telepay\FinancialApiBundle\DependencyInjection\Transactions\Core\BaseService;


class PagoFacilService extends BaseService{

    //This parameters are unique for us. Don't give to the client
    private $testArray =array(
        'id_sucursal'   =>  '42ee3b415f4cebd37dffe881b929c0a0bac8a72c',
        'id_usuario'    =>  '12a27c9c912ec6b4175c3bb316365965a19f6d31',
        'id_servicio'   =>  '3',
        'url_flag'      =>  'test'
    );

    //Para producción
    private $prodArray =array(
        'id_sucursal'   =>  '77cd297945a1b75979f742f183544e4867935777',
        'id_usuario'    =>  'd65a8ff620762e81c026f10b3d76752a7f32d46d',
        'id_servicio'   =>  '3',
        'url_flag'      =>  'prod'
    );

    public function getPagofacilTest(){

        return new PagofacilService(
            $this->testArray['id_sucursal'],
            $this->testArray['id_usuario'],
            $this->testArray['id_servicio'],
            $this->testArray['url_flag']
        );
    }

    public function getPagofacil(){

        return new PagofacilService(
            $this->prodArray['id_sucursal'],
            $this->prodArray['id_usuario'],
            $this->prodArray['id_servicio'],
            $this->prodArray['url_flag']
        );

    }

    public function getReceivedData()
    {
        // TODO: Implement getReceivedData() method.
    }

    public function getStatus()
    {
        // TODO: Implement getStatus() method.
    }

    public function getSentData()
    {
        // TODO: Implement getSentData() method.
    }
}