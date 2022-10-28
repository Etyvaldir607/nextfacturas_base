<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Verifica si es una peticion ajax
if (is_ajax()) {
	// Instancia el objeto
	$objeto = array(
		'date' => date_decode(date('Y-m-d'), $_institution['formato']),
		'hours' => date('H'),
		'minutes' => date('i'),
		'seconds' => date('s'),
	);
	
	// Envia respuesta
	echo json_encode($objeto);
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>