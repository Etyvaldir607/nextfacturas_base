<?php
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'autoload.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'test-functions.php';

use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\DocumentTypes;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\SiatConfig;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\Invoices\SiatInvoice;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\Services\ServicioSiat;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\Services\ServicioFacturacionSincronizacion;

//openssl pkcs12 -info -in [cert_filename] -nodes -nocerts -out [out_cert_filename]
//openssl pkcs12 -in [cert_filename] -clcerts -nokeys -out [out_cert_filename]

date_default_timezone_set('America/La_Paz');

$config = new SiatConfig([
	'nombreSistema' => 'CREATIVA POS',
	'codigoSistema' => '72100532BAC4CC2F777B64F',
	'nit'           => 5556076011,
	'razonSocial'   => 'FIGUEROA VASQUEZ EDWIN OSVALDO',
	//'modalidad'     => ServicioSiat::MOD_COMPUTARIZADA_ENLINEA,
	'modalidad'     => ServicioSiat::MOD_ELECTRONICA_ENLINEA,
	'ambiente'      => ServicioSiat::AMBIENTE_PRUEBAS,
	'tokenDelegado'	=> 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJlZHdpbmZpZ3VldnYiLCJjb2RpZ29TaXN0ZW1hIjoiNzIxMDA1MzJCQUM0Q0MyRjc3N0I2NEYiLCJuaXQiOiJINHNJQUFBQUFBQUFBRE0xTlRVek1EY3pNRFFFQUxoUHUzNEtBQUFBIiwiaWQiOjE3MDEzMSwiZXhwIjoxNjU5MjI1NjAwLCJpYXQiOjE2NTY1NjAzMzcsIm5pdERlbGVnYWRvIjo1NTU2MDc2MDExLCJzdWJzaXN0ZW1hIjoiU0ZFIn0.Ji5MOJ0ySeaRSLGX6jCXCP8Kun4_P3wkfrLWfdzA16jnQO22KtbPR6BRYuBW_GMdxCuNx20IlGxMDeEpI7uxPg',
	'pubCert'		=> MOD_SIAT_DIR . SB_DS . 'certs' . SB_DS . 'creativa-pos' . SB_DS . 'CREATIVA_CER.pem',
	'privCert'		=> MOD_SIAT_DIR . SB_DS . 'certs' . SB_DS . 'creativa-pos' . SB_DS . 'EDWIN_FIGUEROA_VASQUEZ.pem',
	'telefono'		=> '34345435',
	'ciudad'		=> 'CHUQUISACA'
]);
$sucursal 			= 0;
$puntoventa 		= 1;
$cantFacturas		= 1000;
$codigoEvento		= 1;
$evento 			= null;
$fechaEmision		= '2022-06-26T13:31:00.000';
//$fechaEmision		= date("Y-m-d\TH:i:s.v");
$codigoActividad	= '620900';
$codigoProductoSin	= '83141';
$documentoSector	= DocumentTypes::FACTURA_COMPRA_VENTA;
$tipoFactura		= SiatInvoice::FACTURA_DERECHO_CREDITO_FISCAL;
$cafc 				= null; //'10141627BE78D' | '101923071679D'
$resEvento 			= null;


function pruebasCatalogos()
{
	global $config, $sucursal;
	
	$count = 0;
	foreach([0, 1] as $puntoventa)
	{
		$resCuis 	= obtenerCuis($puntoventa, $sucursal, true);
		$service = new ServicioFacturacionSincronizacion($resCuis->RespuestaCuis->codigo);
		$service->setConfig((array)$config);
		for($i = 0; $i < 50; $i++)
		{
			//$res = $service->sincronizarActividades($sucursal, $puntoventa);
			//$res = $service->sincronizarFechaHora($sucursal, $puntoventa);
			//$res = $service->sincronizarListaLeyendasFactura($sucursal, $puntoventa);
			//$res = $service->sincronizarListaMensajesServicios($sucursal, $puntoventa);
			//$res = $service->sincronizarListaProductosServicios($sucursal, $puntoventa);print_r($res);
			//$res = $service->sincronizarParametricaEventosSignificativos($sucursal, $puntoventa);print_r($res);
			//$res = $service->sincronizarParametricaMotivoAnulacion($sucursal, $puntoventa);print_r($res);
			//$res = $service->sincronizarParametricaPaisOrigen($sucursal, $puntoventa);print_r($res);
			//$res = $service->sincronizarParametricaTipoDocumentoIdentidad($sucursal, $puntoventa);print_r($res);
			//$res = $service->sincronizarParametricaTipoDocumentoSector($sucursal, $puntoventa);print_r($res);
			//$res = $service->sincronizarParametricaTipoEmision($sucursal, $puntoventa);print_r($res);
			//$res = $service->sincronizarParametricaTipoHabitacion($sucursal, $puntoventa);print_r($res);
			//$res = $service->sincronizarParametricaTipoMetodoPago($sucursal, $puntoventa);print_r($res);
			//$res = $service->sincronizarParametricaTipoMoneda($sucursal, $puntoventa);print_r($res);
			//$res = $service->sincronizarParametricaTipoPuntoVenta($sucursal, $puntoventa);print_r($res);
			$res = $service->sincronizarParametricaTiposFactura($sucursal, $puntoventa);print_r($res);
			$res = $service->sincronizarParametricaUnidadMedida($sucursal, $puntoventa);print_r($res);
			//$res = $service->sincronizarListaActividadesDocumentoSector($sucursal, $puntoventa);
			//print_r($res);
			$count++;
			if( $count == 80 )
			{
				$count = 0;
				sleep(10);
			}
		}
	}
	
	die;
}
function pruebaCufd()
{
	global $config, $sucursal;
	
	foreach([0, 1] as $puntoventa)
	{
		$resCuis 	= obtenerCuis($puntoventa, $sucursal, true);
		
		for($i = 0; $i < 100; $i++)
		{
			$resCufd = obtenerCufd($puntoventa, $sucursal, $resCuis->RespuestaCuis->codigo, true);
			print_r($resCufd);
		}
	}
	die;
}
function pruebasEmisionIndividual()
{
	global $config, $sucursal, $documentoSector, $codigoActividad, $codigoProductoSin, $tipoFactura;
	
	foreach([0, 1] as $puntoventa)
	{
		$resCuis 	= obtenerCuis($puntoventa, $sucursal, true);
		$resCufd	= obtenerCufd($puntoventa, $sucursal, $resCuis->RespuestaCuis->codigo, true);
		
		for($i = 0; $i < 125; $i++)
		{
			$factura = construirFactura($puntoventa, $sucursal, $config->modalidad, $documentoSector, $codigoActividad, $codigoProductoSin);
			$res = testFactura($sucursal, $puntoventa, $factura, $tipoFactura);
			print_r($res);
		}
		sleep(10);
	}
	die;
}
function pruebasEventos()
{
	global $config, $sucursal, $documentoSector, $codigoActividad, $codigoProductoSin, $tipoFactura;
	
	$cufdAntiguo 	= 'BQUtCLzk0RkE=Nz0MyRjc3N0I2NEY=Qz5rbWpBQkhXVUFIxMDA1MzJCQUM0Q';
	$fechaInicio 	= '2022-06-30T07:36:00.000';
	$fechaFin		= '2022-06-30T07:40:00.000';
	$codigoEvento 	= 7;
	foreach([0, 1] as $puntoventa)
	{
		$pvfechaInicio 	= $fechaInicio;
		$pvfechaFin		= $fechaFin;
		$resCuis 	= obtenerCuis($puntoventa, $sucursal, true);
		$resCufd	= obtenerCufd($puntoventa, $sucursal, $resCuis->RespuestaCuis->codigo, true);
		$evento 	= obtenerListadoEventos($sucursal, $puntoventa, $codigoEvento);
		
		//foreach($eventos->RespuestaListaParametricas->listaCodigos as $evento)
		//{
			for($i = 0; $i < 5; $i++)
			{
				$resEvento = registroEvento(
					$resCuis->RespuestaCuis->codigo, 
					$resCufd->RespuestaCufd->codigo, 
					$sucursal, 
					$puntoventa, 
					$evento, 
					$cufdAntiguo, 
					$pvfechaInicio, 
					$pvfechaFin
				);
				print_r($resEvento);
				$pvfechaInicio = date('Y-m-d\TH:i:s.v', strtotime($pvfechaFin) + 60);
				$pvfechaFin = date('Y-m-d\TH:i:s.v', strtotime($pvfechaInicio) + 600);
				var_dump($pvfechaInicio, $pvfechaFin);
			}
		//}
		
	}
	die;
	//ultimo registro evento hora fecha => 2022-05-19T09:35:00.000
}
function pruebasAnulacion()
{
	global $config, $sucursal, $documentoSector, $codigoActividad, $codigoProductoSin, $tipoFactura;
	
	foreach([1] as $puntoventa)
	{
		$resCuis 	= obtenerCuis($puntoventa, $sucursal, true);
		$resCufd	= obtenerCufd($puntoventa, $sucursal, $resCuis->RespuestaCuis->codigo, true);
		
		for($i = 0; $i < 125; $i++)
		{
			$factura = construirFactura($puntoventa, $sucursal, $config->modalidad, $documentoSector, $codigoActividad, $codigoProductoSin);
			$res = testFactura($sucursal, $puntoventa, $factura, $tipoFactura);
			print_r($res);
			if($res->RespuestaServicioFacturacion->codigoEstado == 908 )
			{
				$resa = testAnular(1, $factura->cabecera->cuf, $sucursal, $puntoventa, $tipoFactura, SiatInvoice::TIPO_EMISION_ONLINE, $documentoSector);
				print_r($resa);
			}
			if( $i == 100 )
				sleep(10);
		}
		sleep(10);
	}
	die;
}
function pruebasPaquetes()
{
	global $config, $sucursal, $documentoSector, $codigoActividad, $codigoProductoSin, $tipoFactura;
	
	$cufdAntiguo 			= 'BQUtCLzk0RkE=Nz0MyRjc3N0I2NEY=Qz5rbWpBQkhXVUFIxMDA1MzJCQUM0Q';
	$codigoControlAntiguo 	= '60C9DE7C4E86D74';
	$fechaInicio 			= '2022-06-30T10:30:00.000';
	$fechaFin 				= '2022-06-30T13:30:00.000';
	$cantidad 		= 5;
	$puntoventa 	= 0;
	$codigoEvento 	= 1;
	$cafc			= null; //'10141627BE78D';
	$resEvento 		= null;
	$resCuis 		= obtenerCuis($puntoventa, $sucursal);
	//print_r($resCuis);
	$resCufd		= obtenerCufd($puntoventa, $sucursal, $resCuis->RespuestaCuis->codigo);
	$evento 		= obtenerListadoEventos($sucursal, $puntoventa, $codigoEvento);
	$evento or die('Evento no encontrado');
	
	$pvfechaInicio 	= $fechaInicio;
	$pvfechaFin		= $fechaFin;
	for($i = 0; $i < 70; $i++)
	{
		$resEvento 		= $resEvento ?: registroEvento(
			$resCuis->RespuestaCuis->codigo,
			$resCufd->RespuestaCufd->codigo,
			$sucursal, $puntoventa, $evento, $cufdAntiguo, $fechaInicio, $fechaFin
		);
		if( !isset($resEvento->RespuestaListaEventos->codigoRecepcionEventoSignificativo) )
		{
			print_r($resEvento);
			die("No se pudo registrar el evento significativo\n");
		}
		test_log($resEvento);
		$facturas 		= construirFacturas(
			$sucursal, $puntoventa, $cantidad, $documentoSector, $codigoActividad, $codigoProductoSin, $pvfechaInicio, $cufdAntiguo
		);
		
		$res = testPaquetes($sucursal, $puntoventa, $facturas, $codigoControlAntiguo, $tipoFactura, $resEvento->RespuestaListaEventos, $cafc);
		print_r($res);
		if( isset($res->RespuestaServicioFacturacion->codigoRecepcion) )
		{
			$res = testRecepcionPaquete($sucursal, $puntoventa, $documentoSector, $tipoFactura, $res->RespuestaServicioFacturacion->codigoRecepcion);
			print_r($res);
		}
		
		//$pvfechaInicio 	= date('Y-m-d\TH:i:s.v', strtotime($pvfechaFin) + 60);
		//$pvfechaFin 	= date('Y-m-d\TH:i:s.v', strtotime($pvfechaInicio) + 10);
		test_log($pvfechaInicio);
		test_log($pvfechaFin);
	}
}
function pruebasFirma()
{
	global $config, $sucursal, $tipoFactura, $documentoSector, $codigoActividad, $codigoProductoSin;
	
	$puntoventa = 0;
	$resCuis = obtenerCuis($puntoventa, $sucursal);
	print_r($resCuis);
	$resCufd = obtenerCufd($puntoventa, $sucursal, $resCuis->RespuestaCuis->codigo);
	
	for($i = 0; $i < 115; $i++)
	{
		$factura = construirFactura($puntoventa, $sucursal, $config->modalidad, $documentoSector, $codigoActividad, $codigoProductoSin);
		$res = testFirma($sucursal, $puntoventa, $factura, $tipoFactura);
		print_r($res);
		//die;
	}
}

//$res = testCierreOperacionesSistema($sucursal, $puntoventa);
//print_r($res);
//$res = registroPuntoVenta($sucursal, 'Punto Venta 1');
//print_r($res);
//$res = obtenerCuis($puntoventa, $sucursal);
//print_r($res);
//pruebasCatalogos();
//pruebaCufd();
//pruebasEmisionIndividual();
//pruebasEventos();
//pruebasPaquetes();
//pruebasAnulacion();
pruebasFirma();
