<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;

class PademobileRedirect{

    private $client;
    private $tp_url;
    private $pm_url;
    private $base_url;

    function __construct($pademobile_client, $url, $base_url){
        $this->client = $pademobile_client;
        $this->pm_url = $url;
        $this->base_url = $base_url;
    }

    public function request($amount, $country, $description, $url_final){

        $this->tp_url=$this->base_url.$url_final;

        $pm_params = array(
            'pais' => $country,
            'id_usuario' => $this->client,
            'descripcion' => $description,
            'importe' => $amount,
            'url' => $this->tp_url
        );

        $pm_params_string = '';

        // Convert array to post values in url format
        foreach ($pm_params as $key => $value) {
            $pm_params_string .= $key . '=' . urlencode($value) . '&';
        }

        // Remove extra '&' from end of string
        $pm_params_string = rtrim($pm_params_string, '&');

        // Generate signature hash
        $private_key = 'i9839l6php8u21y'; // API Key proporcionada
        $firma = hash_hmac('sha1', $pm_params_string, $private_key);
        //die(print_r($firma));

        // Append signature hash to end of request string
        $pm_params_string.='&firma=' . $firma;

        // OUTPUT IMPLEMENTATION
        $output = $this->pm_url . '?' . $pm_params_string;

        return $output;

    }


}
