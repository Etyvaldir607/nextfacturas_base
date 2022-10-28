<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Verifica si es una peticion post
if (is_post()) {
	// Verifica la existencia de los datos enviados
	if (isset($_POST['id_user']) && isset($_POST['persona_id'])) {
		// Obtiene los datos del user
		$id_user = trim($_POST['id_user']);
		$persona_id = trim($_POST['persona_id']);

		// Instancia el user
		$user = array(
			'persona_id' => $persona_id
		);

		// Actualiza la informacion
		$db->where('id_user', $id_user)->update('sys_users', $user);
		
		// Redirecciona a la pagina principal
		redirect('?/usuarios/listar');
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