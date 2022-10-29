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
	'nombreSistema' => 'Siscam POS',
	'codigoSistema' => '72013276DECA20592CA9666',
	'nit'           => 322082021,
	'razonSocial'   => 'LYNX S.R.L.',
	//'modalidad'     => ServicioSiat::MOD_COMPUTARIZADA_ENLINEA,
	'modalidad'     => ServicioSiat::MOD_ELECTRONICA_ENLINEA,
	'ambiente'      => ServicioSiat::AMBIENTE_PRUEBAS,
	'tokenDelegado'	=> 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJrYXJsYTMyMiIsImNvZGlnb1Npc3RlbWEiOiI3MjAxMzI3NkRFQ0EyMDU5MkNBOTY2NiIsIm5pdCI6Ikg0c0lBQUFBQUFBQUFETTJNakt3TURJd01nUUEyWDF0WGdrQUFBQT0iLCJpZCI6NTM0OTIwLCJleHAiOjE2Njk3NjY0MDAsImlhdCI6MTY1MzY1OTY0Miwibml0RGVsZWdhZG8iOjMyMjA4MjAyMSwic3Vic2lzdGVtYSI6IlNGRSJ9.pg2Kwo44b4SXTB_XfFmINvu_QUAS8SYhbn_P23D_OXDBVBlTLEz1z3c3CQW9TCkLlEPOGliD84jJChjGwnEEpA',
	'pubCert'		=> MOD_SIAT_DIR . SB_DS . 'certs' . SB_DS . 'LYNX_SRL_CER.pem',
	'privCert'		=> MOD_SIAT_DIR . SB_DS . 'certs' . SB_DS . 'Karla_Gabriela_Claure_Ortiz.pem',
	'telefono'		=> '34345435',
	'ciudad'		=> 'COCHABAMBA'
]);
$sucursal 			= 0;
$puntoventa 		= 0;
$cantFacturas		= 1000;
$codigoEvento		= 7;
$evento 			= null;
$fechaEmision		= '2022-06-26T13:31:00.000';
//$fechaEmision		= date("Y-m-d\TH:i:s.v");
$codigoActividad	= '464000';
$codigoProductoSin	= '61141';
$documentoSector	= DocumentTypes::FACTURA_COMPRA_VENTA;
$tipoFactura		= SiatInvoice::FACTURA_DERECHO_CREDITO_FISCAL;
$cafc 				= null;
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
			$res = $service->sincronizarParametricaTipoPuntoVenta($sucursal, $puntoventa);print_r($res);
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
	
	foreach([1] as $puntoventa)
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
	
	$cufdAntiguo 	= 'BQUFDRnFuQUE=NzkZCODY1MzA2Qzc=QzlEd1FBVUZXVUFFFNzYzOEZGQkUwQ';
	$fechaInicio 	= '2022-05-19T08:31:00.000';
	$fechaFin		= '2022-05-19T08:40:00.000';
	
	foreach([0, 1] as $puntoventa)
	{
		$pvfechaInicio 	= $fechaInicio;
		$pvfechaFin		= $fechaFin;
		$resCuis 	= obtenerCuis($puntoventa, $sucursal, true);
		$resCufd	= obtenerCufd($puntoventa, $sucursal, $resCuis->RespuestaCuis->codigo, true);
		$evento 	= obtenerListadoEventos($sucursal, $puntoventa, 7);
		
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
	
	foreach([0,1] as $puntoventa)
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
	
	$cufdAntiguo 			= 'BQUtDOlVnQUE=NzjA1OTJDQTk2NjY=QktYWDNNYUdXVUJIwMTMyNzZERUNBM';
	$codigoControlAntiguo 	= '1F78BCB92D86D74';
	$fechaInicio 			= '2022-06-28T23:30:00.000';
	$fechaFin 				= '2022-06-28T23:39:00.000';
	$cantidad 		= 50;
	$puntoventa 	= 0;
	$codigoEvento 	= 7;
	$cafc			= null; //'1012168B84C8D';
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
	
	$puntoventa = 1;
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
//$res = obtenerCuis($puntoventa, $sucursal);
//print_r($res);
//pruebasCatalogos();
//pruebaCufd();
//pruebasEmisionIndividual();
//pruebasEventos();
//pruebasAnulacion();
pruebasPaquetes();
//pruebasFirma();
