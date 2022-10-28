<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene los parametros
$id_egreso = (isset($params[0])) ? $params[0] : 0;
$id_detalle = (isset($params[1])) ? $params[1] : 0;

// Obtiene los egreso
$egreso = $db->select('i.almacen_id, i.monto_total, i.nro_registros, a.principal')
			  ->from('inv_egresos i')
			  ->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')
			  ->where('id_egreso', $id_egreso)
			  ->fetch_first();

// Obtiene el egreso
$detalle = $db->from('inv_egresos_detalles')->where(array('id_detalle' => $id_detalle, 'egreso_id' => $id_egreso))->fetch_first();

// Verifica si el egreso existe
if ($detalle) {
	// Instancia el egreso
	$egreso = array(
		'monto_total' => $egreso['monto_total'] - ($detalle['cantidad'] * $detalle['precio']),
		'nro_registros' => $egreso['nro_registros'] - 1
	);

	// Actualiza el egreso
	$db->where('id_egreso', $id_egreso)->update('inv_egresos', $egreso);
	
	// Guarda en el historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'c',
		'nivel' => 'l',
		'direccion' => '?/egresos/suprimir',
		'detalle' => 'Se inserto egreso con identificador numero ' . $id_egreso ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ; 

	// Elimina el detalle
	$id_eg = $db->delete()->from('inv_egresos_detalles')->where('id_detalle', $id_detalle)->limit(1)->execute();
	
	//Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'd',
		'nivel' => 'l',
		'direccion' => '?/egreso/suprimir',
		'detalle' => 'Se elimino inventario egreso detalle con identificador numero ' . $id_eg ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ;
	

	// Verifica si fue el egreso eliminado
	if ($db->affected_rows) {
		// Instancia variable de notificacion
		$_SESSION[temporary] = array(
			'alert' => 'success',
			'title' => 'Eliminación satisfactoria!',
			'message' => 'El detalle del egreso fue eliminado correctamente.'
		);
	}

	// Redirecciona a la pagina principal
	redirect('?/egresos/ver/' . $id_egreso);
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>