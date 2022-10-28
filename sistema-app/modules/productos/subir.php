<?php

/**
 * FunctionPHP - Framework Functional PHP
 * 
 * @package  FunctionPHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Verifica la peticion post
if (is_post()) {
	// Verifica la existencia de datos
	if (isset($_POST['id_producto']) && isset($_POST['data']) && isset($_FILES['imagen'])) {
		// Obtiene los datos
		$id_producto = trim($_POST['id_producto']);
		$data = get_object_vars(json_decode($_POST['data']));
		$imagen = $_FILES['imagen'];

		// Importa la libreria para subir la imagen
		require_once libraries . '/upload-class/class.upload.php';

		// Define la ruta
		$ruta = files . '/productos/';

		// Obtiene el nombre de la imagen
		$producto = $db->from('inv_productos')->where('id_producto', $id_producto)->fetch_first();

		// Obtiene el nombre de la imagen inicial
		$imagen_inicial = $producto['imagen'];

		// Verifica si la imagen existe
		if ($imagen_inicial != '') {
			// Elimina la imagen
			file_delete($ruta . $imagen_inicial);
		}

		// Obtiene las dimensiones de la imagen
		list($ancho, $alto) = getimagesize($imagen['tmp_name']);

		// Redimensiona la imagen segun la escala
		$ancho = $ancho * $data['scale'];
		$alto = $alto * $data['scale'];

		// Define la extension de la imagen
		$extension = 'jpg';

		// Define el nombre de la imagen final
		$imagen_final = md5(secret . random_string() . $id_producto);

		// Instancia la imagen
		$imagen = new upload($imagen);

		// Verifica si la imagen puede ser subida
		if ($imagen->uploaded) {
			// Define los parametros de salida
			$imagen->file_new_name_body = $imagen_final;
			$imagen->image_resize = true;
			$imagen->image_ratio_crop = true;
			$imagen->image_x = $ancho;
			$imagen->image_y = $alto;
			$imagen->image_rotate = $data['angle'];
			$imagen->image_convert = $extension;
			$imagen->jpeg_quality = 95;
			$imagen->image_background_color = '#fff';
					
			// Recorta la imagen de acuerdo a la rotacion
			switch ($data['angle']) {
				case 90:
					$imagen->image_crop = ($alto - $data['x'] - $data['w']) . ' ' . ($ancho - $data['y'] - $data['h']) . ' ' . $data['x'] . ' ' . $data['y'];
					break;
				case 180:
					$imagen->image_crop =  $data['y'] . ' ' . $data['x'] . ' ' . ($alto - $data['y'] - $data['h']) . ' ' . ($ancho - $data['x'] - $data['w']);
					break;
				case 270:
					$imagen->image_crop = $data['x'] . ' ' . $data['y'] . ' ' . ($alto - $data['x'] - $data['w']) . ' ' . ($ancho - $data['y'] - $data['h']);
					break;
				default:
					$imagen->image_crop =  $data['y'] . ' ' . ($ancho - $data['x'] - $data['w']) . ' ' . ($alto - $data['y'] - $data['h']) . ' ' . $data['x'];
					break;
			}

			// Procesa la imagen
			@$imagen->process($ruta);

			// Verifica si el proceso fue exitoso
			if ($imagen->processed) {
				// Limpia la imagen temporal
				$imagen->clean();

				// Modifica el producto
				$db->where('id_producto', $id_producto)->update('inv_productos', array('imagen' => $imagen_final . '.' . $extension));

				// Define el mensaje de exito
				$_SESSION[temporary] = array(
					'alert' => 'success',
					'title' => 'Subida satisfactoria!',
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
		}

		// Redirecciona la pagina
		redirect('?/productos/ver/' . $id_producto);
	} else {
		// Error 400
		require_once bad_request();
		exit;
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>