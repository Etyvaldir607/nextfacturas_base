<?php
require_once __DIR__ . '/siat.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
$method 		= $_SERVER['REQUEST_METHOD'];
$input 			= json_decode(file_get_contents('php://input'));
$function		= isset($params[0]) ? $params[0] : null;

//die(json_encode(['data' => $method]));
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'sync_cuis') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_obtener_cuis = siat_obtener_cuis($sucursal, $puntoventa);

	die(json_encode(['data' => $siat_obtener_cuis]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'sync_cufd') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;
	$date 		= isset($params[3]) ? $params[3] : null;

	$siat_obtener_cufd   = siat_obtener_cufd($sucursal, $puntoventa, $date);

	die(json_encode(['data' => $siat_obtener_cufd]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'sync_actividades') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_actividades = siat_actividades($sucursal, $puntoventa);
	die(json_encode(['data' => $siat_actividades]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'sync_fecha_hora') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_fecha_hora = siat_fecha_hora($sucursal, $puntoventa);
	die(json_encode(['data' => $siat_fecha_hora]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'sync_actividades_documento_sector') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_actividades_documento_sector = siat_actividades_documento_sector($sucursal, $puntoventa);
	die(json_encode(['data' => $siat_actividades_documento_sector]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'sync_leyenda_facturas') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_obtener_leyendas = siat_obtener_leyendas($sucursal, $puntoventa);
	die(json_encode(['data' => $siat_obtener_leyendas]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'sync_mensajes_servicios') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_mensajes_servicios = siat_mensajes_servicios($sucursal, $puntoventa);
	die(json_encode(['data' => $siat_mensajes_servicios]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'sync_producto_servicios') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_obtener_productos = siat_obtener_productos($sucursal, $puntoventa);
	die(json_encode(['data' => $siat_obtener_productos]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'sync_tipo_eventos') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_tipos_eventos = siat_tipos_eventos($sucursal, $puntoventa);
	die(json_encode(['data' => $siat_tipos_eventos]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************

if ($method === 'GET' && $function === 'sync_motivo_anulaciones') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_motivos_anulacion = siat_motivos_anulacion($sucursal, $puntoventa);
	die(json_encode(['data' => $siat_motivos_anulacion]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************

if ($method === 'GET' && $function === 'sync_pais_origen') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_pais_origen = siat_pais_origen($sucursal, $puntoventa);
	die(json_encode(['data' => $siat_pais_origen]));
}

//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'sync_tipos_documento_identidad') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_tipos_documento_identidad = siat_tipos_documento_identidad($sucursal, $puntoventa);
	die(json_encode(['data' => $siat_tipos_documento_identidad]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'sync_tipos_documento_sector') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_tipos_documentos_sector = siat_tipos_documentos_sector($sucursal, $puntoventa);
	die(json_encode(['data' => $siat_tipos_documentos_sector]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'sync_tipos_emision') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_tipos_emision = siat_tipos_emision($sucursal, $puntoventa);
	die(json_encode(['data' => $siat_tipos_emision]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'sync_tipos_habitacion') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_tipos_habitacion = siat_tipos_habitacion($sucursal, $puntoventa);
	die(json_encode(['data' => $siat_tipos_habitacion]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'sync_tipos_metodos_pago') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_tipos_metodos_pago = siat_tipos_metodos_pago($sucursal, $puntoventa);
	die(json_encode(['data' => $siat_tipos_metodos_pago]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'sync_tipos_moneda') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_tipos_moneda = siat_tipos_moneda($sucursal, $puntoventa);
	die(json_encode(['data' => $siat_tipos_moneda]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************

if ($method === 'GET' && $function === 'sync_tipo_punto_ventas') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_tipos_punto_venta = siat_tipos_punto_venta($sucursal, $puntoventa);

	die(json_encode(['data' => $siat_tipos_punto_venta]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************

if ($method === 'GET' && $function === 'sync_tipos_facturas') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_tipos_facturas = siat_tipos_facturas($sucursal, $puntoventa);

	die(json_encode(['data' => $siat_tipos_facturas]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************

if ($method === 'GET' && $function === 'sync_unidades_medida') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_tipos_unidades_medida = siat_tipos_unidades_medida($sucursal, $puntoventa);

	die(json_encode(['data' => $siat_tipos_unidades_medida]));
}











//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'sync_punto_ventas') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$all_status = isset($params[2]) ? $params[2] : false;//false defecto  todos los estados
	$sync 		= isset($params[3]) ? $params[3] : false;//false defecto  sync desde siat

	$siat_sync_puntos_ventas = siat_obtener_puntos_ventas($sucursal, $all_status, $sync);
	die(json_encode(['data' => $siat_sync_puntos_ventas]));
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'sync_punto_ventas222222222222') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;

	$siat_sync_puntos_ventas = siat_obtener_puntos_ventas($sucursal, $puntoventa);
	$siat_sync_puntos_ventas= usort($siat_sync_puntos_ventas, object_sorter('codigo','DESC'));

	die(json_encode(['data' => $siat_sync_puntos_ventas]));
}





function object_sorter($key, $orden = null)
{
	return function ($a, $b) use ($key, $orden) {
		$result =  ($orden == "DESC") ? strnatcmp($b->$key, $a->$key) :  strnatcmp($a->$key, $b->$key);
		return $result;
	};
}
