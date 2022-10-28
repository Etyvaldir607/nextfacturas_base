<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene el id_terminal
$id_terminal = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene la terminal
$terminal = $db->from('inv_terminales')->where('id_terminal', $id_terminal)->fetch_first();

// Verifica si el terminal existe
if ($terminal) {
	// Definimos las cabeceras
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="locale.js"');
	header('Expires: 0');

	// Escribimos el contenido del archivo
	echo "window.l='" . $terminal['identificador'] . "';";

	// Cerramos la pagina
	exit;
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>