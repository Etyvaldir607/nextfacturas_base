<?php
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'autoload.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'test-functions.php';

use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\DocumentTypes;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\SiatConfig;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\Invoices\SiatInvoice;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\Services\ServicioSiat;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\Services\ServicioFacturacionSincronizacion;

//openssl pkcs12 -info -in certs/ALEJANDRO_ABEL_IMANA_ARGANDONA.p12 -nodes -nocerts -out certs/ALEJANDRO_ABEL_IMANA_ARGANDONA-privatekey.pem
//openssl pkcs12 -in certs/ALEJANDRO_ABEL_IMANA_ARGANDONA.p12 -clcerts -nokeys -out certs/ALEJANDRO_ABEL_IMANA_ARGANDONA.pem

date_default_timezone_set('America/La_Paz');

$config = new SiatConfig([
	'nombreSistema' => 'quibo',
	'codigoSistema' => '71F32C4FB7905030CA55E37',
	'nit'           => 177816024,
	'razonSocial'   => 'SOCIETE DE SERVICES FINANCIERES INVESTEC LTDA.',
	'modalidad'     => ServicioSiat::MOD_COMPUTARIZADA_ENLINEA,
	//'modalidad'     => ServicioSiat::MOD_ELECTRONICA_ENLINEA,
	'ambiente'      => ServicioSiat::AMBIENTE_PRUEBAS,
	'tokenDelegado'	=> 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJTU0YgSU5WRVNURUMiLCJjb2RpZ29TaXN0ZW1hIjoiNzFGMzJDNEZCNzkwNTAzMENBNTVFMzciLCJuaXQiOiJINHNJQUFBQUFBQUFBRE0wTjdjd05ETXdNZ0VBV1BjSWRBa0FBQUE9IiwiaWQiOjIwMTM4LCJleHAiOjE2NTY1NDcyMDAsImlhdCI6MTY1MTYyMjgyMSwibml0RGVsZWdhZG8iOjE3NzgxNjAyNCwic3Vic2lzdGVtYSI6IlNGRSJ9.7jrz9ItgTJA8V2IsZDfgYn1kAQQyx9RfwGA58Egmr85aFW2pY-XT-bprHaQCzj8W3FiszLcZ0xiWUNfyLDtQhw',
	'pubCert'		=> MOD_SIAT_DIR . SB_DS . 'certs' . SB_DS . 'ALEJANDRO_ABEL_IMANA_ARGANDONA.pem',
	'privCert'		=> MOD_SIAT_DIR . SB_DS . 'certs' . SB_DS . 'ALEJANDRO_ABEL_IMANA_ARGANDONA-privatekey.pem',
]);
$sucursal 			= 0;
$puntoventa 		= 0;
$cantFacturas		= 1000;
$codigoEvento		= 7;
$evento 			= null;
$cufdAntiguo 			= 'CQUFvQ1EtUkFBNzTAzMENBNTVFMzc=Q8KhME9lTldFV1VFGMzJDNEZCNzkwN';
$codigoControlAntiguo 	= '295697DAAD56D74';
$fechaInicio 		= '2022-04-22T00:30:00.000';
$fechaFin 			= '2022-04-22T00:39:00.000';
$fechaEmision		= '2022-04-22T02:00:11.000';
//$fechaEmision		= date("Y-m-d\TH:i:s.v");
$codigoActividad	= '661900';
$codigoProductoSin	= '71190';
$documentoSector	= DocumentTypes::FACTURA_HOSPITALES;
$tipoFactura		= SiatInvoice::FACTURA_DERECHO_CREDITO_FISCAL;
$cafc 				= null;
$resEvento 			= null;
//$resEvento = (object)['RespuestaListaEventos' => (object)['codigoRecepcionEventoSignificativo' => 374592]];
//foreach([0, 1] as $puntoventa)
{
	$resCuis 	= obtenerCuis($puntoventa, $sucursal);
	$resCufd 	= obtenerCufd($puntoventa, $sucursal, $resCuis->RespuestaCuis->codigo);
	//var_dump($resCuis);
	for($i = 0; $i < 125; $i++)
	{
		/*
		///////////////////////////////////////////
		////// EMISION INDIVIDUAL y ANULACION /////
		///////////////////////////////////////////
		$factura = construirFactura($puntoventa, $sucursal, $config->modalidad, $documentoSector, $codigoActividad, $codigoProductoSin);
		//sb_siat_debug($factura->toXml()->asXML(), 1);
		$res = testFactura($sucursal, $puntoventa, $factura, $tipoFactura);
		
		if($res->RespuestaServicioFacturacion->codigoEstado == 908 )
		{
			$resa = testAnular(1, $factura->cabecera->cuf, $sucursal, $puntoventa, $tipoFactura, SiatInvoice::TIPO_EMISION_ONLINE, $documentoSector);
			print_r($resa);
		}
		*/
		///*
		///////////////////////////////////////////
		////// EMISION MASIVA /////
		///////////////////////////////////////////
		//*
		$facturas = construirFacturas($sucursal, $puntoventa, $cantFacturas, $documentoSector, $codigoActividad, $codigoProductoSin, $fechaEmision, $cufdAntiguo, $cafc);
		$fechaEmision = date("Y-m-d\TH:i:s.v", strtotime($fechaEmision) + 5);
		$res = testMasiva($sucursal, $puntoventa, $documentoSector, $facturas, $tipoFactura);
		if( $res->RespuestaServicioFacturacion->codigoEstado == 901 ) //PENDIENTE
		{
			
			$res = testRecepcionMasiva(
				$sucursal,
				$puntoventa,
				$documentoSector,
				$tipoFactura,
				$res->RespuestaServicioFacturacion->codigoRecepcion
			);
			
		}
		//*/
		
		/////////////////////////////////////////////
		///// TEST REGISTRO EVENTOS Y PAQUETES //////
		/////////////////////////////////////////////
		//$facturas = construirFacturas($sucursal, $puntoventa, $cantFacturas, $documentoSector, $codigoActividad, $codigoProductoSin, $fechaEmision, $cufdAntiguo, $cafc);
		//$fechaEmision = date("Y-m-d\TH:i:s.v", strtotime($fechaEmision) + 5);
		/*
		$evento = $evento ?: obtenerListadoEventos($sucursal, $puntoventa, $codigoEvento);
		$evento or die('Evento no encontrado');
		//$resEvento = registroEvento($resCuis->RespuestaCuis->codigo, $resCufd->RespuestaCufd->codigo, $sucursal, $puntoventa, $evento, $cufdAntiguo, $fechaInicio, $fechaFin);
		$resEvento = $resEvento ?: registroEvento($resCuis->RespuestaCuis->codigo, $resCufd->RespuestaCufd->codigo, $sucursal, $puntoventa, $evento, $cufdAntiguo, $fechaInicio, $fechaFin);
		test_log($resEvento);
		//$fechaInicio = date('Y-m-d\TH:i:s.v', strtotime($fechaFin) + 60);
		//$fechaFin = date('Y-m-d\TH:i:s.v', strtotime($fechaInicio) + 600);
		//*/
		//$res = testPaquetes($sucursal, $puntoventa, $facturas, $codigoControlAntiguo, $tipoFactura, $resEvento->RespuestaListaEventos, $cafc);
		//testRecepcionPaquete($sucursal, $puntoventa, $documentoSector, $tipoFactura, $res->RespuestaServicioFacturacion->codigoRecepcion);
		
		//////////////////////////////////
		////// TEST FIRMA DIGITAL ////////
		//////////////////////////////////
		//$res = testFirma($sucursal, $puntoventa, $factura, $tipoFactura);
		//print_r($res);
	}
	
}