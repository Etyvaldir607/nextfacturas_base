<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Verifica si es una peticion ajax
if (is_ajax()) {
	// Verifica la existencia de los datos enviados
	if (isset($_POST['codigo_barras'])) {
		// Obtiene los datos del producto
		$codigo_barras = trim($_POST['codigo_barras']);

		// Adiciona prefijo
		$codigo_barras = 'CB' . $codigo_barras;

		// Obtiene los productos con el valor buscado
		$producto = $db->select('id_producto, codigo_barras')->from('inv_productos')->where('codigo_barras', $codigo_barras)->fetch_first();

		// Verifica si existe coincidencias
		if ($producto) {
			$response = array('valid' => false, 'message' => 'El código de barras "' . substr($producto['codigo_barras'], 2) . '" ya fue registrado');
		} else {
			$response = array('valid' => true);
		}

		// Devuelve los resultados
		echo json_encode($response);
	} else {
		// Error 401
		require_once bad_request();
		exit;
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>