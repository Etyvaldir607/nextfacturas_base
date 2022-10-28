<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene los parametros
$id_ingreso = (isset($params[0])) ? $params[0] : 0;
$id_detalle = (isset($params[1])) ? $params[1] : 0;

// Obtiene los ingreso
$ingreso = $db->select('i.almacen_id, i.monto_total, i.nro_registros, a.principal')
			  ->from('inv_ingresos i')
			  ->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')
			  ->where('id_ingreso', $id_ingreso)
			  ->fetch_first();

// Obtiene el ingreso
$detalle = $db->from('inv_ingresos_detalles')->where(array('id_detalle' => $id_detalle, 'ingreso_id' => $id_ingreso))->fetch_first();

// Verifica si el ingreso existe
if ($detalle) {
	// Instancia el ingreso
	$ingreso = array(
		'monto_total' => $ingreso['monto_total'] - ($detalle['cantidad'] * $detalle['costo']),
		'nro_registros' => $ingreso['nro_registros'] - 1
	);

	// Actualiza el ingreso
	$db->where('id_ingreso', $id_ingreso)->update('inv_ingresos', $ingreso);
	
	// Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'u',
		'nivel' => 'l',
		'direccion' => '?/ingresos/suprimir',
		'detalle' => 'Se actualizo ingreso con identificador n¨²mero ' . $id_ingreso ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);
	
	$db->insert('sys_procesos', $data) ; 
	
	// Elimina el detalle
	$db->delete()->from('inv_ingresos_detalles')->where('id_detalle', $id_detalle)->limit(1)->execute();
	
	// Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'u',
		'nivel' => 'l',
		'direccion' => '?/ingresos/suprimir',
		'detalle' => 'Se elimino ingreso detalle con identificador numero' . $id_detalle ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ;
	

	// Verifica si fue el ingreso eliminado
	if ($db->affected_rows) {
		// Instancia variable de notificacion
		$_SESSION[temporary] = array(
			'alert' => 'success',
			'title' => 'Eliminaci¨®n satisfactoria!',
			'message' => 'El detalle del ingreso fue eliminado correctamente.'
		);
	}

	// Redirecciona a la pagina principal
	redirect('?/ingresos/ver/' . $id_ingreso);
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>