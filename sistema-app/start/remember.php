<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Inicializa las sessiones
session_start();

// Verifica si la variable de session existe
if (isset($_SESSION[user])){
	// Redirecciona al modulo index
	redirect(index_private);
} else {
	// Verifica si la cookie no expiro
	if (isset($_COOKIE[remember])) {
		// Obtiene los parametros
		$remember = explode('|', $_COOKIE[remember]);
		$username = $remember[0];
		$password = $remember[1];
		$locale = $remember[2];

		// Importa la conexion a la base de datos
		require config . '/database.php';

		// Realiza la consulta
		$user = $db->query("SELECT id_user, rol_id 
		                    FROM sys_users 
		                    WHERE (MD5(username) = MD5('$username') OR MD5(email) = MD5('$username')) AND password = '$password' AND active = '1' LIMIT 1
		                    ")->fetch_first();

		// Verifica si la cantidad de usuarios es mayor a ceros
		if (is_array($user)) {
			// Instancia la variable de session con los datos del usuario
			$_SESSION[user] = $user;

			// Instancia la variable de session con la ubicacion
			$_SESSION[locale] = $locale;

			// Redirecciona a la pagina principal
			redirect(index_private);
		} else {
			// Instancia la variable de session para mostrar el error
			$_SESSION[temporary] = 'true';

			// Redirecciona al modulo index con error
			redirect(index_public);
		}
	}
}