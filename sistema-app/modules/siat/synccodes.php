<?php
require_once __DIR__ . '/siat.php';
$type = isset($params[0]) ? $params[0] : null;

$data = null;
if( $type == 'eventos' )
{
	$data = siat_tipos_eventos(0, 0);
}
else
{
	$cuis = siat_obtener_cuis();
	$data = $cuis;
}
header('Content-type: application/json');
$res = ['data' => $data];
die(json_encode($res));
