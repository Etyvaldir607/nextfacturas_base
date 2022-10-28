<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene el id_dosificacion
$id_dosificacion = (isset($params[0])) ? $params[0] : 0;

// Obtiene el dosificacion
$dosificacion = $db->from('inv_dosificaciones')->where('id_dosificacion', $id_dosificacion)->fetch_first();

// Verifica si el dosificacion existe
if ($dosificacion) {
	// Obtiene el nuevo estado
	$estado = ($dosificacion['activo'] == 'N') ? 'S' : 'N';

	// Instancia el dosificacion
	$dosificacion = array(
		'activo' => $estado
	);

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
		'direccion' => '?/dosificaciones/bloquear',
		'detalle' => 'Se actualizo dosficaciones con identificador número ' . $id_dosificacion ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ;
	
	// Redirecciona a la pagina principal
	redirect('?/dosificaciones/listar');
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>