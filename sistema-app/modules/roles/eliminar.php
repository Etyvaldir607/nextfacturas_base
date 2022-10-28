<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene el id_rol
$id_rol = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el rol
$rol = $db->from('sys_roles')->where('id_rol', $id_rol)->fetch_first();

// Verifica si el rol existe
if ($rol) {
	// Elimina el rol
	$db->delete()->from('sys_roles')->where('id_rol', $id_rol)->limit(1)->execute();
	// Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'u',
		'nivel' => 'l',
		'direccion' => '?/roles/eliminar',
		'detalle' => 'Se elimino rol con identificador numero' . $id_rol ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ;

	// Verifica si fue el rol eliminado
	if ($db->affected_rows) {
		// Instancia variable de notificacion
		$_SESSION[temporary] = array(
			'alert' => 'success',
			'title' => 'Eliminacion satisfactoria!',
			'message' => 'El registro fue eliminado correctamente.'
		);
	}

	// Redirecciona a la pagina principal
	redirect('?/roles/listar');
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>