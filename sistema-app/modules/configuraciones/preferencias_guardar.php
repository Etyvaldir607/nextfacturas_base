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
	if (isset($_POST['formato'])) {
		// Obtiene los datos de la institucion
		$id_institucion = trim($_institution['id_institucion']);
		$formato = trim($_POST['formato']);

		// Instancia la institucion
		$institucion = array(
			'formato' => $formato
		);

		// Actualiza la informacion
		$db->where('id_institucion', $id_institucion)->update('sys_instituciones', $institucion);
		
		$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'u',
				'nivel' => 'l',
				'direccion' => '?/configuraciones/preferencias_guardar',
				'detalle' => 'Se actualizo institucion con identificador numero ' . $id_institucion ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);			
		$db->insert('sys_procesos', $data) ; 

		// Define el mensaje de exito
		$_SESSION[temporary] = array(
			'alert' => 'success',
			'title' => 'Actualización satisfactoria!',
			'message' => 'El registro se actualizó correctamente.'
		);

		// Redirecciona a la pagina principal
		redirect('?/configuraciones/preferencias');
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