<?php
//ini_set('display_errors', 1);error_reporting(E_ALL);
require_once __DIR__ . '/siat.php';

if( $_SERVER['REQUEST_METHOD'] == 'POST' )
{
	try
	{
		$json = file_get_contents('php://input');
		if( empty($json) )
			throw new Exception('Datos invalidos');
		
		$data 	= json_decode($json);
		$pv 	= siat_crear_puntoventa($data->sucursal_id, $data->tipo_id, $data->nombre);
		header('Content-type: application/json');
		die(json_encode(['data' => $pv]));
	}
	catch(Exception $e)
	{
		http_response_code(500);
		die(json_encode(['status' => 'error', 'error' => $e->getMessage()]));
	}
}
if( $_SERVER['REQUEST_METHOD'] == 'DELETE' )
{
	
}
else
{
	$items = siat_puntos_venta_db();
	//var_dump($items);
	header('Content-type: application/json');
	die(json_encode(['data' => $items ?: []]));
}

