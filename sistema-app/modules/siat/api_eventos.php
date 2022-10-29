<?php
require_once __DIR__ . '/siat.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
$method 		= $_SERVER['REQUEST_METHOD'];
$input 			= json_decode(file_get_contents('php://input'));
$function  		= isset($params[0]) ? $params[0] : null;

//die(json_encode(['method' => $method,'function' => $function,'input' => $input]));

//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'obtener_eventos') { //lista de eventos generados

	$sucursal 	= isset($params[1]) ? intval($params[1]) : 0;
	$puntoventa = isset($params[2]) ? intval($params[2]) : 0;
	$page 		= isset($params[3]) ? $params[3] : 1;
	$limit 		= 25;

	$items = siat_eventos_db((int)$sucursal, (int)$puntoventa, (int)$page, $limit);
	//die(json_encode(['method' => $items,'function' => $function,'input' => $input]));
	$rdata = [];
	foreach ($items as $item) {
		$data = (object)$item;
		//print_r($data);die;
		if ($data->data)
			$data->data = json_decode(preg_replace('/[[:cntrl:]]/', '', $data->data), null, 20, JSON_THROW_ON_ERROR);
		$rdata[] = $data;
	}
	die(json_encode(['data' => $rdata])); //$rdata ?: []
}

//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method === 'GET' && $function === 'stats') { //total de facturas

	$id_evento 	= isset($params[1]) ? $params[1] : 0;

	$invoices 		= siat_eventos_obtener_egresos($id_evento);
	$total			= count($invoices);

	die(json_encode(['data' => $invoices, 'total_facturas' => $total])); //$rdata ?: []
}


//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method == 'POST' && $function === 'cerrar_evento') { //cerrar evento

	//die(json_encode(['method' => $method,'function' => $function,'input' => $input]));
	try {
		$id_evento = $input->id_evento;
		if (!$id_evento)
			die('Identificador invalido, no se puede cerrar evento');

		$siat_eventos_cerrar = siat_eventos_cerrar($id_evento);

		die(json_encode(['data' => $siat_eventos_cerrar]));
	} catch (Exception $e) {
		http_response_code(500);
		die(json_encode(['status' => 'error', 'error' => $e->getMessage()]));
	}
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method == 'GET' && $function === 'validar_recepcion') { //cerrar evento
	//die(json_encode(['method' => $method,'function' => $function,'input' => $input]));
	try {
		$id_evento 	= isset($params[1]) ? $params[1] : 0;

		$localEvent = siat_eventos_obtener($id_evento);
		if (!$localEvent)
			throw new Exception('El evento no existe');
		$siat_eventos_verificar = siat_eventos_verificar($localEvent);

		die(json_encode(['data' => $siat_eventos_verificar]));
	} catch (Exception $e) {
		http_response_code(500);
		die(json_encode(['status' => 'error', 'error' => $e->getMessage()]));
	}
}
//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
if ($method == 'PUT' && $function === 'anular_evento') { //anular evento
	try {
		$id_evento = $input->id;
		//die(json_encode(['data' => $input]));
		if (!$id_evento)
			die('Identificador invalido, no se puede cerrar evento');
		$siat_eventos_anular = siat_eventos_anular($id_evento);
		die(json_encode(['data' => $siat_eventos_anular]));
	} catch (Exception $e) {
		http_response_code(500);
		die(json_encode(['status' => 'error', 'error' => $e->getMessage()]));
	}
}

//********************************************************************************************************************************************************
//********************************************************************************************************************************************************

if ($method === 'POST' && $function === 'crear_evento') { //crear evento
	//die(json_encode(['method' => $method,'function' => $function,'input' => $input]));

	try {
		$event = new stdClass();
		$event->evento_id  		= $input->evento_id;
		$event->puntoventa_id  	= $input->puntoventa_id;
		$event->sucursal_id  	= $input->sucursal_id;
		$event->fecha_inicio 	= date('Y-m-d H:i:s');
		$event->user_id 		= 1;
		//die(json_encode(['data' => $event]));
		validate_evento($event);
		$eventoSiat = siat_buscar_tipo_evento($event->evento_id, $event->sucursal_id, $event->puntoventa_id);
		//die(json_encode(['data' => $eventoSiat]));
		if (!$eventoSiat)
			throw new Exception('El codigo de evento no existe en SIAT');
		$event->descripcion 	= $eventoSiat->descripcion;

		if (in_array($event->evento_id, [5, 6, 7])) {//eventos contigencia
			//$event->cafc  			= $input->cafc;
			$event->fecha_inicio 		= date('Y-m-d H:i:s', strtotime($input->fecha_inicio));
			$event->fecha_fin  			= date('Y-m-d H:i:s', strtotime($input->fecha_fin));
			$event->cufd_evento  		= $input->cufd_evento;

			$config 	= siat_get_config();
			$event->cafc  				= $config->cafc;
			validate_evento_contigencia($event);
		} else {
			$cufd 					= siat_obtener_cufd($event->sucursal_id, $event->puntoventa_id);
			$event->cufd_evento 	= $cufd->codigo;
			$event->fecha_fin 		= null;
		}
		$siat_eventos_crear 	= siat_eventos_crear($event);
		die(json_encode(['data' => $siat_eventos_crear]));
	} catch (Exception $e) {
		http_response_code(500);
		die(json_encode(['status' => 'error', 'error' => $e->getMessage()]));
	}
}

//********************************************************************************************************************************************************
//********************************************************************************************************************************************************
function validate_evento(object $event)
{
	if (!isset($event->sucursal_id) || (int)$event->sucursal_id < 0)
		throw new Exception('Sucursal invalida');
	if (!isset($event->puntoventa_id) || (int)$event->puntoventa_id < 0)
		throw new Exception('Punto de venta invalida');
	if (!isset($event->evento_id) || (int)$event->evento_id <= 0)
		throw new Exception('Punto de venta invalida');
	if (!isset($event->fecha_inicio))
		throw new Exception('Debe seleccionar una fecha de inicio');
	if (strtotime($event->fecha_inicio) > time())
		throw new Exception('Fecha inicio invalida');
}

function validate_evento_contigencia(object $event)
{
	global $db;
	if (!isset($event->fecha_fin))
		throw new Exception('Debe seleccionar una fecha de finalizacion para el evento');
	if (strtotime($event->fecha_fin) <= strtotime($event->fecha_inicio))
		throw new Exception('La fecha y hora fin del evento no puede ser inferior a la fecha y hora de inicio');

	if (empty($event->cufd_evento))
		throw new Exception('Debe seleccionar CUFD para el evento');

	$query = sprintf(
		"SELECT id,creation_date 
						FROM mb_siat_cufd 
						WHERE user_id = %d AND sucursal_id = %d AND puntoventa_id = %d AND codigo = '%s' 
						LIMIT 1",
		1,
		$event->sucursal_id,
		$event->puntoventa_id,
		$event->cufd_evento
	);
	if (!($cufd = $db->query($query)->fetch_first()))
		throw new Exception('El CUFD para el evento no existe');

	if (strtotime($event->fecha_inicio) < strtotime($cufd['creation_date']))
		throw new Exception('La fecha de inicio no puede ser inferior a la fecha de creacion del CUFD');
}


die(json_encode(['data' => 'sin acciones']));
