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
$user = $db->from('sys_users')->where('id_user', $id_user)->fetch_first();

// Verifica si el user existe
if ($user && $user['id_user'] != 1) {
	// Obtiene el nuevo estado
	$estado = ($user['active'] == 0) ? 1 : 0;

	// Instancia el user
	$user = array(
		'active' => $estado
	);

	// Genera la condicion
	$condicion = array('id_user' => $id_user);

	// Actualiza la informacion
	$db->where($condicion)->update('sys_users', $user);
	
	// Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'u',
		'nivel' => 'l',
		'direccion' => '?/usuarios/activar',
		'detalle' => 'Se actualizo usuario con identificador número ' . $id_user ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ;

	// Redirecciona a la pagina principal
	redirect('?/usuarios/listar');
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>