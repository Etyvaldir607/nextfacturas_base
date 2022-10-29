<?php
require_once __DIR__ . '/siat.php';
$res = siat_obtener_cuis();
die(json_encode($res));
