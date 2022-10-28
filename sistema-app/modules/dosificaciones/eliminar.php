<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene el id_dosificacion
$id_dosificacion = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene la dosificación
$dosificacion = $db->from('inv_dosificaciones')->where('id_dosificacion', $id_dosificacion)->fetch_first();

// Verifica si la dosificación existe
if ($dosificacion) {
	// Elimina la dosificación
	$db->delete()->from('inv_dosificaciones')->where('id_dosificacion', $id_dosificacion)->limit(1)->execute();
	//Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'd',
		'nivel' => 'l',
		'direccion' => '?/dosificaciones/eliminar',
		'detalle' => 'Se elimino dosificacion con identificador número ' . $id_dosificacion ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ;
	

	// Verifica si fue la dosificación eliminado
	if ($db->affected_rows) {
		// Instancia variable de notificacion
		$_SESSION[temporary] = array(
			'alert' => 'success',
			'title' => 'Eliminación satisfactoria!',
			'message' => 'El registro fue eliminado correctamente.'
		);
	}

	// Redirecciona a la pagina principal
	redirect('?/dosificaciones/listar');
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>