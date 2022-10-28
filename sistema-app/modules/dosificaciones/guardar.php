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
	if (isset($_POST['id_dosificacion']) && isset($_POST['nro_tramite']) && isset($_POST['nro_autorizacion']) && isset($_POST['llave_dosificacion']) && isset($_POST['fecha_limite']) && isset($_POST['leyenda']) && isset($_POST['observacion'])) {
		// Obtiene las datos de la dosificación
		$id_dosificacion = trim($_POST['id_dosificacion']);
		$nro_tramite = trim($_POST['nro_tramite']);
		$nro_autorizacion = trim($_POST['nro_autorizacion']);
		$llave_dosificacion = trim($_POST['llave_dosificacion']);
		$fecha_limite = trim($_POST['fecha_limite']);
		$leyenda = trim($_POST['leyenda']);
		$observacion = trim($_POST['observacion']);
		
		// Instancia la dosificación
		$dosificacion = array(
			'nro_tramite' => $nro_tramite,
			'nro_autorizacion' => $nro_autorizacion,
			'llave_dosificacion' => base64_encode($llave_dosificacion),
			'fecha_limite' => date_encode($fecha_limite),
			'leyenda' => $leyenda,
			'observacion' => $observacion
		);
		
		// Verifica si es creacion o modificacion
		if ($id_dosificacion > 0) {
			// Genera la condicion
			$condicion = array('id_dosificacion' => $id_dosificacion);
			
			// Actualiza la informacion
			$db->where($condicion)->update('inv_dosificaciones', $dosificacion);
			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'u',
				'nivel' => 'l',
				'direccion' => '?dosificaciones/guardar',
				'detalle' => 'Se actualizo dosificacion con identificador numero ' . $id_dosificacion ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);			
			$db->insert('sys_procesos', $data) ; 
			
			
			// Instancia la variable de notificacion
			$_SESSION[temporary] = array(
				'alert' => 'success',
				'title' => 'Actualización satisfactoria!',
				'message' => 'El registro se actualizó correctamente.'
			);
		} else {
			// Adiciona informacion extra
			$dosificacion['fecha_registro'] = date('Y-m-d');
			$dosificacion['hora_registro'] = date('H:i:s');
			$dosificacion['activo'] = 'N';
			$dosificacion['nro_facturas'] = 0;

			// Guarda la informacion
			$id = $db->insert('inv_dosificaciones', $dosificacion);
			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/dosificaciones/guardar',
				'detalle' => 'Se creo dosificacion con identificador numero ' . $id ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);
			
			$db->insert('sys_procesos', $data) ;
			
			// Instancia la variable de notificacion
			$_SESSION[temporary] = array(
				'alert' => 'success',
				'title' => 'Adición satisfactoria!',
				'message' => 'El registro se guardó correctamente.'
			);
		}
		
		// Redirecciona a la pagina principal
		redirect('?/dosificaciones/listar');
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