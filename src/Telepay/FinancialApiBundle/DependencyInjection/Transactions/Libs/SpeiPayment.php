<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;

include('includes/STPServices2.php');

use OrdenPago;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SpeiPayment{

    private $enterprise;
    private $account;
    private $partner;
    private $operant;
    private $passphrase;

    function __construct($enterprise, $account, $partner, $operant, $passphrase)
    {
        $this->enterprise = $enterprise;
        $this->account = $account;
        $this->partner = $partner;
        $this->operant = $operant;
        $this->passphrase = $passphrase;
    }

    public function register($name,$reference,$amount,$transaction_id){

        $data = new OrdenPago();
        $data->set_empresa($this->enterprise);
        $data->set_claveRastreo($transaction_id);
        $data->set_conceptoPago($reference);
        $data->set_cuentaBeneficiario($this->account);
        //$data->set_cuentaOrdenante("846180000050000011");
        $data->set_referenciaNumerica(2);
        $data->set_monto($amount);
        $data->set_tipoCuentaBeneficiario(40);
        $data->set_tipoPago(1);
        $data->set_institucionContraparte($this->partner);
        $data->set_nombreBeneficiario($name);
        $data->set_institucionOperante($this->operant);
        //$data->set_iva(16);
        //$data->set_fechaOperacion('20141112');
        //$data->set_nombreOrdenante("Juan Lopez");
        //$data->set_rfcCurpBeneficiario("RFCBEN");
        //$data->set_rfcCurpOrdenante("RFCORD");
        //$data->set_tipoCuentaOrdenante(40);
        //die(print_r($data,true));
        $pemFile='prueba-key.pem';
        $passphrase = $this->passphrase;

        $response = registraOrden($data, $pemFile, $passphrase);

        return $response;

    }

}
