<?php
class OrdenPago
{
  var $fechaOperacion;
  var $institucionOperante;
  var $institucionContraparte;
  var $claveRastreo;
  var $monto;
  var $tipoPago;
  var $topologia;
  var $prioridad;
  var $usuario;
  var $empresa;
  var $tipoOperacion;
  var $causaDevolucion;
  var $claveRastreoDevolucion;
  var $nombreOrdenante;
  var $tipoCuentaOrdenante;
  var $cuentaOrdenante;
  var $rfcCurpOrdenante;
  var $nombreBeneficiario;
  var $tipoCuentaBeneficiario;
  var $cuentaBeneficiario;
  var $rfcCurpBeneficiario;
  var $nombreBeneficiario2;
  var $tipoCuentaBeneficiario2;
  var $cuentaBeneficiario2;
  var $rfcCurpBeneficiario2;
  var $conceptoPago;
  var $conceptoPago2;
  var $referenciaNumerica;
  var $clavePago;
  var $referenciaCobranza;
  var $iva;
  var $medioEntrega;
  var $emailBeneficiario;
  var $claveCatUsuario1;
  var $claveCatUsuario2;
  var $folioOrigen;
  var $estado; 
  var $firma; 


 function set_fechaOperacion($fechaOperacion){
    $this->fechaOperacion = $fechaOperacion;
 }

 function get_fechaOperacion(){
    return $this->fechaOperacion;
 }

 function set_institucionOperante($institucionOperante){
    $this->institucionOperante = $institucionOperante;
 }

 function get_institucionOperante(){
    return $this->institucionOperante;
 }
 
 function set_institucionContraparte($institucionContraparte){
    $this->institucionContraparte = $institucionContraparte;
 }

 function get_institucionContraparte(){
    return $this->institucionContraparte;
 }

 function set_claveRastreo($claveRastreo){
    $this->claveRastreo = $claveRastreo;
 }

 function get_claveRastreo(){
    return $this->claveRastreo;
 }

 function set_monto($monto){
    $this->monto = $monto;
 }

 function get_monto(){
    return $this->monto;
 }

 function set_tipoPago($tipoPago){
    $this->tipoPago = $tipoPago;
 }

 function get_tipoPago(){
    return $this->tipoPago;
 }

 function set_topologia($topologia){
    $this->topologia = $topologia;
 }

 function get_topologia(){
    return $this->topologia;
 }

 function set_prioridad($prioridad){
    $this->prioridad = $prioridad;
 }

 function get_prioridad(){
    return $this->prioridad;
 }

 function set_usuario($usuario){
    $this->usuario = $usuario;
 }

 function get_usuario(){
    return $this->usuario;
 }

 function set_empresa($empresa){
    $this->empresa = $empresa;
 }

 function get_empresa(){
    return $this->empresa;
 }

 function set_tipoOperacion($tipoOperacion){
    $this->tipoOperacion = $tipoOperacion;
 }

 function get_tipoOperacion(){
    return $this->tipoOperacion;
 }

 function set_causaDevolucion($causaDevolucion){
    $this->causaDevolucion = $causaDevolucion;
 }

 function get_causaDevolucion(){
    return $this->causaDevolucion;
 }

 function set_claveRastreoDevolucion($claveRastreoDevolucion){
    $this->claveRastreoDevolucion = $claveRastreoDevolucion;
 }

 function get_claveRastreoDevolucion(){
    return $this->claveRastreoDevolucion;
 }

 function set_nombreOrdenante($nombreOrdenante){
    $this->nombreOrdenante = $nombreOrdenante;
 }

 function get_nombreOrdenante(){
    return $this->nombreOrdenante;
 }

 function set_tipoCuentaOrdenante($tipoCuentaOrdenante){
    $this->tipoCuentaOrdenante = $tipoCuentaOrdenante;
 }

 function get_tipoCuentaOrdenante(){
    return $this->tipoCuentaOrdenante;
 }

 function set_cuentaOrdenante($cuentaOrdenante){
    $this->cuentaOrdenante = $cuentaOrdenante;
 }

 function get_cuentaOrdenante(){
    return $this->cuentaOrdenante;
 }

 function set_rfcCurpOrdenante($rfcCurpOrdenante){
    $this->rfcCurpOrdenante = $rfcCurpOrdenante;
 }

 function get_rfcCurpOrdenante(){
    return $this->rfcCurpOrdenante;
 }

 function set_nombreBeneficiario($nombreBeneficiario){
    $this->nombreBeneficiario = $nombreBeneficiario;
 }

 function get_nombreBeneficiario(){
    return $this->nombreBeneficiario;
 }

 function set_tipoCuentaBeneficiario($tipoCuentaBeneficiario){
    $this->tipoCuentaBeneficiario = $tipoCuentaBeneficiario;
 }

 function get_tipoCuentaBeneficiario(){
    return $this->tipoCuentaBeneficiario;
 }

 function set_cuentaBeneficiario($cuentaBeneficiario){
    $this->cuentaBeneficiario = $cuentaBeneficiario;
 }

 function get_cuentaBeneficiario(){
    return $this->cuentaBeneficiario;
 }

 function set_rfcCurpBeneficiario($rfcCurpBeneficiario){
    $this->rfcCurpBeneficiario = $rfcCurpBeneficiario;
 }

 function get_rfcCurpBeneficiario(){
    return $this->rfcCurpBeneficiario;
 }

 function set_nombreBeneficiario2($nombreBeneficiario2){
    $this->nombreBeneficiario2 = $nombreBeneficiario2;
 }

 function get_nombreBeneficiario2(){
    return $this->nombreBeneficiario2;
 }

 function set_tipoCuentaBeneficiario2($tipoCuentaBeneficiario2){
    $this->tipoCuentaBeneficiario2 = $tipoCuentaBeneficiario2;
 }

 function get_tipoCuentaBeneficiario2(){
    return $this->tipoCuentaBeneficiario2;
 }

 function set_cuentaBeneficiario2($cuentaBeneficiario2){
    $this->cuentaBeneficiario2 = $cuentaBeneficiario2;
 }

 function get_cuentaBeneficiario2(){
    return $this->cuentaBeneficiario2;
 }

 function set_rfcCurpBeneficiario2($rfcCurpBeneficiario2){
    $this->rfcCurpBeneficiario2 = $rfcCurpBeneficiario2;
 }

 function get_rfcCurpBeneficiario2(){
    return $this->rfcCurpBeneficiario2;
 }

 function set_conceptoPago($conceptoPago){
    $this->conceptoPago = $conceptoPago;
 }

 function get_conceptoPago(){
    return $this->conceptoPago;
 }

 function set_conceptoPago2($conceptoPago2){
    $this->conceptoPago2 = $conceptoPago2;
 }

 function get_conceptoPago2(){
    return $this->conceptoPago2;
 }

 function set_referenciaNumerica($referenciaNumerica){
    $this->referenciaNumerica = $referenciaNumerica;
 }

 function get_referenciaNumerica(){
    return $this->referenciaNumerica;
 }

 function set_clavePago($clavePago){
    $this->clavePago = $clavePago;
 }

 function get_clavePago(){
    return $this->clavePago;
 }

 function set_referenciaCobranza($referenciaCobranza){
    $this->referenciaCobranza = $referenciaCobranza;
 }

 function get_referenciaCobranza(){
    return $this->referenciaCobranza;
 }

 function set_iva($iva){
    $this->iva = $iva;
 }

 function get_iva(){
    return $this->iva;
 }

 function set_medioEntrega($medioEntrega){
    $this->medioEntrega = $medioEntrega;
 }

 function get_medioEntrega(){
    return $this->medioEntrega;
 }

 function set_emailBeneficiario($emailBeneficiario){
    $this->emailBeneficiario = $emailBeneficiario;
 }

 function get_emailBeneficiario(){
    return $this->emailBeneficiario;
 }

 function set_claveCatUsuario1($claveCatUsuario1){
    $this->claveCatUsuario1 = $claveCatUsuario1;
 }

 function get_claveCatUsuario1(){
    return $this->claveCatUsuario1;
 }

 function set_claveCatUsuario2($claveCatUsuario2){
    $this->claveCatUsuario2 = $claveCatUsuario2;
 }

 function get_claveCatUsuario2(){
    return $this->claveCatUsuario2;
 }

 function set_folioOrigen($folioOrigen){
    $this->folioOrigen = $folioOrigen;
 }

 function get_folioOrigen(){
    return $this->folioOrigen;
 }

 function set_estado($estado){
    $this->estado = $estado;
 }

 function get_estado(){
    return $this->estado;
 }

 function set_firma($firma){
    $this->firma = $firma;
 }

 function get_firma(){
    return $this->firma;
 }

 
  function _getDataToSign(){
  	$retVal = "||".$this->institucionContraparte."|";
	$retVal .= trim($this->empresa)."|";
	$retVal .= $this->fechaOperacion."|";
  	$retVal .= trim($this->folioOrigen)."|";
  	$retVal .= trim($this->claveRastreo)."|";
  	$retVal .= $this->institucionOperante."|";

  	//el monto tiene que tener como separador de fracciones el caracter "."
  	$retVal .= number_format($this->monto, 2, '.', '')."|";
  	$retVal .= $this->tipoPago."|";
  	$retVal .= $this->tipoCuentaOrdenante."|";
  	$retVal .= trim($this->nombreOrdenante)."|";
  	$retVal .= trim($this->cuentaOrdenante)."|";
  	$retVal .= trim($this->rfcCurpOrdenante)."|";
  	$retVal .= $this->tipoCuentaBeneficiario."|";
  	$retVal .= trim($this->nombreBeneficiario)."|";
  	$retVal .= trim($this->cuentaBeneficiario)."|";
  	$retVal .= trim($this->rfcCurpBeneficiario)."|";
  	$retVal .= trim($this->emailBeneficiario)."|";
  	$retVal .= $this->tipoCuentaBeneficiario2."|";
  	$retVal .= trim($this->nombreBeneficiario2)."|";
  	$retVal .= trim($this->cuentaBeneficiario2)."|";
  	$retVal .= trim($this->rfcCurpBeneficiario2)."|";
  	$retVal .= trim($this->conceptoPago)."|";
  	$retVal .= trim($this->conceptoPago2)."|";
  	$retVal .= trim($this->claveCatUsuario1)."|";
  	$retVal .= trim($this->claveCatUsuario2)."|";
  	$retVal .= trim($this->clavePago)."|";
  	$retVal .= trim($this->referenciaCobranza)."|";
  	$retVal .= $this->referenciaNumerica."|";
  	$retVal .= $this->tipoOperacion."|";
  	$retVal .= trim($this->topologia)."|";
  	$retVal .= trim($this->usuario)."|";
  	$retVal .= $this->medioEntrega."|";
  	$retVal .= $this->prioridad."|";
  	$retVal .= number_format($this->iva, '', '', '')."||";
  		
  	return $retVal;
  }
}

?>
