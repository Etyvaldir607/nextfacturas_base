<?php

/**
 * SimplePHP - Simple Framework PHP
 *
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Funcion que redimensiona la imagen
function resize($image_width = 0, $image_height = 0, $length = 0, $dimention = false) {
	$ratio = $image_width / $image_height;

	if ($dimention) {
		$length = $length * $ratio;
	} else {
		$length = $length / $ratio;
	}

	return intval($length);
}

// Verifica si es una peticion post
if (is_post()) {

	// Verifica la existencia de los datos enviados
	if (isset($_POST['pie_pagina']) && isset($_FILES['imagen_encabezado'])) {

		// Obtiene los datos de la institucion
		$id_institucion = trim($_institution['id_institucion']);
		$pie_pagina = trim($_POST['pie_pagina']);
		$imagen = $_FILES['imagen_encabezado'];

		// Obtiene el nombre de la imagen de encabezado
		$ilustracion = $db->from('sys_instituciones')->where('id_institucion', $id_institucion)->fetch_first();
		$ilustracion = $ilustracion['imagen_encabezado'];

		// Instancia la institucion
		$institucion = array(
			'pie_pagina' => $pie_pagina
		);

		// Actualiza la informacion
		$db->where('id_institucion', $id_institucion)->update('sys_instituciones', $institucion);
		
		$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'u',
				'nivel' => 'l',
				'direccion' => '?/configuraciones/reportes_guardar',
				'detalle' => 'Se actualizo insitucion con identificador numero ' . $id_institucion ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);			
		$db->insert('sys_procesos', $data) ; 

		// Define el mensaje de error
		$_SESSION[temporary] = array(
			'alert' => 'success',
			'title' => 'Actualizaci¨®n satisfactoria!',
			'message' => 'El registro se actualizÃ³ correctamente.'
		);

		// Obtiene los valores del archivo
		$imagen_nombre = $imagen['tmp_name'];

		// Verifica si existe el archivo
		if ($imagen_nombre != '') {
			// Verifica si esta almacenada la imagen en la base de datos
			if ($ilustracion != '') {
				// Verifica si la imagen esta almacenada en la carpeta de clientes
				if (file_exists(institucion . '/' . $ilustracion)) {
					// Elimina el archivo
					unlink(institucion . '/' . $ilustracion);
				}

				// Verifica si la imagen esta almacenada en la carpeta de clientes
				if (file_exists(institucion . '/' . $ilustracion)) {
					// Elimina el archivo
					unlink(institucion . '/' . $ilustracion);
				}
			}

			// Obtiene las rutas de las nuevas imagens
			$imagen_institucion = md5(prefix . random_string() . $id_institucion) . '.jpg';
			$imagen_grande_ruta = institucion . '/' . $imagen_institucion;
			$imagen_pequena_ruta = institucion . '/' . $imagen_institucion;

			// Obtiene las dimensiones de la imagen
			list($imagen_width, $imagen_height) = getimagesize($imagen_nombre);

			// Obiene las dimensiones de la imagen grande
			$imagen_grande_width = 650;
			$imagen_grande_height = resize($imagen_width, $imagen_height, $imagen_grande_width, false);

			// Obiene las dimensiones de la imagen pequeÃ±a
			$imagen_pequena_height = 100;
			$imagen_pequena_width = resize($imagen_width, $imagen_height, $imagen_pequena_height, true);

			// Obtiene el contenido de la imagen
			$imagen = file_get_contents($imagen_nombre);

			// Crea la imagen
			$imagen = imagecreatefromstring($imagen);

			// Crea la imagen grande
			$imagen_grande = imagecreatetruecolor($imagen_grande_width, $imagen_grande_height);
			$fondo = imagecolorallocate($imagen_grande, 255, 255, 255);
			imagefill($imagen_grande, 0, 0, $fondo);
			imagecopyresized($imagen_grande, $imagen, 0, 0, 0, 0, $imagen_grande_width, $imagen_grande_height, $imagen_width, $imagen_height);

			// Crea la imagen en miniatura
			$imagen_pequena = imagecreatetruecolor($imagen_pequena_width, $imagen_pequena_height);
			$fondo = imagecolorallocate($imagen_pequena, 255, 255, 255);
			imagefill($imagen_pequena, 0, 0, $fondo);
			imagecopyresized($imagen_pequena, $imagen, 0, 0, 0, 0, $imagen_pequena_width, $imagen_pequena_height, $imagen_width, $imagen_height);

			// Verifica si se creo la imagen grande
			if (imagejpeg($imagen_grande, $imagen_grande_ruta, 100) && imagejpeg($imagen_pequena, $imagen_pequena_ruta, 100)) {
				// Destruimos las imagens temporales
				imagedestroy($imagen_grande);
				imagedestroy($imagen_pequena);

				// Actualiza la informacion
				$db->where(array('id_institucion' => $id_institucion))->update('sys_instituciones', array('imagen_encabezado' => $imagen_institucion));

				$data = array(
    				'fecha_proceso' => date("Y-m-d"),
    				'hora_proceso' => date("H:i:s"), 
    				'proceso' => 'u',
    				'nivel' => 'l',
    				'direccion' => '?/configuraciones/reportes_guardar',
    				'detalle' => 'Se actualizo insitucion con identificador numero ' . $id_institucion ,
    				'usuario_id' => $_SESSION[user]['id_user']			
    			);			
		        $db->insert('sys_procesos', $data) ; 

				// Define el mensaje de exito
				$_SESSION[temporary] = array(
					'alert' => 'success',
					'title' => 'Subida satisfactoria!',
					'message' => 'La imagen fue subida correctamente.'
				);
			} else {
				// Define el mensaje de error
				$_SESSION[temporary] = array(
					'alert' => 'danger',
					'title' => 'Advertencia!',
					'message' => 'Se produjo un error al subir la imagen.'
				);
			}
		}

		// Redirecciona a la pagina principal
		redirect('?/configuraciones/reportes');
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