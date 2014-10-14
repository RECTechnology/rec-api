<?php

	require_once('includes/nusoap.php');

  class PaynetService{

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
    var $key='pxD4t09*09Wm';
    
    function __construct($group_id, $chain_id, $shop_id,$pos_id,$cashier_id){
      
      $this->group_id=$group_id;
      $this->chain_id=$chain_id;
      $this->shop_id=$shop_id;
      $this->pos_id=$pos_id;
      $this->cashier_id=$cashier_id;
      
    }

    public function info($local_date, $local_hour, $transaction_id, $sku, $reference){

      $this->local_date=$local_date;
      $this->local_hour=$local_hour;
      $this->transaction_id=$transaction_id;
      $this->sku=$sku;
      $this->reference=$reference;


        exec('java -jar ../src/Telepay/FinancialApiBundle/DependencyInjection/Services/libs/jar/JAVAMUCOM.jar "E" "'.$reference.'" "'.$this->key.'"',$enc_ref);

      $params = '
        <Info xmlns="http://www.pagoexpress.com.mx/pxUniversal">
          <cArrayCampos>
            <cCampo>
              <sCampo>IDGRUPO</sCampo>
              <iTipo>NE</iTipo>
              <iLongitud>0</iLongitud>
              <iClase>0</iClase>
              <sValor xsi:type="xsd:int">'.$this->group_id.'</sValor>
              <bEncriptado>false</bEncriptado>
            </cCampo>
            <cCampo>
              <sCampo>IDCADENA</sCampo>
              <iTipo>NE</iTipo>
              <iLongitud>0</iLongitud>
              <iClase>0</iClase>
              <sValor xsi:type="xsd:int">'.$this->chain_id.'</sValor>
              <bEncriptado>false</bEncriptado>
            </cCampo>
            <cCampo>
              <sCampo>IDTIENDA</sCampo>
              <iTipo>NE</iTipo>
              <iLongitud>0</iLongitud>
              <iClase>0</iClase>
              <sValor xsi:type="xsd:int">'.$this->shop_id.'</sValor>
              <bEncriptado>false</bEncriptado>
            </cCampo>
            <cCampo>
              <sCampo>IDPOS</sCampo>
              <iTipo>NE</iTipo>
              <iLongitud>0</iLongitud>
              <iClase>0</iClase>
              <sValor xsi:type="xsd:int">'.$this->pos_id.'</sValor>
              <bEncriptado>false</bEncriptado>
            </cCampo>
            <cCampo>
              <sCampo>IDCAJERO</sCampo>
              <iTipo>NE</iTipo>
              <iLongitud>0</iLongitud>
              <iClase>0</iClase>
              <sValor xsi:type="xsd:int">'.$this->cashier_id.'</sValor>
              <bEncriptado>false</bEncriptado>
            </cCampo>
            <cCampo>
              <sCampo>FECHALOCAL</sCampo>
              <iTipo>FD</iTipo>
              <iLongitud>0</iLongitud>
              <iClase>0</iClase>
              <sValor xsi:type="xsd:string">'.$this->local_date.'</sValor>
              <bEncriptado>false</bEncriptado>
            </cCampo>
            <cCampo>
              <sCampo>HORALOCAL</sCampo>
              <iTipo>HR</iTipo>
              <iLongitud>0</iLongitud>
              <iClase>0</iClase>
              <sValor xsi:type="xsd:string">'.$this->local_hour.'</sValor>
              <bEncriptado>false</bEncriptado>
            </cCampo>
            <cCampo>
              <sCampo>TRANSACCION</sCampo>
              <iTipo>NE</iTipo>
              <iLongitud>0</iLongitud>
              <iClase>0</iClase>
              <sValor xsi:type="xsd:long">'.$this->transaction_id.'</sValor>
              <bEncriptado>false</bEncriptado>
            </cCampo>
            <cCampo>
              <sCampo>SKU</sCampo>
              <iTipo>AN</iTipo>
              <iLongitud>0</iLongitud>
              <iClase>0</iClase>
              <sValor xsi:type="xsd:string">'.$this->sku.'</sValor>
              <bEncriptado>false</bEncriptado>
            </cCampo>
            <cCampo>
              <sCampo>REFERENCIA</sCampo>
              <iTipo>AN</iTipo>
              <iLongitud>0</iLongitud>
              <iClase>0</iClase>
              <sValor xsi:type="xsd:string">'.$enc_ref[0].'</sValor>
              <bEncriptado>false</bEncriptado>
            </cCampo>
            <cCampo xsi:nil="true" />
          </cArrayCampos>
        </Info>';

        //die(print_r($params,true));

                        
      $url = 'https://www.integracionesqapx.com.mx/wsUniversal/pxUniversal.asmx?WSDL';
      $username='usr_ws';
      $password='usr123';

      $client = new nusoap_client($url, true);

      $client->setCredentials('', '','ntlm');
      $client->setUseCurl(true);
      $client->useHTTPPersistentConnection();
      $client->setCurlOption(CURLOPT_USERPWD, $username.':'.$password);
      $client->soap_defencoding = 'utf-8';
      
      $result = $client -> call('Info',$params,'','','','','','literal');

        $resultado=array();
        //die(print_r($result,true));

      if($result['InfoResult']['cCampo'][0]['sCampo']=='CODIGORESPUESTA'){
          $resultado['error_code']=$result['InfoResult']['cCampo'][0]['sValor'];
          $resultado['error_description']=$result['InfoResult']['cCampo'][1]['sValor'];
      }else{
          $resultado['fee']=$result['InfoResult']['cCampo'][0]['sValor'];
          exec('java -jar ../src/Telepay/FinancialApiBundle/DependencyInjection/Services/libs/jar/JAVAMUCOM.jar "D" "'.$result['InfoResult']['cCampo'][1]['sValor'].'" "'.$this->key.'"',$desenc_ref);
          $resultado['reference']=$desenc_ref;
          if(isset($result['InfoResult']['cCampo'][2]['sValor'])){
              $resultado['amount']=$result['InfoResult']['cCampo'][2]['sValor'];
          }
          if(isset($result['InfoResult']['cCampo'][3]['sValor'])){
              $resultado['dv']=$result['InfoResult']['cCampo'][3]['sValor'];
          }
      }



      return $resultado;

    }

    public function ejecuta($local_date, $local_hour, $transaction_id, $sku, $fee, $reference,$amount,$dv){

      $this->local_date=$local_date;
      $this->local_hour=$local_hour;
      $this->transaction_id=$transaction_id;
      $this->sku=$sku;
      $this->fee=$fee;
      $this->reference=$reference;
      $this->amount=$amount;
      $this->dv=$dv;
        exec('java -jar ../src/Telepay/FinancialApiBundle/DependencyInjection/Services/libs/jar/JAVAMUCOM.jar "E" "'.$reference.'" "'.$this->key.'"',$enc_ref);

        if($dv=='0'){

              $params = '
                  <Ejecuta xmlns="http://www.pagoexpress.com.mx/pxUniversal">
                    <cArrayCampos>
                      <cCampo>
                        <sCampo>IDGRUPO</sCampo>
                        <iTipo>NE</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:int">'.$this->group_id.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>IDCADENA</sCampo>
                        <iTipo>NE</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:int">'.$this->chain_id.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>IDTIENDA</sCampo>
                        <iTipo>NE</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:int">'.$this->shop_id.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>IDPOS</sCampo>
                        <iTipo>NE</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:int">'.$this->pos_id.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>IDCAJERO</sCampo>
                        <iTipo>NE</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:int">'.$this->cashier_id.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>FECHALOCAL</sCampo>
                        <iTipo>FD</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:string">'.$this->local_date.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>HORALOCAL</sCampo>
                        <iTipo>HR</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:string">'.$this->local_hour.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>TRANSACCION</sCampo>
                        <iTipo>NE</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:long">'.$this->transaction_id.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>SKU</sCampo>
                        <iTipo>AN</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:string">'.$this->sku.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>COMISION</sCampo>
                        <iTipo>AN</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:string">'.$this->fee.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>REFERENCIA</sCampo>
                        <iTipo>AN</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:string">'.$enc_ref[0].'</sValor>
                        <bEncriptado>true</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>MONTO</sCampo>
                        <iTipo>ND</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:string">'.$this->amount.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                    </cArrayCampos>
                  </Ejecuta>';
        }else{
            $params = '
                  <Ejecuta xmlns="http://www.pagoexpress.com.mx/pxUniversal">
                    <cArrayCampos>
                      <cCampo>
                        <sCampo>IDGRUPO</sCampo>
                        <iTipo>NE</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:int">'.$this->group_id.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>IDCADENA</sCampo>
                        <iTipo>NE</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:int">'.$this->chain_id.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>IDTIENDA</sCampo>
                        <iTipo>NE</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:int">'.$this->shop_id.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>IDPOS</sCampo>
                        <iTipo>NE</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:int">'.$this->pos_id.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>IDCAJERO</sCampo>
                        <iTipo>NE</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:int">'.$this->cashier_id.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>FECHALOCAL</sCampo>
                        <iTipo>FD</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:string">'.$this->local_date.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>HORALOCAL</sCampo>
                        <iTipo>HR</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:string">'.$this->local_hour.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>TRANSACCION</sCampo>
                        <iTipo>NE</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:long">'.$this->transaction_id.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>SKU</sCampo>
                        <iTipo>AN</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:string">'.$this->sku.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>COMISION</sCampo>
                        <iTipo>AN</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:string">'.$this->fee.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>REFERENCIA</sCampo>
                        <iTipo>AN</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:string">'.$enc_ref[0].'</sValor>
                        <bEncriptado>true</bEncriptado>
                      </cCampo>
                      <cCampo>
                        <sCampo>MONTO</sCampo>
                        <iTipo>ND</iTipo>
                        <iLongitud>0</iLongitud>
                        <iClase>0</iClase>
                        <sValor xsi:type="xsd:string">'.$this->amount.'</sValor>
                        <bEncriptado>false</bEncriptado>
                      </cCampo>
                      <cCampo>
                          <sCampo>DV</sCampo>
                          <iTipo>ND</iTipo>
                          <iLongitud>0</iLongitud>
                          <iClase>0</iClase>
                          <sValor xsi:type="xsd:int">'.$this->dv.'</sValor>
                          <bEncriptado>false</bEncriptado>
                        </cCampo>
                    </cArrayCampos>
                  </Ejecuta>';
        }

        //die(print_r($params,true));

      $url = 'https://www.integracionesqapx.com.mx/wsUniversal/pxUniversal.asmx?WSDL';
      $username='usr_ws';
      $password='usr123';

      $client = new nusoap_client($url, true);

      $client->setCredentials('', '','ntlm');
      $client->setUseCurl(true);
      $client->useHTTPPersistentConnection();
      $client->setCurlOption(CURLOPT_USERPWD, $username.':'.$password);
      $client->soap_defencoding = 'utf-8';
    
      $result = $client -> call('Ejecuta',$params,'','','','','','literal');
        //var_dump($result);
      $resultado=array();
        if($result['EjecutaResult']['cCampo'][0]['sCampo']=='CODIGORESPUESTA'){
            $resultado['error_code']=$result['EjecutaResult']['cCampo'][0]['sValor'];
            $resultado['error_description']=$result['EjecutaResult']['cCampo'][1]['sValor'];
        }else{
            exec('java -jar ../src/Telepay/FinancialApiBundle/DependencyInjection/Services/libs/jar/JAVAMUCOM.jar "D" "'.$result['EjecutaResult']['cCampo'][0]['sValor'].'" "'.$this->key.'"',$desenc_ref);
            $resultado['reference']=$desenc_ref;
            $resultado['authorization']=$result['EjecutaResult']['cCampo'][1]['sValor'];
                if(isset($result['EjecutaResult']['cCampo'][2]['sValor'])){
                    $resultado['amount']=$result['EjecutaResult']['cCampo'][2]['sValor'];
                    $resultado['fee']=$result['EjecutaResult']['cCampo'][3]['sValor'];
                    if(isset ($result['EjecutaResult']['cCampo'][4]['sValor'])){
                        $resultado['legend']=$result['EjecutaResult']['cCampo'][4]['sValor'];
                    }
                }
        }

      return $resultado;
    
    }

    public function reversa($local_date, $local_hour, $transaction_id, $sku, $reference,$amount){

        $this->local_date=$local_date;
        $this->local_hour=$local_hour;
        $this->transaction_id=$transaction_id;
        $this->sku=$sku;
        $this->reference=$reference;
        $this->amount=$amount;

        exec('java -jar ../src/Telepay/FinancialApiBundle/DependencyInjection/Services/libs/jar/JAVAMUCOM.jar "E" "'.$reference.'" "'.$this->key.'"',$enc_ref);

        $params = '
            <Reversa xmlns="http://www.pagoexpress.com.mx/pxUniversal">
              <cArrayCampos>
                <cCampo>
                  <sCampo>IDGRUPO</sCampo>
                  <iTipo>NE</iTipo>
                  <iLongitud>0</iLongitud>
                  <iClase>0</iClase>
                  <sValor xsi:type="xsd:int">'.$this->group_id.'</sValor>
                  <bEncriptado>false</bEncriptado>
                </cCampo>
                <cCampo>
                  <sCampo>IDCADENA</sCampo>
                  <iTipo>NE</iTipo>
                  <iLongitud>0</iLongitud>
                  <iClase>0</iClase>
                  <sValor xsi:type="xsd:int">'.$this->chain_id.'</sValor>
                  <bEncriptado>false</bEncriptado>
                </cCampo>
                <cCampo>
                  <sCampo>IDTIENDA</sCampo>
                  <iTipo>NE</iTipo>
                  <iLongitud>0</iLongitud>
                  <iClase>0</iClase>
                  <sValor xsi:type="xsd:int">'.$this->shop_id.'</sValor>
                  <bEncriptado>false</bEncriptado>
                </cCampo>
                <cCampo>
                  <sCampo>IDPOS</sCampo>
                  <iTipo>NE</iTipo>
                  <iLongitud>0</iLongitud>
                  <iClase>0</iClase>
                  <sValor xsi:type="xsd:int">'.$this->pos_id.'</sValor>
                  <bEncriptado>false</bEncriptado>
                </cCampo>
                <cCampo>
                  <sCampo>IDCAJERO</sCampo>
                  <iTipo>NE</iTipo>
                  <iLongitud>0</iLongitud>
                  <iClase>0</iClase>
                  <sValor xsi:type="xsd:int">'.$this->cashier_id.'</sValor>
                  <bEncriptado>false</bEncriptado>
                </cCampo>
                <cCampo>
                  <sCampo>FECHALOCAL</sCampo>
                  <iTipo>FD</iTipo>
                  <iLongitud>0</iLongitud>
                  <iClase>0</iClase>
                  <sValor xsi:type="xsd:string">'.$this->local_date.'</sValor>
                  <bEncriptado>false</bEncriptado>
                </cCampo>
                <cCampo>
                  <sCampo>HORALOCAL</sCampo>
                  <iTipo>HR</iTipo>
                  <iLongitud>0</iLongitud>
                  <iClase>0</iClase>
                  <sValor xsi:type="xsd:string">'.$this->local_hour.'</sValor>
                  <bEncriptado>false</bEncriptado>
                </cCampo>
                <cCampo>
                  <sCampo>TRANSACCION</sCampo>
                  <iTipo>NE</iTipo>
                  <iLongitud>0</iLongitud>
                  <iClase>0</iClase>
                  <sValor xsi:type="xsd:long">'.$this->transaction_id.'</sValor>
                  <bEncriptado>false</bEncriptado>
                </cCampo>
                <cCampo>
                  <sCampo>SKU</sCampo>
                  <iTipo>AN</iTipo>
                  <iLongitud>0</iLongitud>
                  <iClase>0</iClase>
                  <sValor xsi:type="xsd:string">'.$this->sku.'</sValor>
                  <bEncriptado>false</bEncriptado>
                </cCampo>
                <cCampo>
                  <sCampo>REFERENCIA</sCampo>
                  <iTipo>AN</iTipo>
                  <iLongitud>0</iLongitud>
                  <iClase>0</iClase>
                  <sValor xsi:type="xsd:string">'.$enc_ref[0].'</sValor>
                  <bEncriptado>true</bEncriptado>
                </cCampo>
                <cCampo>
                  <sCampo>MONTO</sCampo>
                  <iTipo>ND</iTipo>
                  <iLongitud>0</iLongitud>
                  <iClase>0</iClase>
                  <sValor xsi:type="xsd:decimal">'.$this->amount.'</sValor>
                  <bEncriptado>false</bEncriptado>
                </cCampo>
              </cArrayCampos>
            </Reversa>';

        $url = 'https://www.integracionesqapx.com.mx/wsUniversal/pxUniversal.asmx?WSDL';
        $username='usr_ws';
        $password='usr123';

        $client = new nusoap_client($url, true);

        $client->setCredentials('', '','ntlm');
        $client->setUseCurl(true);
        $client->useHTTPPersistentConnection();
        $client->setCurlOption(CURLOPT_USERPWD, $username.':'.$password);
        $client->soap_defencoding = 'utf-8';

        $result = $client -> call('Reversa',$params,'','','','','','literal');

        $resultado=array();
        $resultado['error_code']=$result['EjecutaResult']['cCampo'][0]['sValor'];
        $resultado['error_description']=$result['EjecutaResult']['cCampo'][1]['sValor'];

        return $resultado;
    }

  }


