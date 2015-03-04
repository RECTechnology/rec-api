<?php
namespace Telepay\FinancialApiBundle\DependencyInjection\Transactions\Libs;

use nusoap_client;

require_once('includes/nusoap.php');

class PaynetService
{

    var $group_id;
    var $chain_id;
    var $shop_id;
    var $pos_id;
    var $cashier_id;
    var $local_date;
    var $local_hour;
    var $transaction_id;
    var $sku;
    var $reference;
    var $fee;
    var $amount;
    var $dv;
    var $token;
    var $key = 'pxD4t09*09Wm';

    function __construct($group_id, $chain_id, $shop_id, $pos_id, $cashier_id){

        $this->group_id = $group_id;
        $this->chain_id = $chain_id;
        $this->shop_id = $shop_id;
        $this->pos_id = $pos_id;
        $this->cashier_id = $cashier_id;

    }

    public function info($local_date, $local_hour, $transaction_id, $sku, $reference, $amount){

        $this->local_date = $local_date;
        $this->local_hour = $local_hour;
        $this->transaction_id = $transaction_id;
        $this->sku = $sku;
        $this->reference = $reference;
        $this->amount = $amount;

        exec('java -jar ../src/Telepay/FinancialApiBundle/DependencyInjection/Services/libs/jar/JAVAMUCOM.jar "E" "' . $reference . '" "' . $this->key . '"', $enc_ref);
        if ($amount == 0) {

            $params = '
                <Info xmlns="http://www.pagoexpress.com.mx/pxUniversal">
                    <cArrayCampos>
                        <cCampo>
                            <sCampo>IDGRUPO</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->group_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->group_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDCADENA</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->chain_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->chain_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDTIENDA</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->shop_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->shop_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDPOS</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->pos_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->pos_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDCAJERO</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->cashier_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->cashier_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>FECHALOCAL</sCampo>
                            <iTipo>FD</iTipo>
                            <iLongitud>' . strlen($this->local_date) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->local_date . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>HORALOCAL</sCampo>
                            <iTipo>HR</iTipo>
                            <iLongitud>' . strlen($this->local_hour) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->local_hour . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>TRANSACCION</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->transaction_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:long">' . $this->transaction_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>SKU</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($this->sku) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->sku . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>REFERENCIA</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($enc_ref[0]) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $enc_ref[0] . '</sValor>
                            <bEncriptado>true</bEncriptado>
                        </cCampo>
                        <cCampo xsi:nil="true" />
                    </cArrayCampos>
                </Info>';

        } else {
            $params = '<Info xmlns="http://www.pagoexpress.com.mx/pxUniversal"><cArrayCampos><cCampo><sCampo>IDGRUPO</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->group_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->group_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>IDCADENA</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->chain_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->chain_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>IDTIENDA</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->shop_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->shop_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>IDPOS</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->pos_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->pos_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>IDCAJERO</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->cashier_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->cashier_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>FECHALOCAL</sCampo><iTipo>FD</iTipo><iLongitud>' . strlen($this->local_date) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->local_date . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>HORALOCAL</sCampo><iTipo>HR</iTipo><iLongitud>' . strlen($this->local_hour) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->local_hour . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>TRANSACCION</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->transaction_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:long">' . $this->transaction_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>SKU</sCampo><iTipo>AN</iTipo><iLongitud>' . strlen($this->sku) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->sku . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>REFERENCIA</sCampo><iTipo>AN</iTipo><iLongitud>' . strlen($enc_ref[0]) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $enc_ref[0] . '</sValor><bEncriptado>true</bEncriptado></cCampo><cCampo><sCampo>MONTO</sCampo><iTipo>ND</iTipo><iLongitud>' . strlen($this->amount) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->amount . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo xsi:nil="true" /></cArrayCampos></Info>';
        }

        $url = 'https://www.integracionesqapx.com.mx/wsUniversal/pxUniversal.asmx?WSDL';
        $username = 'usr_ws';
        $password = 'usr123';

        $client = new nusoap_client($url, true);

        $client->setCredentials('', '', 'ntlm');
        $client->setUseCurl(true);
        $client->useHTTPPersistentConnection();
        $client->setCurlOption(CURLOPT_USERPWD, $username . ':' . $password);
        $client->soap_defencoding = 'utf-8';

        $result = $client->call('Info', $params, '', '', '', '', '', 'literal');

        $result = $result['InfoResult']['cCampo'];

        $resultado = array();

        if ($result[0]['sCampo'] == 'CODIGORESPUESTA') {
            $resultado['error_code'] = $result[0]['sValor'];
            $resultado['error_description'] = $result[1]['sValor'];
        } else {
            $resultado['fee']=$result[0]['sValor'];
            exec('java -jar ../src/Telepay/FinancialApiBundle/DependencyInjection/Services/libs/jar/JAVAMUCOM.jar "D" "'.$result[1]['sValor'].'" "'.$this->key.'"',$desenc_ref);
            $resultado['reference']=$desenc_ref;
            if(isset($result[2]['sValor'])){
                $resultado['amount']=$result[2]['sValor'];
            }
            if(isset($result[3]['sValor'])){
                if($result[3]['sCampo']=='TOKEN'){
                    $resultado['token']=$result[3]['sValor'];
                }elseif($result[3]['sCampo']=='DV'){
                    $resultado['dv']=$result[3]['sValor'];
                }
            }
        }

        return $resultado;

    }

    public function infoV2($local_date, $local_hour, $transaction_id, $sku, $reference, $amount){

        $this->local_date = $local_date;
        $this->local_hour = $local_hour;
        $this->transaction_id = $transaction_id;
        $this->sku = $sku;
        $this->reference = $reference;
        $this->amount = $amount;

        exec('java -jar ../src/Telepay/FinancialApiBundle/DependencyInjection/Services/libs/jar/JAVAMUCOM.jar "E" "' . $reference . '" "' . $this->key . '"', $enc_ref);
        if ($amount == 0) {

            $params = '
                <Info xmlns="http://www.pagoexpress.com.mx/pxUniversal">
                    <cArrayCampos>
                        <cCampo>
                            <sCampo>IDGRUPO</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->group_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->group_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDCADENA</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->chain_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->chain_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDTIENDA</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->shop_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->shop_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDPOS</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->pos_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->pos_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDCAJERO</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->cashier_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->cashier_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>FECHALOCAL</sCampo>
                            <iTipo>FD</iTipo>
                            <iLongitud>' . strlen($this->local_date) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->local_date . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>HORALOCAL</sCampo>
                            <iTipo>HR</iTipo>
                            <iLongitud>' . strlen($this->local_hour) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->local_hour . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>TRANSACCION</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->transaction_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:long">' . $this->transaction_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>SKU</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($this->sku) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->sku . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>REFERENCIA</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($enc_ref[0]) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $enc_ref[0] . '</sValor>
                            <bEncriptado>true</bEncriptado>
                        </cCampo>
                        <cCampo xsi:nil="true" />
                    </cArrayCampos>
                </Info>';

        } else {
            $params = '<Info xmlns="http://www.pagoexpress.com.mx/pxUniversal"><cArrayCampos><cCampo><sCampo>IDGRUPO</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->group_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->group_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>IDCADENA</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->chain_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->chain_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>IDTIENDA</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->shop_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->shop_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>IDPOS</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->pos_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->pos_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>IDCAJERO</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->cashier_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->cashier_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>FECHALOCAL</sCampo><iTipo>FD</iTipo><iLongitud>' . strlen($this->local_date) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->local_date . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>HORALOCAL</sCampo><iTipo>HR</iTipo><iLongitud>' . strlen($this->local_hour) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->local_hour . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>TRANSACCION</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->transaction_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:long">' . $this->transaction_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>SKU</sCampo><iTipo>AN</iTipo><iLongitud>' . strlen($this->sku) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->sku . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>REFERENCIA</sCampo><iTipo>AN</iTipo><iLongitud>' . strlen($enc_ref[0]) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $enc_ref[0] . '</sValor><bEncriptado>true</bEncriptado></cCampo><cCampo><sCampo>MONTO</sCampo><iTipo>ND</iTipo><iLongitud>' . strlen($this->amount) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->amount . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo xsi:nil="true" /></cArrayCampos></Info>';
        }

        $url = 'https://www.integracionesqapx.com.mx/wsUniversal/pxUniversal.asmx?WSDL';
        $username = 'usr_ws';
        $password = 'usr123';

        $client = new nusoap_client($url, true);

        $client->setCredentials('', '', 'ntlm');
        $client->setUseCurl(true);
        $client->useHTTPPersistentConnection();
        $client->setCurlOption(CURLOPT_USERPWD, $username . ':' . $password);
        $client->soap_defencoding = 'utf-8';

        $result = $client->call('Info', $params, '', '', '', '', '', 'literal');

        $result = $result['InfoResult']['cCampo'];

        $resultado = array();

        if ($result[0]['sCampo'] == 'CODIGORESPUESTA') {
            $resultado['error_code'] = $result[0]['sValor'];
            $resultado['error_description'] = $result[1]['sValor'];
        } else {
            for ($i = 0; $i < count($result); $i++) {
                if ($result[$i]['sCampo'] == 'REFERENCIA') {
                    exec('java -jar ../src/Telepay/FinancialApiBundle/DependencyInjection/Services/libs/jar/JAVAMUCOM.jar "D" "' . $result[$i]['sValor'] . '" "' . $this->key . '"', $desenc_ref);
                    $resultado[strtolower($result[$i]['sCampo'])] = $desenc_ref;
                } else {
                    $resultado[strtolower($result[$i]['sCampo'])] = $result[$i]['sValor'];
                }
            }
        }

        return $resultado;

    }

    public function ejecuta($local_date, $local_hour, $transaction_id, $sku, $fee, $reference, $amount, $dv, $token){

        $this->local_date = $local_date;
        $this->local_hour = $local_hour;
        $this->transaction_id = $transaction_id;
        $this->sku = $sku;
        $this->fee = $fee;
        $this->reference = $reference;
        $this->amount = $amount;
        $this->dv = $dv;
        $this->token = $token;
        exec('java -jar ../src/Telepay/FinancialApiBundle/DependencyInjection/Services/libs/jar/JAVAMUCOM.jar "E" "' . $reference . '" "' . $this->key . '"', $enc_ref);

        if (($dv == '0') && ($token == '0')) {

            $params = '<Ejecuta xmlns="http://www.pagoexpress.com.mx/pxUniversal"><cArrayCampos><cCampo><sCampo>IDGRUPO</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->group_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->group_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>IDCADENA</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->chain_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->chain_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>IDTIENDA</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->shop_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->shop_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>IDPOS</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->pos_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->pos_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>IDCAJERO</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->cashier_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->cashier_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>FECHALOCAL</sCampo><iTipo>FD</iTipo><iLongitud>' . strlen($this->local_date) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->local_date . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>HORALOCAL</sCampo><iTipo>HR</iTipo><iLongitud>' . strlen($this->local_hour) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->local_hour . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>TRANSACCION</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->transaction_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:long">' . $this->transaction_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>SKU</sCampo><iTipo>AN</iTipo><iLongitud>' . strlen($this->sku) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->sku . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>COMISION</sCampo><iTipo>AN</iTipo><iLongitud>' . strlen($this->fee) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->fee . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>REFERENCIA</sCampo><iTipo>AN</iTipo><iLongitud>' . strlen($enc_ref[0]) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $enc_ref[0] . '</sValor><bEncriptado>true</bEncriptado></cCampo><cCampo><sCampo>MONTO</sCampo><iTipo>ND</iTipo><iLongitud>' . strlen($this->amount) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->amount . '</sValor><bEncriptado>false</bEncriptado></cCampo></cArrayCampos></Ejecuta>';

        } elseif ($dv != '0') {

            $params = '
                <Ejecuta xmlns="http://www.pagoexpress.com.mx/pxUniversal">
                    <cArrayCampos>
                        <cCampo>
                            <sCampo>IDGRUPO</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->group_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->group_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDCADENA</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->chain_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->chain_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDTIENDA</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->shop_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->shop_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDPOS</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->pos_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->pos_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDCAJERO</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->cashier_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->cashier_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>FECHALOCAL</sCampo>
                            <iTipo>FD</iTipo>
                            <iLongitud>' . strlen($this->local_date) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->local_date . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>HORALOCAL</sCampo>
                            <iTipo>HR</iTipo>
                            <iLongitud>' . strlen($this->local_hour) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->local_hour . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>TRANSACCION</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->transaction_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:long">' . $this->transaction_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>SKU</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($this->sku) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->sku . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>COMISION</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($this->fee) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->fee . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>REFERENCIA</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($enc_ref[0]) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $enc_ref[0] . '</sValor>
                            <bEncriptado>true</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>MONTO</sCampo>
                            <iTipo>ND</iTipo>
                            <iLongitud>' . strlen($this->amount) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->amount . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>DV</sCampo>
                            <iTipo>ND</iTipo>
                            <iLongitud>' . strlen($this->dv) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->dv . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                    </cArrayCampos>
                </Ejecuta>';

        } elseif ($token != '0') {

            $params = '
                <Ejecuta xmlns="http://www.pagoexpress.com.mx/pxUniversal">
                    <cArrayCampos>
                        <cCampo>
                            <sCampo>IDGRUPO</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->group_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->group_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDCADENA</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->chain_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->chain_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDTIENDA</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->shop_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->shop_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDPOS</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->pos_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->pos_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDCAJERO</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->cashier_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->cashier_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>FECHALOCAL</sCampo>
                            <iTipo>FD</iTipo>
                            <iLongitud>' . strlen($this->local_date) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->local_date . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>HORALOCAL</sCampo>
                            <iTipo>HR</iTipo>
                            <iLongitud>' . strlen($this->local_hour) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->local_hour . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>TRANSACCION</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->transaction_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:long">' . $this->transaction_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>SKU</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($this->sku) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->sku . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>COMISION</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($this->fee) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->fee . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>REFERENCIA</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($enc_ref[0]) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $enc_ref[0] . '</sValor>
                            <bEncriptado>true</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>MONTO</sCampo>
                            <iTipo>ND</iTipo>
                            <iLongitud>' . strlen($this->amount) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->amount . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>TOKEN</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($this->token) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->token . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                    </cArrayCampos>
              </Ejecuta>';
        }

        $url = 'https://www.integracionesqapx.com.mx/wsUniversal/pxUniversal.asmx?WSDL';
        $username = 'usr_ws';
        $password = 'usr123';

        $client = new nusoap_client($url, true);

        $client->setCredentials('', '', 'ntlm');
        $client->setUseCurl(true);
        $client->useHTTPPersistentConnection();
        $client->setCurlOption(CURLOPT_USERPWD, $username . ':' . $password);
        $client->soap_defencoding = 'utf-8';

        $result = $client->call('Ejecuta', $params, '', '', '', '', '', 'literal');

        $result = $result['EjecutaResult']['cCampo'];

        $resultado = array();
        if ($result[0]['sCampo'] == 'CODIGORESPUESTA') {
            $resultado['error_code'] = $result[0]['sValor'];
            $resultado['error_description'] = $result[1]['sValor'];
        } elseif ($result[0]['sCampo'] == 'PROVEEDOR') {
            exec('java -jar ../src/Telepay/FinancialApiBundle/DependencyInjection/Services/libs/jar/JAVAMUCOM.jar "D" "' . $result[1]['sValor'] . '" "' . $this->key . '"', $desenc_ref);
            $resultado['provider'] = $result[0]['sValor'];
            $resultado['reference'] = $desenc_ref;
            $resultado['authorization'] = $result[2]['sValor'];
            $resultado['amount'] = $result[3]['sValor'];
            $resultado['legend'] = $result[4]['sValor'];
        } else {
            exec('java -jar ../src/Telepay/FinancialApiBundle/DependencyInjection/Services/libs/jar/JAVAMUCOM.jar "D" "' . $result['EjecutaResult']['cCampo'][0]['sValor'] . '" "' . $this->key . '"', $desenc_ref);
            $resultado['reference'] = $desenc_ref;
            $resultado['authorization'] = $result[1]['sValor'];
            if (isset($result[2]['sValor'])) {
                $resultado['amount'] = $result[2]['sValor'];
                if ($result[3]['sValor'] == "COMISION") {
                    $resultado['fee'] = $result[3]['sValor'];
                } else {
                    $resultado['legend'] = utf8_decode($result[3]['sValor']);
                }
                if (isset ($result[4]['sValor'])) {
                    $resultado['legend'] = $result[4]['sValor'];
                }
            }
        }

        return $resultado;

    }

    public function ejecutaV2($local_date, $local_hour, $transaction_id, $sku, $fee, $reference, $amount, $dv, $token){

        $this->local_date = $local_date;
        $this->local_hour = $local_hour;
        $this->transaction_id = $transaction_id;
        $this->sku = $sku;
        $this->fee = $fee;
        $this->reference = $reference;
        $this->amount = $amount;
        $this->dv = $dv;
        $this->token = $token;
        exec('java -jar ../src/Telepay/FinancialApiBundle/DependencyInjection/Services/libs/jar/JAVAMUCOM.jar "E" "' . $reference . '" "' . $this->key . '"', $enc_ref);

        if (($dv == '0') && ($token == '0')) {

            $params = '<Ejecuta xmlns="http://www.pagoexpress.com.mx/pxUniversal"><cArrayCampos><cCampo><sCampo>IDGRUPO</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->group_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->group_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>IDCADENA</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->chain_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->chain_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>IDTIENDA</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->shop_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->shop_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>IDPOS</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->pos_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->pos_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>IDCAJERO</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->cashier_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:int">' . $this->cashier_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>FECHALOCAL</sCampo><iTipo>FD</iTipo><iLongitud>' . strlen($this->local_date) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->local_date . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>HORALOCAL</sCampo><iTipo>HR</iTipo><iLongitud>' . strlen($this->local_hour) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->local_hour . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>TRANSACCION</sCampo><iTipo>NE</iTipo><iLongitud>' . strlen($this->transaction_id) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:long">' . $this->transaction_id . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>SKU</sCampo><iTipo>AN</iTipo><iLongitud>' . strlen($this->sku) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->sku . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>COMISION</sCampo><iTipo>AN</iTipo><iLongitud>' . strlen($this->fee) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->fee . '</sValor><bEncriptado>false</bEncriptado></cCampo><cCampo><sCampo>REFERENCIA</sCampo><iTipo>AN</iTipo><iLongitud>' . strlen($enc_ref[0]) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $enc_ref[0] . '</sValor><bEncriptado>true</bEncriptado></cCampo><cCampo><sCampo>MONTO</sCampo><iTipo>ND</iTipo><iLongitud>' . strlen($this->amount) . '</iLongitud><iClase>0</iClase><sValor xsi:type="xsd:string">' . $this->amount . '</sValor><bEncriptado>false</bEncriptado></cCampo></cArrayCampos></Ejecuta>';

        } elseif ($dv != '0') {

            $params = '
                <Ejecuta xmlns="http://www.pagoexpress.com.mx/pxUniversal">
                    <cArrayCampos>
                        <cCampo>
                            <sCampo>IDGRUPO</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->group_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->group_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDCADENA</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->chain_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->chain_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDTIENDA</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->shop_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->shop_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDPOS</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->pos_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->pos_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDCAJERO</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->cashier_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->cashier_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>FECHALOCAL</sCampo>
                            <iTipo>FD</iTipo>
                            <iLongitud>' . strlen($this->local_date) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->local_date . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>HORALOCAL</sCampo>
                            <iTipo>HR</iTipo>
                            <iLongitud>' . strlen($this->local_hour) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->local_hour . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>TRANSACCION</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->transaction_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:long">' . $this->transaction_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>SKU</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($this->sku) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->sku . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>COMISION</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($this->fee) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->fee . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>REFERENCIA</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($enc_ref[0]) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $enc_ref[0] . '</sValor>
                            <bEncriptado>true</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>MONTO</sCampo>
                            <iTipo>ND</iTipo>
                            <iLongitud>' . strlen($this->amount) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->amount . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>DV</sCampo>
                            <iTipo>ND</iTipo>
                            <iLongitud>' . strlen($this->dv) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->dv . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                    </cArrayCampos>
                </Ejecuta>';

        } elseif ($token != '0') {

            $params = '
                <Ejecuta xmlns="http://www.pagoexpress.com.mx/pxUniversal">
                    <cArrayCampos>
                        <cCampo>
                            <sCampo>IDGRUPO</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->group_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->group_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDCADENA</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->chain_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->chain_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDTIENDA</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->shop_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->shop_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDPOS</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->pos_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->pos_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>IDCAJERO</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->cashier_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:int">' . $this->cashier_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>FECHALOCAL</sCampo>
                            <iTipo>FD</iTipo>
                            <iLongitud>' . strlen($this->local_date) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->local_date . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>HORALOCAL</sCampo>
                            <iTipo>HR</iTipo>
                            <iLongitud>' . strlen($this->local_hour) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->local_hour . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>TRANSACCION</sCampo>
                            <iTipo>NE</iTipo>
                            <iLongitud>' . strlen($this->transaction_id) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:long">' . $this->transaction_id . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>SKU</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($this->sku) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->sku . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>COMISION</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($this->fee) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->fee . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>REFERENCIA</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($enc_ref[0]) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $enc_ref[0] . '</sValor>
                            <bEncriptado>true</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>MONTO</sCampo>
                            <iTipo>ND</iTipo>
                            <iLongitud>' . strlen($this->amount) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->amount . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                        <cCampo>
                            <sCampo>TOKEN</sCampo>
                            <iTipo>AN</iTipo>
                            <iLongitud>' . strlen($this->token) . '</iLongitud>
                            <iClase>0</iClase>
                            <sValor xsi:type="xsd:string">' . $this->token . '</sValor>
                            <bEncriptado>false</bEncriptado>
                        </cCampo>
                    </cArrayCampos>
              </Ejecuta>';
        }

        $url = 'https://www.integracionesqapx.com.mx/wsUniversal/pxUniversal.asmx?WSDL';
        $username = 'usr_ws';
        $password = 'usr123';

        $client = new nusoap_client($url, true);

        $client->setCredentials('', '', 'ntlm');
        $client->setUseCurl(true);
        $client->useHTTPPersistentConnection();
        $client->setCurlOption(CURLOPT_USERPWD, $username . ':' . $password);
        $client->soap_defencoding = 'utf-8';

        $result = $client->call('Ejecuta', $params, '', '', '', '', '', 'literal');

        $result = $result['EjecutaResult']['cCampo'];

        $resultado = array();
        if ($result[0]['sCampo'] == 'CODIGORESPUESTA') {
            $resultado['error_code'] = $result[0]['sValor'];
            $resultado['error_description'] = $result[1]['sValor'];
        } else{
            for ($i = 0; $i < count($result); $i++) {
                if ($result[$i]['sCampo'] == 'REFERENCIA') {
                    exec('java -jar ../src/Telepay/FinancialApiBundle/DependencyInjection/Services/libs/jar/JAVAMUCOM.jar "D" "' . $result[$i]['sValor'] . '" "' . $this->key . '"', $desenc_ref);
                    $resultado[strtolower($result[$i]['sCampo'])] = $desenc_ref;
                } else {
                    $resultado[strtolower($result[$i]['sCampo'])] = $result[$i]['sValor'];
                }
            }
        }

        return $resultado;

    }

    public function reversa($local_date, $local_hour, $transaction_id, $sku, $reference, $amount)
    {

        $this->local_date = $local_date;
        $this->local_hour = $local_hour;
        $this->transaction_id = $transaction_id;
        $this->sku = $sku;
        $this->reference = $reference;
        $this->amount = $amount;

        exec('java -jar ../src/Telepay/FinancialApiBundle/DependencyInjection/Services/libs/jar/JAVAMUCOM.jar "E" "' . $reference . '" "' . $this->key . '"', $enc_ref);

        $params = '
                <Reversa xmlns="http://www.pagoexpress.com.mx/pxUniversal">
                  <cArrayCampos>
                    <cCampo>
                      <sCampo>IDGRUPO</sCampo>
                      <iTipo>NE</iTipo>
                      <iLongitud>0</iLongitud>
                      <iClase>0</iClase>
                      <sValor xsi:type="xsd:int">' . $this->group_id . '</sValor>
                      <bEncriptado>false</bEncriptado>
                    </cCampo>
                    <cCampo>
                      <sCampo>IDCADENA</sCampo>
                      <iTipo>NE</iTipo>
                      <iLongitud>0</iLongitud>
                      <iClase>0</iClase>
                      <sValor xsi:type="xsd:int">' . $this->chain_id . '</sValor>
                      <bEncriptado>false</bEncriptado>
                    </cCampo>
                    <cCampo>
                      <sCampo>IDTIENDA</sCampo>
                      <iTipo>NE</iTipo>
                      <iLongitud>0</iLongitud>
                      <iClase>0</iClase>
                      <sValor xsi:type="xsd:int">' . $this->shop_id . '</sValor>
                      <bEncriptado>false</bEncriptado>
                    </cCampo>
                    <cCampo>
                      <sCampo>IDPOS</sCampo>
                      <iTipo>NE</iTipo>
                      <iLongitud>0</iLongitud>
                      <iClase>0</iClase>
                      <sValor xsi:type="xsd:int">' . $this->pos_id . '</sValor>
                      <bEncriptado>false</bEncriptado>
                    </cCampo>
                    <cCampo>
                      <sCampo>IDCAJERO</sCampo>
                      <iTipo>NE</iTipo>
                      <iLongitud>0</iLongitud>
                      <iClase>0</iClase>
                      <sValor xsi:type="xsd:int">' . $this->cashier_id . '</sValor>
                      <bEncriptado>false</bEncriptado>
                    </cCampo>
                    <cCampo>
                      <sCampo>FECHALOCAL</sCampo>
                      <iTipo>FD</iTipo>
                      <iLongitud>0</iLongitud>
                      <iClase>0</iClase>
                      <sValor xsi:type="xsd:string">' . $this->local_date . '</sValor>
                      <bEncriptado>false</bEncriptado>
                    </cCampo>
                    <cCampo>
                      <sCampo>HORALOCAL</sCampo>
                      <iTipo>HR</iTipo>
                      <iLongitud>0</iLongitud>
                      <iClase>0</iClase>
                      <sValor xsi:type="xsd:string">' . $this->local_hour . '</sValor>
                      <bEncriptado>false</bEncriptado>
                    </cCampo>
                    <cCampo>
                      <sCampo>TRANSACCION</sCampo>
                      <iTipo>NE</iTipo>
                      <iLongitud>0</iLongitud>
                      <iClase>0</iClase>
                      <sValor xsi:type="xsd:long">' . $this->transaction_id . '</sValor>
                      <bEncriptado>false</bEncriptado>
                    </cCampo>
                    <cCampo>
                      <sCampo>SKU</sCampo>
                      <iTipo>AN</iTipo>
                      <iLongitud>0</iLongitud>
                      <iClase>0</iClase>
                      <sValor xsi:type="xsd:string">' . $this->sku . '</sValor>
                      <bEncriptado>false</bEncriptado>
                    </cCampo>
                    <cCampo>
                      <sCampo>REFERENCIA</sCampo>
                      <iTipo>AN</iTipo>
                      <iLongitud>0</iLongitud>
                      <iClase>0</iClase>
                      <sValor xsi:type="xsd:string">' . $enc_ref[0] . '</sValor>
                      <bEncriptado>true</bEncriptado>
                    </cCampo>
                    <cCampo>
                      <sCampo>MONTO</sCampo>
                      <iTipo>ND</iTipo>
                      <iLongitud>0</iLongitud>
                      <iClase>0</iClase>
                      <sValor xsi:type="xsd:decimal">' . $this->amount . '</sValor>
                      <bEncriptado>false</bEncriptado>
                    </cCampo>
                  </cArrayCampos>
                </Reversa>';

        $url = 'https://www.integracionesqapx.com.mx/wsUniversal/pxUniversal.asmx?WSDL';
        $username = 'usr_ws';
        $password = 'usr123';

        $client = new nusoap_client($url, true);

        $client->setCredentials('', '', 'ntlm');
        $client->setUseCurl(true);
        $client->useHTTPPersistentConnection();
        $client->setCurlOption(CURLOPT_USERPWD, $username . ':' . $password);
        $client->soap_defencoding = 'utf-8';

        $result = $client->call('Reversa', $params, '', '', '', '', '', 'literal');

        $resultado = array();
        $resultado['error_code'] = $result['EjecutaResult']['cCampo'][0]['sValor'];
        $resultado['error_description'] = $result['EjecutaResult']['cCampo'][1]['sValor'];

        return $resultado;
    }

}


