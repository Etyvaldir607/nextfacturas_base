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
	if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['email'])) {
		// Obtiene los datos del user
		$id_user = trim($_user['id_user']);
		$username = trim($_POST['username']);
		$password = $_POST['password'];
		$email = trim($_POST['email']);

		// Verifica si existe la contraseña
		if ($password == '') {
			// Instancia el user
			$user = array(
				'username' => $username,
				'email' => $email
			);
		} else {
			// Instancia el user
			$user = array(
				'username' => $username,
				'password' => sha1(prefix . md5($password)),
				'email' => $email
			);
		}

		// Genera la condicion
		$condicion = array('id_user' => $id_user);

		// Actualiza la informacion
		$db->where($condicion)->update('sys_users', $user);

		// Define la variable para mostrar los cambios
		$_SESSION[temporary] = array(
			'alert' => 'success',
			'title' => 'Actualización satisfactoria!',
			'message' => 'Los datos de usuario de tu perfil han sido actualizados correctamente.'
		);

		// Redirecciona a la pagina principal
		redirect('?/' . home . '/perfil_ver');
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