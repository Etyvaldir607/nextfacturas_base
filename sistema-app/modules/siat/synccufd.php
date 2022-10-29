<?php
require_once __DIR__ . '/siat.php';

$sucursal 	= (int)filter_input(INPUT_GET, 'sucursal');
$puntoventa = (int)filter_input(INPUT_GET, 'puntoventa');
$sucursal	= $sucursal < 0 ? 0 : $sucursal;
$puntoventa	= $puntoventa < 0 ? 0 : $puntoventa; 

$res 		= siat_obtener_cufd($sucursal, $puntoventa);
die(json_encode(['data' => $res]));
