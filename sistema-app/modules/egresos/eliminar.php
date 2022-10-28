<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene el id_egreso
$id_egreso = (isset($params[0])) ? $params[0] : 0;

// Obtiene el egreso
$egreso = $db->from('inv_egresos')
			 ->where('id_egreso', $id_egreso)
			 ->fetch_first();

// Verifica si el egreso existe
if ($egreso) {
	// Elimina el egreso
	$db->delete()->from('inv_egresos')->where('id_egreso', $id_egreso)->limit(1)->execute();
	//Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'd',
		'nivel' => 'l',
		'direccion' => '?/egresos/eliminar',
		'detalle' => 'Se elimino egreso con identificador numero ' . $id_egreso ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ;

	// Elimina los detalles
	$db->delete()->from('inv_egresos_detalles')->where('egreso_id', $id_egreso)->execute();
	
	//Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'd',
		'nivel' => 'l',
		'direccion' => '?/egresos/eliminar',
		'detalle' => 'Se elimino egreso detalle con identificador numero ' . $id_egreso ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ;
	

	// Verifica si fue el egreso eliminado
	if ($db->affected_rows) {
		// Instancia variable de notificacion
		$_SESSION[temporary] = array(
			'alert' => 'success',
			'title' => 'Eliminaci¨®n satisfactoria!',
			'message' => 'El egreso y todo su detalle fueron eliminados correctamente.'
		);
	}

	// Redirecciona a la pagina principal
	redirect('?/egresos/listar');
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>