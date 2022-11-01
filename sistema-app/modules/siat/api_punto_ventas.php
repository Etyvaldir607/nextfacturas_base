<?php
require_once __DIR__ . '/siat.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
$method 		= $_SERVER['REQUEST_METHOD'];
$input 			= json_decode(file_get_contents('php://input')); //inpust params post
$function  		= isset($params[0]) ? $params[0] : null;

//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
// if ($method === 'GET' && $function === 'obtener_punto_ventas') {

// 	$sucursal 	= isset($params[1]) ? $params[1] : 0;
// 	$puntoventa = isset($params[2]) ? $params[2] : 0;

// 	$siat_puntos_venta_db = siat_puntos_venta_db($sucursal, $puntoventa);
// 	die(json_encode(['data' => $siat_puntos_venta_db]));
	
// }
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'POST' && $function === 'crear_punto_venta') {

	try {
		$sucursal 			= $input->sucursal_id;
		$tipo_punto_venta 	= $input->codigo_tipo_punto_venta; 
		$nombre 	 		= $input->nombre_punto_venta;
		$descripcion 		= $input->descripcion;

		if($tipo_punto_venta == 1 ){
			$contrato_nit 			= $input->contrato_nit;
			$contrato_numeral		= $input->contrato_nro;
			$contrato_start_date	= $input->contrato_fecha_inicio;
			$contrato_end_date		= $input->contrato_fecha_fin;

			$resp 	= siat_crear_puntoventa_comisionista($sucursal, $tipo_punto_venta, $nombre, $descripcion, $contrato_nit, $contrato_numeral, $contrato_start_date, $contrato_end_date);
			//die(json_encode(['data' => $resp]));
			$transaccion    = $resp->RespuestaPuntoVentaComisionista->transaccion;
	
			die(json_encode([
				"data"      => $resp,//siat
				"status"    => $transaccion ? 200 : 500, //status 201 creacion
				"title"     => $transaccion ? "Exito !!&nbsp;" : "Error !!&nbsp;",
				"type"      => $transaccion ? "success" : "warning", //info  warning
				"icon"      => $transaccion ? "glyphicon glyphicon-ok" : "glyphicon glyphicon-remove", //"glyphicon glyphicon-info-sign",
				"message"   => $transaccion ?  "PUNTO DE VENTA CON EL ID : ¨{$resp->RespuestaPuntoVentaComisionista->codigoPuntoVenta}¨ REGISTRADO CORRECTAMENTE EN SIAT" :sb_siat_message($resp->RespuestaRegistroPuntoVenta)
			]));
	
		}else{
			$resp 	= siat_crear_puntoventa($sucursal, $tipo_punto_venta, $nombre, $descripcion);
			//die(json_encode(['data' => $resp]));
			$transaccion    = $resp->RespuestaRegistroPuntoVenta->transaccion;
	
			die(json_encode([
				"data"      => $resp,//siat
				"status"    => $transaccion ? 200 : 500, //status 201 creacion
				"title"     => $transaccion ? "Exito !!&nbsp;" : "Error !!&nbsp;",
				"type"      => $transaccion ? "success" : "warning", //info  warning
				"icon"      => $transaccion ? "glyphicon glyphicon-ok" : "glyphicon glyphicon-remove", //"glyphicon glyphicon-info-sign",
				"message"   => $transaccion ?  "PUNTO DE VENTA CON EL ID : ¨{$resp->RespuestaRegistroPuntoVenta->codigoPuntoVenta}¨ REGISTRADO CORRECTAMENTE EN SIAT" :sb_siat_message($resp->RespuestaRegistroPuntoVenta)
			]));

		}
	
	} catch (Exception $e) {
		http_response_code(500);
		die(json_encode(['status' => 'error', 'error' => $e->getMessage()]));
	}
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method == 'PUT' && $function === 'cerrar_punto_venta') { //crear evento
	//die(json_encode(['data' => $input]));
	try {
		$puntoventa 	= isset($input->punto_venta_id) ? $input->punto_venta_id : null;
		$resp           = siat_cierre_punto_venta($puntoventa);
		// $siat_message = sb_siat_message($resp->RespuestaCierrePuntoVenta);
		// die(json_encode(['data' => $siat_message]));
		$transaccion    = $resp->RespuestaCierrePuntoVenta->transaccion;
		$codigo_pv = null;

		if ($transaccion) { //si la respuesta es correcta cambiamos el estado de la factura
			$codigo_pv      = $resp->RespuestaCierrePuntoVenta->codigoPuntoVenta;
			$db->where('codigo', $codigo_pv)->update('mb_siat_puntos_venta', ['status' => 'closed']);
		}

		die(json_encode([
			"data"      => $resp,//siat
			"status"    => $transaccion ? 200 : 500, //status 201 creacion
			"title"     => $transaccion ? "Exito !!&nbsp;" : "Error !!&nbsp;",
			"type"      => $transaccion ? "success" : "warning", //info  warning
			"icon"      => $transaccion ? "glyphicon glyphicon-ok" : "glyphicon glyphicon-remove", //"glyphicon glyphicon-info-sign",
			"message"   => $transaccion ? "PUNTO DE VENTA CON EL ID : ¨{$codigo_pv}¨ CERRADO CORRECTAMENTE EN SIAT" :sb_siat_message($resp->RespuestaCierrePuntoVenta)
		]));

	} catch (Exception $e) {
		http_response_code(500);
		die(json_encode(['status' => 'error', 'error' => $e->getMessage()]));
	}
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************