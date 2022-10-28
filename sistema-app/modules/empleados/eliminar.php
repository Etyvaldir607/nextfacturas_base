<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene el id_empleado
$id_empleado = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el empleado
$empleado = $db->from('sys_empleados')->where('id_empleado', $id_empleado)->fetch_first();

// Verifica si el empleado existe
if ($empleado) {
	// Elimina el empleado
	$db->delete()->from('sys_empleados')->where('id_empleado', $id_empleado)->limit(1)->execute();
	
	//Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'd',
		'nivel' => 'l',
		'direccion' => '?/empleados/eliminar',
		'detalle' => 'Se elimino empleado con identificador numero ' . $id_empleado ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ;

	// Verifica si fue el empleado eliminado
	if ($db->affected_rows) {
		// Instancia variable de notificacion
		$_SESSION[temporary] = array(
			'alert' => 'success',
			'title' => 'Eliminación satisfactoria!',
			'message' => 'El registro fue eliminado correctamente.'
		);
	}

	// Redirecciona a la pagina principal
	redirect('?/empleados/listar');
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>