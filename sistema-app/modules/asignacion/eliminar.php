<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene los parametros
$id_ruta = (isset($params[0])) ? $params[0] : 0;

// Obtiene el precio
$ruta = $db->from('gps_rutas')->where(array('id_ruta' => $id_ruta))->fetch_first();

// Verifica si el precio existe
if ($ruta) {
	// Elimina el precio
	$db->delete()->from('gps_rutas')->where('id_ruta', $id_ruta)->limit(1)->execute();
	//Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'd',
		'nivel' => 'l',
		'direccion' => '?/control/eliminar',
		'detalle' => 'Se elimino ruta con identificador número ' . $id_ruta ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ;

	// Verifica si fue el precio eliminado
	if ($db->affected_rows) {
		// Instancia variable de notificacion
		$_SESSION[temporary] = array(
			'alert' => 'success',
			'title' => 'Eliminación satisfactoria!',
			'message' => 'El registro fue eliminado correctamente.'
		);
	}

	// Redirecciona a la pagina principal
	redirect('?/control/listar');
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>