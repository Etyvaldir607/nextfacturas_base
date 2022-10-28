<?php

/**
 * FunctionPHP - Framework Functional PHP
 * 
 * @package  FunctionPHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Verifica la peticion post
if (is_post()) {
	// Verifica la cadena csrf
	if (isset($_POST[get_csrf()])) {
		// Obtiene los parametros
		$id_asignacion = (isset($params[0])) ? $params[0] : 0;

		// Obtiene la asignacion
		$asignacion = $db->from('inv_asignaciones')->where('id_asignacion', $id_asignacion)->fetch_first();

		// Verifica si existen los productos
		if ($asignacion) {
			// Verifica la existencia de datos
			if (isset($_POST['fecha_precio']) && isset($_POST['precio']) && isset($_POST['observacion'])) {
				// Obtiene los datos
				$fecha_precio = clear($_POST['fecha_precio']);
				$precio = clear($_POST['precio']);
				$observacion = clear($_POST['observacion']);

				// Reformula los datos
				$precio = (is_numeric($precio)) ? $precio : 0;

				// Instancia el precio
				$precio = array(
					'fecha_precio' => date_encode($fecha_precio) . ' ' . date('H:i:s'),
					'precio' => $precio,
					'observacion' => $observacion,
					'asignacion_id' => $id_asignacion,
					'usuario_id' => $_user['id_user']
				);

				// Crea el precio
				$id_precio = $db->insert('inv_precios', $precio);

				// Guarda el proceso
				/*$db->insert('sys_procesos', array(
					'fecha_proceso' => date('Y-m-d H:i:s'),
					'proceso' => 'c',
					'nivel' => 'm',
					'detalle' => 'Se fijó el precio con identificador número ' . $id_precio . ' al producto con identificador número ' . $asignacion['producto_id'] . '.',
					'direccion' => $_location,
					'usuario_id' => $_user['id_user']
				));*/
				
				// Crea la notificacion
				set_notification('success', 'Modificación exitosa!', 'El precio se fijó satisfactoriamente.');

				// Redirecciona la pagina
				redirect(back());
			} else {
				// Error 400
				require_once bad_request();
				exit;
			}
		} else {
			// Error 400
			require_once bad_request();
			exit;
		}
	} else {
		// Redirecciona la pagina
		redirect(back());
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>