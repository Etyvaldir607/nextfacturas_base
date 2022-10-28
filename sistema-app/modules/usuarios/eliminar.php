<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene el id_user
$id_user = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el user
$usuario = $db->from('sys_users')->where('id_user', $id_user)->fetch_first();

// Verifica si el user existe
if ($usuario && $usuario['id_user'] != 1) {
	// Elimina el user
	$db->delete()->from('sys_users')->where('id_user', $id_user)->limit(1)->execute();
	$db->delete()->from('inv_users_almacenes')->where('user_id', $id_user)->execute();
	// Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'u',
		'nivel' => 'l',
		'direccion' => '?/usuarios/eliminar',
		'detalle' => 'Se elimino usuario con identificador numero ' . $id_user ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ;

	// Verifica si fue el user eliminado
	if ($db->affected_rows) {
		// Define la variable de error
		$_SESSION[temporary] = array(
			'alert' => 'success',
			'title' => 'Eliminacion satisfactoria!',
			'message' => 'El usuario fue eliminado correctamente.'
		);
	}

	// Redirecciona a la pagina principal
	redirect('?/usuarios/listar');
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>