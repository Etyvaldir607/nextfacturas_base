<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene el id_proforma
$id_proforma = (isset($params[0])) ? $params[0] : 0;

// Obtiene el proforma
$venta = $db->from('inv_egresos')
			   ->where('id_egreso', $id_proforma)
			   ->fetch_first();

// Verifica si el proforma existe
if ($venta) {
	// Elimina el proforma
	$db->delete()->from('inv_egresos')->where('id_egreso', $id_proforma)->limit(1)->execute();
	
	// Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'u',
		'nivel' => 'l',
		'direccion' => '?/operaciones/manuales_eliminar',
		'detalle' => 'Se elimino inventario egreso con identificador numero' . $id_proforma ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ;

	// Elimina los detalles
	$db->delete()->from('inv_egresos_detalles')->where('egreso_id', $id_proforma)->execute();
	
	// Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'u',
		'nivel' => 'l',
		'direccion' => '?/operaciones/manuales_eliminar',
		'detalle' => 'Se elimino inventario egreso detalle con identificador numero' . $id_proforma ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ;

	// Verifica si fue el proforma eliminado
	if ($db->affected_rows) {
		// Instancia variable de notificacion
		$_SESSION[temporary] = array(
			'alert' => 'success',
			'title' => 'Eliminación satisfactoria!',
			'message' => 'La venta manual y todo su detalle fueron eliminados correctamente.'
		);
	}

	// Redirecciona a la pagina principal
	redirect('?/operaciones/listar_manuales');
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>