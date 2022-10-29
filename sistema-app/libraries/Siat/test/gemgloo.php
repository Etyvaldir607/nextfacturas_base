<?php
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'autoload.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'test-functions.php';

use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\DocumentTypes;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\SiatConfig;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\Invoices\SiatInvoice;
use SinticBolivia\SBFramework\Modules\Invoices\Classes\Siat\Services\ServicioSiat;


date_default_timezone_set('America/La_Paz');

$config = new SiatConfig([
	'nombreSistema' => "GEMGLOO",
	'codigoSistema' => '71CB9992332DC481EA9D567',
	'nit'           => 398134028,
	'razonSocial'   => "GEMGLOO",
	'modalidad'     => ServicioSiat::MOD_COMPUTARIZADA_ENLINEA,
	//'modalidad'     => ServicioSiat::MOD_ELECTRONICA_ENLINEA,
	'ambiente'      => ServicioSiat::AMBIENTE_PRUEBAS,
	'tokenDelegado'	=> 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJHZW1nbG9vMjAyMCIsImNvZGlnb1Npc3RlbWEiOiI3MUNCOTk5MjMzMkRDNDgxRUE5RDU2NyIsIm5pdCI6Ikg0c0lBQUFBQUFBQUFETzJ0REEwTmpFd3NnQUFEVHRNUndrQUFBQT0iLCJpZCI6MzAxNDQ5OSwiZXhwIjoxNjcyNDQ0ODAwLCJpYXQiOjE2NDM3MzI5OTUsIm5pdERlbGVnYWRvIjozOTgxMzQwMjgsInN1YnNpc3RlbWEiOiJTRkUifQ.ZBqAmOKpaMz7SVcY01uXcLO5J8TyK8W5f8GRgsLOPSrBAb3EEYMwkURLMIMF0Wk7ARW4UCqBKqkzbjGj2mCRGA',
]);

$sucursal 	= 0;
$puntoventa = 0;
$resCuis 	= obtenerCuis($puntoventa, $sucursal);
$resCufd 	= obtenerCufd($puntoventa, $sucursal, $resCuis->RespuestaCuis->codigo);
//$evento 	= obtenerListadoEventos($sucursal, $puntoventa, 7);
//$evento or die('Evento no encontrado');
$cufdAntiguo = null;//'BQT5DaTxuQUE=NzzQ4MUVBOUQ1Njc=Q285dmhQVEVXVUJFDQjk5OTIzMzJEQ';
$codigoControlAntiguo = '741A7D39FC56D74';
$fechaInicio 		= '2022-04-19T02:30:00.000';
$fechaFin 			= '2022-04-19T02:39:00.000';
$fechaEmision		= '2022-04-19T02:31:00.000';
$fechaEmision		= date("Y-m-d\TH:i:s.v");
$documentoSector	= DocumentTypes::FACTURA_COM_EXPORT_SERVICIOS;
$tipoFactura		= SiatInvoice::FACTURA_SIN_DERECHO_CREDITO_FISCAL;
$cafc = null;

/*
$resEvento = null; //(object)['RespuestaListaEventos' => (object)['codigoRecepcionEventoSignificativo' => 360272]];
if( !$resEvento )
{
	$resEvento = registroEvento(
		$resCuis->RespuestaCuis->codigo,
		$resCufd->RespuestaCufd->codigo,
		$sucursal,
		$puntoventa,
		$evento,
		$cufdAntiguo,
		$fechaInicio,
		$fechaFin
	);
	if( !isset($resEvento->RespuestaListaEventos->codigoRecepcionEventoSignificativo) )
	{
		print_r($resEvento);
		die();
	}
}
test_log("RESULTADO REGISTRO DE EVENTO\n=========================\n");
test_log($resEvento);
$receps = [];
*/


for($i = 0; $i < 70; $i++)
{
	$facturas = construirFacturas($sucursal, $puntoventa, 10, $documentoSector, $fechaEmision, $cufdAntiguo, $cafc);
	$fechaEmision = date("Y-m-d\TH:i:s.v", strtotime($fechaEmision) + 5);
	/*
	$res = testPaquetes($sucursal, $puntoventa, $facturas, $codigoControlAntiguo, $tipoFactura, $resEvento->RespuestaListaEventos, $cafc);
	
	if( $res->RespuestaServicioFacturacion->codigoEstado == 901 )
	{
		$receps[] = $res->RespuestaServicioFacturacion->codigoRecepcion;
		$res = testRecepcionPaquete($sucursal, $puntoventa,
			$documentoSector,
			$tipoFactura,
			$res->RespuestaServicioFacturacion->codigoRecepcion
		);
		//die;
	}
	*/
	
	$res  = testMasiva($sucursal, $puntoventa, $documentoSector, $facturas, $tipoFactura);
	print_r($res);
	if( $res->RespuestaServicioFacturacion->codigoEstado == 901 )
	{
		$res = testRecepcionMasiva(
			$sucursal,
			$puntoventa,
			$documentoSector,
			$tipoFactura,
			$res->RespuestaServicioFacturacion->codigoRecepcion
		);
		
	}
	//testFirma();
	//die;
}
//$receps = explode(',', '4d70cad2-c0d4-11ec-9dd3-fd6c979089f4,4dc21f24-c0d4-11ec-9dd3-fd6c979089f4,4e5389b6-c0d4-11ec-9dd3-fd6c979089f4,4ffb4648-c0d4-11ec-9dd3-fd6c979089f4,504c738a-c0d4-11ec-9dd3-fd6c979089f4,50a73dbc-c0d4-11ec-9dd3-fd6c979089f4,50ff48ce-c0d4-11ec-9dd3-fd6c979089f4,51790cb0-c0d4-11ec-9dd3-fd6c979089f4,51c9c528-c0d4-11ec-8a91-ed5e4b2cd182,521e74da-c0d4-11ec-8a91-ed5e4b2cd182,526d09a2-c0d4-11ec-9dd3-fd6c979089f4,52c20774-c0d4-11ec-9dd3-fd6c979089f4,5333b506-c0d4-11ec-9dd3-fd6c979089f4,539e852c-c0d4-11ec-8a91-ed5e4b2cd182,53fd4698-c0d4-11ec-9dd3-fd6c979089f4,5468d9aa-c0d4-11ec-9dd3-fd6c979089f4,54ca33ee-c0d4-11ec-8a91-ed5e4b2cd182,554508dc-c0d4-11ec-9dd3-fd6c979089f4,55a1329e-c0d4-11ec-9dd3-fd6c979089f4,55f6a600-c0d4-11ec-8a91-ed5e4b2cd182,564d9fa2-c0d4-11ec-8a91-ed5e4b2cd182,569e5750-c0d4-11ec-9dd3-fd6c979089f4,56edafd2-c0d4-11ec-9dd3-fd6c979089f4,573edd14-c0d4-11ec-9dd3-fd6c979089f4,57969a06-c0d4-11ec-9dd3-fd6c979089f4,57e1d434-c0d4-11ec-8a91-ed5e4b2cd182,58330176-c0d4-11ec-8a91-ed5e4b2cd182,5884a388-c0d4-11ec-9dd3-fd6c979089f4,58db4f0a-c0d4-11ec-9dd3-fd6c979089f4,592ec63c-c0d4-11ec-9dd3-fd6c979089f4,597dd09e-c0d4-11ec-9dd3-fd6c979089f4,5a1492b8-c0d4-11ec-8a91-ed5e4b2cd182,5ab454d0-c0d4-11ec-9dd3-fd6c979089f4,5b487e82-c0d4-11ec-9dd3-fd6c979089f4,5bb87e64-c0d4-11ec-9dd3-fd6c979089f4,5c24d4c6-c0d4-11ec-9dd3-fd6c979089f4,5c731bd8-c0d4-11ec-9dd3-fd6c979089f4,5cc819aa-c0d4-11ec-9dd3-fd6c979089f4,5ddd332c-c0d4-11ec-9dd3-fd6c979089f4,5e3d2d7e-c0d4-11ec-9dd3-fd6c979089f4,5e92c7da-c0d4-11ec-8a91-ed5e4b2cd182,5eef18ac-c0d4-11ec-8a91-ed5e4b2cd182,5fdb7430-c0d4-11ec-9dd3-fd6c979089f4,604de512-c0d4-11ec-9dd3-fd6c979089f4,60b7f1ce-c0d4-11ec-8a91-ed5e4b2cd182,61405bb0-c0d4-11ec-8a91-ed5e4b2cd182,6193f9a4-c0d4-11ec-9dd3-fd6c979089f4,61e74a12-c0d4-11ec-8a91-ed5e4b2cd182,6239af86-c0d4-11ec-9dd3-fd6c979089f4,6293b6b4-c0d4-11ec-8a91-ed5e4b2cd182,62fd7506-c0d4-11ec-8a91-ed5e4b2cd182,63533628-c0d4-11ec-8a91-ed5e4b2cd182,63a32aea-c0d4-11ec-8a91-ed5e4b2cd182,63f56948-c0d4-11ec-9dd3-fd6c979089f4,6447cf5c-c0d4-11ec-8a91-ed5e4b2cd182,6496b25a-c0d4-11ec-9dd3-fd6c979089f4,652b2a2c-c0d4-11ec-9dd3-fd6c979089f4,65b36d4e-c0d4-11ec-8a91-ed5e4b2cd182,66153c0e-c0d4-11ec-9dd3-fd6c979089f4,66649490-c0d4-11ec-9dd3-fd6c979089f4,66b63702-c0d4-11ec-9dd3-fd6c979089f4,6706a0f4-c0d4-11ec-9dd3-fd6c979089f4,67581c56-c0d4-11ec-9dd3-fd6c979089f4,67a5c728-c0d4-11ec-9dd3-fd6c979089f4,67f3990a-c0d4-11ec-9dd3-fd6c979089f4,6845899c-c0d4-11ec-9dd3-fd6c979089f4,6892711e-c0d4-11ec-9dd3-fd6c979089f4,68e63670-c0d4-11ec-9dd3-fd6c979089f4,693a70f2-c0d4-11ec-9dd3-fd6c979089f4,69dbba04-c0d4-11ec-9dd3-fd6c979089f4');
//test_log("RECEPCIONES\n=====================\n");
//test_log(implode(",", $receps));