<?php
if (is_post()) {
	// Verifica si fueron enviados los datos
	if (isset($_POST['locale']) && isset($_POST['username']) && isset($_POST['password'])) {
		// Importa la configuracion para el manejo de la base de datos
		require config . '/database.php';

		// Obtiene los datos para la validacion
		$locale = trim($_POST['locale']);
		$username = trim($_POST['username']);
		$password = trim($_POST['password']);
		$remember = (isset($_POST['remember'])) ? 1 : 0;

		// Encripta los datos para la comparacion con la base de datos
		$password = sha1(prefix . md5($password));

		// Obtiene los usuarios que cumplen la condicion
		$user = $db->query("SELECT id_user, rol_id 
		                    FROM sys_users 
		                    WHERE (MD5(username) = MD5('$username') OR MD5(email) = MD5('$username')) AND password = '$password' AND active = '1' 
		                    LIMIT 1
		                    ")->fetch_first();

/*
		$user = $db->query("SELECT id_user, rol_id 
			FROM sys_users 
			LIMIT 1
		")->fetch_first();
		*/

		// Inicializa las sessiones
		session_start();

		// Verifica si la cantidad de usuarios es mayor a cero
		if (is_array($user)) {
			// Instancia la variable de session con los datos del usuario
			$_SESSION[user] = $user;

			// Instancia la variable de session con la ubicacion
			$_SESSION[locale] = $locale;

			// Verifica si fue marcado la casilla recuerdame
			if ($remember == 1) {
				setcookie(remember, $username . '|' . $password . '|' . $locale, time() + (3000 * 60 * 12) ); 
			} else {
				setcookie(remember, '', time() + (3000 * 60 * 12) );
			}

			// Actualiza el ultimo ingreso del usuario
			$where = array('id_user' => $user['id_user']);
			$user = array(
				'login_at' => date('Y-m-d H:i:s'),
				'logout_at' => '0000-00-00 00:00:00'
			);
			$db->where($where)->update('sys_users', $user);

			// Redirecciona a la pagina principal
			redirect(index_private);
		} else {
			// Define la variable de error
			$_SESSION[temporary] = 'true';

			// Redirecciona al modulo index con error
			redirect(index_public);
		}
	} else {
		// Redirecciona al modulo index
		redirect(index_public);
	}
} else {
	// Redirecciona al modulo index
	redirect(index_public);
}

?>