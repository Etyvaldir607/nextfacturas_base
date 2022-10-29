<?php
require_once __DIR__ . '/siat.php';
$cuis = siat_obtener_cuis();
$res = ['data' => $cuis];
die(json_encode($res));
