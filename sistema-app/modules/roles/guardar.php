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
	if (isset($_POST['id_rol']) && isset($_POST['rol']) && isset($_POST['descripcion'])) {
		// Obtiene los datos del rol
		$id_rol = trim($_POST['id_rol']);
		$rol = trim($_POST['rol']);
		$descripcion = trim($_POST['descripcion']);
		
		// Instancia el rol
		$rol = array(
			'rol' => $rol,
			'descripcion' => $descripcion
		);
		
		// Verifica si es creacion o modificacion
		if ($id_rol > 0) {
			// Genera la condicion
			$condicion = array('id_rol' => $id_rol);
			
			// Actualiza la informacion
			$db->where($condicion)->update('sys_roles', $rol);
			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'u',
				'nivel' => 'l',
				'direccion' => '?/roles/guardar',
				'detalle' => 'Se actualizo rol con identificador numero ' . $id_rol ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);			
			$db->insert('sys_procesos', $data) ;

			// Instancia la variable de notificacion
			$_SESSION[temporary] = array(
				'alert' => 'success',
				'title' => 'Actualizacion satisfactoria!',
				'message' => 'El registro se actualizo correctamente.'
			);
		} else {
			// Guarda la informacion
			$id = $db->insert('sys_roles', $rol);
			
			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/roles/guardar',
				'detalle' => 'Se creo rol con identificador numero ' . $id ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);
			
			$db->insert('sys_procesos', $data) ; 
			
			// Instancia la variable de notificacion
			$_SESSION[temporary] = array(
				'alert' => 'success',
				'title' => 'Adicion satisfactoria!',
				'message' => 'El registro se guardo correctamente.'
			);
		}
		
		// Redirecciona a la pagina principal
		redirect('?/roles/listar');
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