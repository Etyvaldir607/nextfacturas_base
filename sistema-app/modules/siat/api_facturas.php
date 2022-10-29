<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/siat.php';
require_once __DIR__ . '/Resources/ResourceInvoice.php';

$method 		= $_SERVER['REQUEST_METHOD'];
$input 			= json_decode(file_get_contents('php://input'));
$function  		= isset($params[0]) ? $params[0] : null;

//die(json_encode(['data' => $function]));
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'obtener_facturas') {

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;
	$items 		= [];
	foreach (siat_obtener_facturas($sucursal, $puntoventa) as $item) {
		$items[] = new ResourceInvoice((object)$item);
	}
	die(json_encode(['data' => $items]));
}

//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'estado_factura') {

	$egreso_id 	= isset($params[1]) ? (int)$params[1] : 0;
	$resp       = siat_estado_factura($egreso_id);

	$transaccion    = $resp->RespuestaServicioFacturacion->transaccion;

	die(json_encode([
		"data"      => $resp, //siat
		"status"    => intval($resp->RespuestaServicioFacturacion->codigoEstado),
		"title"     => null,
		"type"      => null,
		"icon"      => null,
		"message"   => $transaccion ?
			'FACTURA ' . $resp->RespuestaServicioFacturacion->codigoDescripcion . ' COD. RECEPCION : ' . $resp->RespuestaServicioFacturacion->codigoRecepcion :
			sb_siat_message($resp->RespuestaServicioFacturacion)
	]));
}

//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method == 'PUT' && $function === 'anular_factura') { //crear evento
	try {

		$egreso_id              = $input->invoice_id;
		$motivo_anulacion_id    = $input->codigoMotivo;
		$resp                   = siat_anular_factura($egreso_id, $motivo_anulacion_id);

		$transaccion = $resp->RespuestaServicioFacturacion->transaccion;

		if ($transaccion) { //si la respuesta es correcta cambiamos el estado de la factura
			$db->where('id_factura', $egreso_id)->update('inv_egresos_facturas', ['status' => 'void']);
		}

		die(json_encode([
			"data"      => $resp,
			"status"    => $transaccion ? 200 : 500, //status 201 creacion
			"title"     => $transaccion ? "Exito!! " : "Error!! ",
			"type"      => $transaccion ? "success" : "warning", //info  warning
			"icon"      => $transaccion ? "glyphicon glyphicon-ok" : "glyphicon glyphicon-remove", //"glyphicon glyphicon-info-sign",
			"message"   => $transaccion ? $resp->RespuestaServicioFacturacion->codigoDescripcion : $resp->RespuestaServicioFacturacion->mensajesList->descripcion
		]));
	} catch (Exception $e) {
		http_response_code(500);
		die(json_encode(['status' => 'error', 'error' => $e->getMessage()]));
	}
}


//********************************************************************************************************************************************************
//********************************************************************************************************************************************************



die(json_encode(['data' => 'sin acciones']));
