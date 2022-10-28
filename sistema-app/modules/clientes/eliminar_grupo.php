<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene el id_empleado
$id_grupo = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el empleado
$grupo = $db->from('inv_clientes_grupos')->where('id_cliente_grupo', $id_grupo)->fetch_first();

// Verifica si el empleado existe
if ($grupo) {
	// Elimina el empleado
	$db->delete()->from('inv_clientes_grupos')->where('id_cliente_grupo', $id_grupo)->limit(1)->execute();
	
	//Guarda en el historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'd',
		'nivel' => 'l',
		'direccion' => '?/clientes/eliminar_grupo',
		'detalle' => 'Se elimino grupo cliente con identificador numero ' . $id_grupo ,
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
	redirect('?/clientes/crear_grupo');
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>