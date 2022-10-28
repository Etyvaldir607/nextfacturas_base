<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Verifica si es una peticion post
if (is_post()) {
	// Verifica la existencia de los datos enviados
	if (isset($_POST['id_movimiento']) && isset($_POST['fecha_movimiento']) && isset($_POST['hora_movimiento']) && isset($_POST['nro_comprobante']) && isset($_POST['concepto']) && isset($_POST['monto']) && isset($_POST['observacion'])) {
		// Obtiene las datos del gasto
		$id_movimiento = trim($_POST['id_movimiento']);
		$fecha_movimiento = trim($_POST['fecha_movimiento']);
		$hora_movimiento = trim($_POST['hora_movimiento']);
		$nro_comprobante = trim($_POST['nro_comprobante']);
		$concepto = trim($_POST['concepto']);
		$monto = trim($_POST['monto']);
		$observacion = trim($_POST['observacion']);
		
		// Instancia el gasto
		$gasto = array(
			'fecha_movimiento' => date_encode($fecha_movimiento),
			'hora_movimiento' => $hora_movimiento,
			'nro_comprobante' => $nro_comprobante,
			'tipo' => 'g',
			'concepto' => $concepto,
			'monto' => $monto,
			'observacion' => $observacion
		);
		
		// Verifica si es creacion o modificacion
		if ($id_movimiento > 0) {
			// Genera la condicion
			$condicion = array('id_movimiento' => $id_movimiento);
			
			// Actualiza la informacion
			$db->where($condicion)->update('caj_movimientos', $gasto);
			
			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'u',
				'nivel' => 'l',
				'direccion' => '?/movimientos/gastos_guardar',
				'detalle' => 'Se actualizo movimiento con identificador numero ' .$id_movimiento ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);			
			$db->insert('sys_procesos', $data) ; 
			
			
			// Instancia la variable de notificacion
			$_SESSION[temporary] = array(
				'alert' => 'success',
				'title' => 'Actualizaci車n satisfactoria!',
				'message' => 'El registro se actualiz車 correctamente.'
			);
		} else {
			// Inserta campo
			$gasto['empleado_id'] = $_user['persona_id'];

			// Guarda la informacion
			$id = $db->insert('caj_movimientos', $gasto);
			
			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/movimientos/gastos_guardar',
				'detalle' => 'Se creo movimiento con identificador numero ' . $id ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);
			
			$db->insert('sys_procesos', $data) ;
			
			// Instancia la variable de notificacion
			$_SESSION[temporary] = array(
				'alert' => 'success',
				'title' => 'Adici車n satisfactoria!',
				'message' => 'El registro se guard車 correctamente.'
			);
		}
		
		// Redirecciona a la pagina principal
		redirect('?/movimientos/gastos_listar');
	} else {
		// Error 401
		require_once bad_request();
		exit;
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>