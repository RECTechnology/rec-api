<?php

namespace Telepay\FinancialApiBundle\DependencyInjection\Services\Libs;

class HALManager {


    const ENTORNOT = 'http://172.27.0.225:8080/node/';
    const ENTORNO = 'http://172.27.0.225:8080/';
    const ENTORNOSP = 'http://172.27.0.240:8080/node/';

    private static $SERVICIOS = array(
                          'ALTA' => 'Altcempet', 
                          'PAGO' => 'Autcadpet', 
                          'ANULACION' => 'Antcempet',
                          'REVERSO' => 'Cotcadpet'

                          );                  
    private static $entrada;
    private static $resultado;
    const CADUCIDAD = ' + 7 day';

    /* alta */
    function cargaentradaalta($telefono, $importe, $concepto, $pin, $ticketemisor ){
        
                self::$entrada = sprintf('<?xml version="1.0" encoding="UTF-8"?>
                        <HELENAMESSAGE>
                        <ATM_ALTCEMPET>
                        <VERSION>1.0</VERSION>
                        <ID_EMISOR NOMBRE="MI BANCO" PAIS="MX">TPAM</ID_EMISOR>
                        <NOMBRE_ORDENANTE>Telepay</NOMBRE_ORDENANTE>
                        <MODO_COMUNICACION PLANTILLA="00042" IDIOMA="SPA" TIPO="SMS"
                        OPERADORA="VFN">%s</MODO_COMUNICACION>
                        <TFNO_DESTINO OPERADORA="VFN" PLANTILLA="00001"
                        IDIOMA="SPA">%s</TFNO_DESTINO>
                        <IMPORTE_EMISOR>%s</IMPORTE_EMISOR>
                        <MONEDA_EMISOR>484</MONEDA_EMISOR>
                        <FECHA_OP>%s</FECHA_OP>
                        <CADUCIDAD>%s</CADUCIDAD>
                        <CONCEPTO TIPO_CONCEPTO="01">%s</CONCEPTO>
                        <CLAVE_SECRETA>%s</CLAVE_SECRETA>
                        <TICKET_EMISOR>%s</TICKET_EMISOR>
                        <MENSAJE_ADICIONAL_ORD IDIOMA="SPA" PLANTILLA="00001"/>
                        <MENSAJE_ADICIONAL_BEN IDIOMA="SPA" PLANTILLA="00001"/>
                        <TICKET_CONSULTA_HAL>4444</TICKET_CONSULTA_HAL>
                        <TIPO_ORDEN>N</TIPO_ORDEN>
                        <ID_CANAL ID_TERMINAL="2222ESW003723456">04</ID_CANAL>
                        <TIPO_ORDENANTE>01</TIPO_ORDENANTE>
                        <PAIS_DESTINO>MX</PAIS_DESTINO>
                        <MONEDA_DESTINO>484</MONEDA_DESTINO>
                        </ATM_ALTCEMPET>
                        </HELENAMESSAGE>
                ',
                $telefono,
                $telefono,
                $importe,
                self::fechaoperacion(),
                self::fechacaducidad(),
                $concepto,
                $pin,
                $ticketemisor
                );

        //die(print_r(self::$entrada,true));


    }

    /* pago */
    function cargaentradapago($telefono, $importe, $referencia, $pin, $ticketadquiriente ){
        
                self::$entrada = sprintf('<?xml version="1.0" encoding="ISO-8859-1"?>
                        <HELENAMESSAGE>
                        <ATM_AUTCADPET>
                        <VERSION>1.0</VERSION>
                        <ID_ADQUIRENTE NOMBRE="MI BANCO" PAIS="MX">TPAM</ID_ADQUIRENTE>
                        <ID_TERMINAL>2222ESCO05Z02000</ID_TERMINAL>
                        <TFNO_DESTINO PLANTILLA="00000"
                        IDIOMA="SPA">%s</TFNO_DESTINO>
                        <IMPORTE_ADQUIRENTE>%s</IMPORTE_ADQUIRENTE>
                        <MONEDA_ADQUIRENTE>484</MONEDA_ADQUIRENTE>
                        <REFERENCIA>%s</REFERENCIA>
                        <CLAVE_SECRETA>%s</CLAVE_SECRETA>
                        <TICKET_ADQUIRENTE>%s</TICKET_ADQUIRENTE>
                        <FECHA_OP>%s</FECHA_OP>
                        <ID_CANAL>04</ID_CANAL>
                        <MIN_DENOMINACION>2000</MIN_DENOMINACION>
                        </ATM_AUTCADPET>
                        </HELENAMESSAGE>

                ',
                $telefono,
                $importe,
                $referencia,
                $pin,
                $ticketadquiriente, 
                self::fechaoperacion()
                );

      }

    /* anulacion */
    function cargaentradaanulacion($motivo , $literalmotivo, $ticketemisor){

            self::$entrada = sprintf('<?xml version="1.0" encoding="UTF-8"?>
                    <HELENAMESSAGE>
                    <ATM_ANTCEMPET>
                    <VERSION>1.0</VERSION>
                    <ID_EMISOR NOMBRE="MI BANCO" PAIS="MX">TPAM</ID_EMISOR>
                    <ORIGEN_ANULACION>E</ORIGEN_ANULACION>
                    <MOTIVO TIPO_MOTIVO="%s">%s</MOTIVO>
                    <TICKET_EMISOR>%s</TICKET_EMISOR>
                    <ID_CANAL>01</ID_CANAL>
                    <FECHA_OP>%s</FECHA_OP>
                    </ATM_ANTCEMPET>
                    </HELENAMESSAGE>
                ',
            $motivo,
            $literalmotivo,
            $ticketemisor,
            self::fechaoperacion()
            );

      }  

    public function cargaentradareverso( $telefono, $importe, $referencia, $pin, $ticketadquiriente ){

        self::$entrada = sprintf('<?xml version="1.0" encoding="ISO-8859-1"?>
               <HELENAMESSAGE>
               <ATM_COTCADPET>
                <VERSION>1.0</VERSION>
                <ID_ADQUIRENTE NOMBRE="MI BANCO" PAIS="MX">TPAM</ID_ADQUIRENTE>
               <TFNO_DESTINO PLANTILLA="00000"
                IDIOMA="SPA">%s</TFNO_DESTINO>
                <IMPORTE_ADQUIRENTE>%s</IMPORTE_ADQUIRENTE>
                <MONEDA_ADQUIRENTE>484</MONEDA_ADQUIRENTE>
                <REFERENCIA>%s</REFERENCIA>
                <CLAVE_SECRETA>%s</CLAVE_SECRETA>
                <TICKET_ADQUIRENTE>%s</TICKET_ADQUIRENTE>
                <FECHA_OP>%s</FECHA_OP>
                </ATM_COTCADPET>
                </HELENAMESSAGE>
                ', 

                $telefono,
                $importe,
                $referencia,
                $pin,
                $ticketadquiriente, 
                self::fechaoperacion()    
                );    

    }  


    public function getentrada(){
        
        return self::$entrada;
      
    }
      
    public function enviadatos( $servicio ){
        //die(print_r('caca',true));
        $ch = curl_init();   
        curl_setopt($ch, CURLOPT_URL, sprintf( '%s%s', self::ENTORNO, $servicio) );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::$entrada);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: close'));
        self::$resultado = curl_exec($ch);

        return true; //ojo
     }

     public function enviadatosTest( $servicio ){
        //die(print_r($servicio,true));
        $ch = curl_init();   
        curl_setopt($ch, CURLOPT_URL, sprintf( '%s%s', self::ENTORNOT, $servicio) );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_POSTFIELDS, self::$entrada);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: close'));
        self::$resultado = curl_exec($ch);

        return true; //ojo
     }
      
     public function getresultado() {

        return self::$resultado;
      
     }

     public function servicios( $servicio ){

        return self::$SERVICIOS[$servicio];


    }

    private function fechaoperacion(){

        return date('Ymdhis');

    }

    private function fechacaducidad(){

       return  date('Ymdhis' , strtotime( date('Ymdhis') . self::CADUCIDAD ) );

    }
      
}    

