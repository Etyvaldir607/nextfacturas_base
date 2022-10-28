<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene el id_menu
$id_menu = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el menu
$menu = $db->from('sys_menus')->where('id_menu', $id_menu)->fetch_first();

// Verifica si el menu existe
if ($menu) {
	// Elimina el menu
	$db->delete()->from('sys_menus')->where('id_menu', $id_menu)->limit(1)->execute();

	// Verifica si fue el menu eliminado
	if ($db->affected_rows) {
		// Define la variable de error
		$_SESSION[temporary] = 'true';
	}

	// Redirecciona a la pagina principal
	redirect('?/' . tools . '/menus_listar');
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>