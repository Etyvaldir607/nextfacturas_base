<?php
require_once __DIR__ . '/siat.php';
ini_set('display_errors', 1);error_reporting(E_ALL);
$method = $_SERVER['REQUEST_METHOD'];

$rdata = null;
function crear_evento(object $data)
{
	if( !isset($data->sucursal_id) || (int)$data->sucursal_id < 0 )
		throw new Exception('Sucursal invalida');
	if( !isset($data->puntoventa_id) || (int)$data->puntoventa_id < 0 )
		throw new Exception('Punto de venta invalida');
	if( !isset($data->evento_id) || (int)$data->evento_id <= 0 )
		throw new Exception('Punto de venta invalida');
	if( !isset($data->fecha_inicio) )
		throw new Exception('Debe seleccionar una fecha de inicio');
	if( strtotime($data->fecha_inicio) > time() )
		throw new Exception('Fecha inicio invalida');
	$eventoSiat = siat_buscar_tipo_evento($data->evento_id);
	if( !$eventoSiat )
		throw new Exception('El codigo de evento no existe en SIAT');
		
	if( in_array($data->evento_id, [5, 6, 7]) )
	{
		if( !isset($data->fecha_fin) )
			throw new Exception('Debe seleccionar una fecha de finalizacion para el evento');
		if( strtotime($data->fecha_fin) <= strtotime($data->fecha_inicio) )
			throw new Exception('La fecha y hora fin del evento no puede ser inferior a la fecha y hora de inicio');
			
		if( empty($data->cufd_evento) )
			throw new Exception('Debe seleccionar CUFD para el evento');
			
		$query = sprintf("	SELECT id,creation_date 
							FROM mb_siat_cufd 
							WHERE user_id = %d AND sucursal_id = %d AND puntoventa_id = %d AND codigo = '%s' 
							LIMIT 1",
			1,
			$data->sucursal_id,
			$data->puntoventa_id,
			$data->cufd_evento
			);
		if( !($cufd = $db->query($query)->fetch_first()) )
			throw new Exception('El CUFD para el evento no existe');
			
		if( strtotime($data->fecha_inicio) < strtotime($cufd->creation_date) )
			throw new Exception('La fecha de inicio no puede ser inferior a la fecha de creacion del CUFD');
							
	}
	else
	{
		$cufd = siat_obtener_cufd($data->sucursal_id, $data->puntoventa_id);
		$data->cufd_evento 	= $cufd->codigo;
		$data->fecha_fin 	= null;
	}
	unset($data->meta_col_id, $data->meta);
	$data->descripcion 	= $eventoSiat->descripcion;
	$data->user_id 		= 1;
	$data->fecha_inicio = date('Y-m-d H:i:s', strtotime($data->fecha_inicio));
	$pv 	= siat_eventos_crear($data);
	
	return $pv;
}
if( $method == 'POST' )
{
	try
	{
		$json = file_get_contents('php://input');
		if( empty($json) )
			throw new Exception('Datos invalidos');
		
		$data 	= json_decode($json);
		$pv = crear_evento($data);
		header('Content-type: application/json');
		die(json_encode(['data' => $pv]));
	}
	catch(Exception $e)
	{
		http_response_code(500);
		header('Content-type: application/json');
		die(json_encode(['status' => 'error', 'error' => $e->getMessage()]));
	}
}
elseif( $method == 'DELETE' )
{
	die('DELETE not implemented');
}
else
{
	$limit 		= 25;
	$page 		= isset($params[0]) ? $params[0] : 1;
	$sucursal 	= isset($params[1]) ? $params[1] : 0;
	$puntoventa = isset($params[2]) ? $params[2] : 0;
	
	if( $sucursal == 'anular' )
	{
		$id = (int)$page;
		siat_eventos_anular($id);
	}
	elseif( $sucursal == 'crear' )
	{
		$codigoEvento = (int)$params[0];
		$data = (object)[
			'sucursal_id' 	=> (int)$params[2],
			'puntoventa_id'	=> (int)$params[3],
			'evento_id'		=> $codigoEvento,
			'fecha_inicio'	=> date('Y-m-d H:i:s')
		];
		//print_r($data);die;
		crear_evento($data);
		if( isset($_SERVER['HTTP_REFERER']) )
		{
			header('Location: ' . $_SERVER['HTTP_REFERER']);
			die;
		}
	}
	elseif( $sucursal == 'cerrar' )
	{
		$id = isset($params[0]) ? (int)$params[0] : null;
		if( !$id )
			die('Identificador invalido, no se puede cerrar evento');
		siat_eventos_cerrar($id);
		if( isset($_SERVER['HTTP_REFERER']) )
		{
			header('Location: ' . $_SERVER['HTTP_REFERER']);
			die;
		}
	}
	else
	{
		
		//var_dump($page, $sucursal, $puntoventa);
		$items = siat_eventos_db((int)$sucursal, (int)$puntoventa, (int)$page, $limit);
		$rdata = [];
		foreach($items as $item)
		{
			$data = (object)$item;
			//print_r($data);die;
			if( $data->data )
				$data->data = json_decode(preg_replace('/[[:cntrl:]]/', '', $data->data), null, 20, JSON_THROW_ON_ERROR);
			$rdata[] = $data;
		}
	}
	
}
header('Content-type: application/json');
die(json_encode(['data' => $rdata]));
