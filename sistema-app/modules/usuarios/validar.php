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
	if (isset($_POST['username'])) {
		// Obtiene los datos del user
		$username = $_POST['username'];

		// Obtiene los usuarios con el valor buscado
		$user = $db->select('id_user, username')
				   ->from('sys_users')
				   ->where('username', $username)
				   ->fetch_first();

		// Verifica si existe coincidencias
		if ($user) {
			$response = array('valid' => false, 'message' => 'El nombre "' . $user['username'] . '" no esta disponible');
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