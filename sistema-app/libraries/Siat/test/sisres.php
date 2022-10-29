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
//openssl pkcs12 -in softoken.p12 -nodes -nocerts > llave_privada.pem

date_default_timezone_set('America/La_Paz');

$config = new SiatConfig([
	'nombreSistema' => 'sisres',
	'codigoSistema' => '720F80E7DABC58C2993DF86',
	'nit'           => 1492840013,
	'razonSocial'   => 'RODRIGUEZ MORENO CARMEN',
	'modalidad'     => ServicioSiat::MOD_COMPUTARIZADA_ENLINEA,
	//'modalidad'     => ServicioSiat::MOD_ELECTRONICA_ENLINEA,
	'ambiente'      => ServicioSiat::AMBIENTE_PRUEBAS,
	'tokenDelegado'	=> 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJTc2FudGFjcnV6IiwiY29kaWdvU2lzdGVtYSI6IjcyMEY4MEU3REFCQzU4QzI5OTNERjg2Iiwibml0IjoiSDRzSUFBQUFBQUFBQURNMHNUU3lNREV3TURRR0FKQU1YYU1LQUFBQSIsImlkIjo2NjQ4MTYsImV4cCI6MTY1Njg5MjgwMCwiaWF0IjoxNjU2NTkzMDY0LCJuaXREZWxlZ2FkbyI6MTQ5Mjg0MDAxMywic3Vic2lzdGVtYSI6IlNGRSJ9.86O2pXKrNxdWMC4VJSXCqLjhbst8ldFpZz0LdKfAgemc1WdiOofA4E31GGj0JGQ8mg3j2xjRJ_tuW08LRIuYUg',
	'pubCert'		=> MOD_SIAT_DIR . SB_DS . 'certs' . SB_DS . 'terminalx' . SB_DS . 'certificado.pem',
	'privCert'		=> MOD_SIAT_DIR . SB_DS . 'certs' . SB_DS . 'terminalx' . SB_DS . 'llave_privada.pem',
	'telefono'		=> '34345435',
	'ciudad'		=> 'COCHABAMBA'
]);
$sucursal 			= 0;
$puntoventa 		= 1;
$cantFacturas		= 10;
$codigoEvento		= 1;
$evento 			= null;
$fechaEmision		= '2022-06-26T13:31:00.000';
//$fechaEmision		= date("Y-m-d\TH:i:s.v");
$codigoActividad	= '560001';
$codigoProductoSin	= '63393';
$documentoSector	= DocumentTypes::FACTURA_COMPRA_VENTA;
$tipoFactura		= SiatInvoice::FACTURA_DERECHO_CREDITO_FISCAL;
$cafc 				= null; //'10122AD0AAA7D';
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
			//############## quitar cometario de 2 en 2 (para envitar calapso en servidores de impuestos) ############
			
			//$res = $service->sincronizarActividades($sucursal, $puntoventa);
			//$res = $service->sincronizarFechaHora($sucursal, $puntoventa);
			
			//$res = $service->sincronizarListaLeyendasFactura($sucursal, $puntoventa);
			//$res = $service->sincronizarListaMensajesServicios($sucursal, $puntoventa);
			
			//$res = $service->sincronizarListaProductosServicios($sucursal, $puntoventa);print_r($res);
			//$res = $service->sincronizarParametricaEventosSignificativos($sucursal, $puntoventa);print_r($res);
			
			//$res = $service->sincronizarListaActividadesDocumentoSector($sucursal, $puntoventa);
			
			//$res = $service->sincronizarParametricaMotivoAnulacion($sucursal, $puntoventa);print_r($res);
			//$res = $service->sincronizarParametricaPaisOrigen($sucursal, $puntoventa);print_r($res);
			
			//$res = $service->sincronizarParametricaTipoDocumentoIdentidad($sucursal, $puntoventa);print_r($res);
			//$res = $service->sincronizarParametricaTipoDocumentoSector($sucursal, $puntoventa);print_r($res);
			
			//$res = $service->sincronizarParametricaTipoEmision($sucursal, $puntoventa);print_r($res);
			//$res = $service->sincronizarParametricaTipoHabitacion($sucursal, $puntoventa);print_r($res);
			
			//$res = $service->sincronizarParametricaTipoMetodoPago($sucursal, $puntoventa);print_r($res);
			//$res = $service->sincronizarParametricaTipoMoneda($sucursal, $puntoventa);print_r($res);
			
			//$res = $service->sincronizarParametricaTipoPuntoVenta($sucursal, $puntoventa);print_r($res);
			//$res = $service->sincronizarParametricaTiposFactura($sucursal, $puntoventa);print_r($res);
			
			$res = $service->sincronizarParametricaUnidadMedida($sucursal, $puntoventa);print_r($res);
			//
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
	
	$cufdAntiguo 	= 'BQWVCb2N4QkE=NzThDMjk5M0RGODY=Q3k2T0ZUQkhXVUJIwRjgwRTdEQUJDN';
	$fechaInicio 	= '2022-07-01T00:30:00.000';
	$fechaFin		= '2022-07-01T00:39:00.000';
	$codigoEvento	= 7;
	foreach([0,1] as $puntoventa)
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
	
	$cufdAntiguo 	= 'BQWVCb2N4QkE=NzThDMjk5M0RGODY=Q3k2T0ZUQkhXVUJIwRjgwRTdEQUJDN';
	$fechaInicio 	= '2022-07-01T03:00:00.000';
	$fechaFin		= '2022-07-01T03:59:00.000';
	$codigoControlAntiguo 	= '5F81E3A75E86D74';
	
	$cantidad 		= 5;
	$puntoventa 	= 0;
	$codigoEvento 	= 1;
	$cafc			= null; //'10122AD0AAA7D';
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
function pruebasEmisionMasiva()
{
	global $config, $documentoSector, $codigoActividad, $codigoProductoSin, $tipoFactura;
	
	$fechaEmision		= date("Y-m-d\TH:i:s.v");
	$cantidad 			= 20;
	$codigoSucursal 	= 0;
	$codigoPuntoVenta 	= 1;
	$cafc				= null; //'101B4283AAD6D';
	
	for($i = 0; $i < 20; $i++)
	{
		$facturas = construirFacturas($codigoSucursal, $codigoPuntoVenta, $cantidad, $documentoSector, $codigoActividad, $codigoProductoSin, $fechaEmision);
		$fechaEmision = date("Y-m-d\TH:i:s.v", strtotime($fechaEmision) + 5);
		$res = testMasiva($codigoSucursal, $codigoPuntoVenta, $documentoSector, $facturas, $tipoFactura);
		print_r($res);
		if( $res->RespuestaServicioFacturacion->codigoEstado == 901 )
		{
			$res = testRecepcionMasiva(
				$codigoSucursal,
				$codigoPuntoVenta,
				$documentoSector,
				$tipoFactura,
				$res->RespuestaServicioFacturacion->codigoRecepcion
			);
			print_r($res);
		}
	}
	
}
//$res = testCierreOperacionesSistema($sucursal, 0);print_r($res);
//$res = registroPuntoVenta($sucursal, 'Punto Venta 1');print_r($res);
//$res = obtenerCuis($puntoventa, $sucursal);print_r($res);
//pruebasCatalogos();
//pruebaCufd();
//pruebasEmisionIndividual();
//pruebasEventos();
//pruebasPaquetes();
pruebasAnulacion();
//pruebasFirma();
//pruebasEmisionMasiva();
