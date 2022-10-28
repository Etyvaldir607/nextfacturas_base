<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene el id_movimiento
$id_movimiento = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el ingreso
$ingreso = $db->from('caj_movimientos')->where('id_movimiento', $id_movimiento)->fetch_first();

// Verifica si el ingreso existe
if ($ingreso) {
    
	// Elimina el ingreso
	$db->delete()->from('caj_movimientos')->where('id_movimiento', $id_movimiento)->limit(1)->execute();

	//Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'd',
		'nivel' => 'l',
		'direccion' => '?/movimientos/ingresos_eliminar',
		'detalle' => 'Se elimino movimiento con identificador numero ' . $id_movimiento ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ;
	
	
	// Verifica si fue el ingreso eliminado
	if ($db->affected_rows) {
		// Instancia variable de notificacion
		$_SESSION[temporary] = array(
			'alert' => 'success',
			'title' => 'Eliminación satisfactoria!',
			'message' => 'El registro fue eliminado correctamente.'
		);
	}

	// Redirecciona a la pagina principal
	redirect('?/movimientos/ingresos_listar');
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>