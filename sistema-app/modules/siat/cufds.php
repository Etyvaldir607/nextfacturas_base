<?php
ini_set('display_errors', 1);error_reporting(E_ALL);
require_once __DIR__ . '/siat.php';

if( $_SERVER['REQUEST_METHOD'] == 'POST' )
{
	try
	{
		$json = file_get_contents('php://input');
		if( empty($json) )
			throw new Exception('Datos invalidos');
		
		
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
	$page = 1;
	$limit = 25;
	$items = siat_cufds_db($page, $limit);
	header('Content-type: application/json');
	die(json_encode(['data' => $items ?: []]));
}

