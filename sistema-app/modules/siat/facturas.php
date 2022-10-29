<?php
ini_set('display_errors', 1);error_reporting(E_ALL);
require_once __DIR__ . '/siat.php';
require_once __DIR__ . '/Resources/ResourceInvoice.php';


$method 	= $_SERVER['REQUEST_METHOD'];
$task 		= isset($params[1]) ? $params[1] : null;
$response 	= null;

if( $method == 'POST' )
{
	
}
elseif( $method == 'DELETE' )
{
	
}
else //GET
{
	if( in_array($task, ['view', 'print']) )
	{
		$id = isset($params[0]) ? (int)$params[0] : null;
		$tpl = isset($params[2]) ? $params[2] : null;
		try
		{
			$invoice 	= null;
			$egreso		= null;
			$pdf 		= siat_factura_print($id, $tpl, $egreso, $invoice);
			$pdf->stream(sprintf("factura-%d.pdf", $invoice->invoice_id), ['Attachment' => 0]);
			die;
		}
		catch(Exception $e)
		{
			$response 	= ['code' => 500, 'status' => 'error', 'error' => $e->getMessage()];
		}
	}
	else
	{
		$sucursal 	= isset($params[0]) ? (int)$params[0] : 0;
		$puntoventa = isset($params[1]) ? (int)$params[1] : 0;
		$items 		= [];
		foreach(siat_obtener_facturas($sucursal, $puntoventa) as $item)
		{
			$items[] = new ResourceInvoice((object)$item);
		}
		$response 	= ['code' => 200, 'status' => 'ok', 'data' => $items];
	}
	
}
http_response_code($response['code']);
header('Content-Type: application/json');
die(json_encode($response));