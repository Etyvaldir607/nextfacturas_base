<?php
ini_set('display_errors', 1);error_reporting(E_ALL);
require_once __DIR__ . '/siat.php';

//print_r($_GET);
$items = siat_tipos_punto_venta(0, 0);

header('Content-type: application/json');
die(json_encode(['data' => $items]));
