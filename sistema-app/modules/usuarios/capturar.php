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
	if (isset($_POST['id_user']) && isset($_POST['avatar']) && isset($_POST['avatar_x']) && isset($_POST['avatar_y']) && isset($_POST['avatar_w']) && isset($_POST['avatar_h'])) {
		// Obtiene los datos del user
		$id_user = trim($_POST['id_user']);
		$avatar = trim($_POST['avatar']);
		$avatar_x = trim($_POST['avatar_x']);
		$avatar_y = trim($_POST['avatar_y']);
		$avatar_w = trim($_POST['avatar_w']);
		$avatar_h = trim($_POST['avatar_h']);

		// Obtiene el user
		$user = $db->from('sys_users')->where('id_user', $id_user)->fetch_first();
		
		// Verifica si existe el user
		if ($user) {
			// Obtiene el avatar del user
			$avatar_user = $user['avatar'];

			// Verifica si el avatar esta almacenada en la base de datos
			if ($avatar_user != '') {
				// Verifica si el avatar esta almacenada en la carpeta de profiles
				if (file_exists(profiles . '/' . $avatar_user)) {
					// Elimina el archivo
					unlink(profiles . '/' . $avatar_user);
				}

				// Verifica si el avatar esta almacenada en la carpeta de profiles
				if (file_exists(profiles . '/' . $avatar_user)) {
					// Elimina el archivo
					unlink(profiles . '/' . $avatar_user);
				}
			}

			// Acorta el avatar en modo texto
			list(, $avatar) = explode(';', $avatar);
			list(, $avatar) = explode(',', $avatar);

			// Genera el archivo a partir de la informacion
			$avatar = base64_decode($avatar);
			$avatar = imagecreatefromstring($avatar);

			// Obtiene las rutas de los nuevos avatares
			$nuevo_nombre = md5(prefix . random_string() . $id_user) . '.jpg';
			$ruta_avatar_grande = profiles . '/' . $nuevo_nombre;
			$ruta_avatar_pequena = profiles . '/' . $nuevo_nombre;

			// Crea el avatar grande
			$avatar_grande = imagecreatetruecolor(650, 650);
			$fondo = imagecolorallocate($avatar_grande, 255, 255, 255);
			imagefill($avatar_grande, 0, 0, $fondo);
			imagecopyresized($avatar_grande, $avatar, 0, 0, $avatar_x, $avatar_y, 650, 650, $avatar_w, $avatar_h);

			// Crea el avatar en miniatura
			$avatar_pequena = imagecreatetruecolor(100, 100);
			$fondo = imagecolorallocate($avatar_pequena, 255, 255, 255);
			imagefill($avatar_pequena, 0, 0, $fondo);
			imagecopyresized($avatar_pequena, $avatar, 0, 0, $avatar_x, $avatar_y, 100, 100, $avatar_w, $avatar_h);

			// Verifica si se creo el avatar grande y el avatar pequena
			if (imagejpeg($avatar_grande, $ruta_avatar_grande, 90) && imagejpeg($avatar_pequena, $ruta_avatar_pequena, 90)) {
				// Destruimos los avatares temporales
				imagedestroy($avatar_grande);
				imagedestroy($avatar_pequena);

				// Actualiza la informacion
				$db->where(array('id_user' => $id_user))->update('sys_users', array('avatar' => $nuevo_nombre));
				
				// Define el mensaje de exito
				$_SESSION[temporary] = array(
					'alert' => 'success',
					'title' => 'Advertencia!',
					'message' => 'El avatar se guardó correctamente.'
				);
			} else {
				// Define el mensaje de error
				$_SESSION[temporary] = array(
					'alert' => 'danger',
					'title' => 'Advertencia!',
					'message' => 'Se produjo un error al subir el avatar.'
				);
			}

			// Redirecciona a la pagina principal
			redirect(back());
		} else {
			// Error 404
			require_once not_found();
			exit;
		}
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