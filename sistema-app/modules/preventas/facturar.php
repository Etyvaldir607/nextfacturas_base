<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene el id_proforma
$id_proforma = (isset($params[0])) ? $params[0] : 0;

// Obtiene la proforma
$proforma = $db->from('inv_proformas')
			   ->where('id_proforma', $id_proforma)
			   ->fetch_first();

// Verifica si existe la proforma
if ($proforma) {
	// Redirecciona a la pagina principal
	redirect('?/electronicas/facturar/' . $id_proforma);
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>