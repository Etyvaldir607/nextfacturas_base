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
	if (isset($_POST['id_moneda']) && isset($_POST['moneda']) && isset($_POST['sigla']) && isset($_POST['oficial'])) {
		// Obtiene las datos de la moneda
		$id_moneda = trim($_POST['id_moneda']);
		$moneda = trim($_POST['moneda']);
		$sigla = trim($_POST['sigla']);
		$oficial = trim($_POST['oficial']);
		
		// Instancia la moneda
		$moneda = array(
			'moneda' => $moneda,
			'sigla' => $sigla,
			'oficial' => $oficial
		);
		
		// Verifica si sera moneda oficial
		if ($oficial == 'S') {
			// Elimina monedas oficiales
			$db->where('oficial', 'S')->update('inv_monedas', array('oficial' => 'N'));
		}

		// Verifica si es creacion o modificacion
		if ($id_moneda > 0) {
			// Genera la condicion
			$condicion = array('id_moneda' => $id_moneda);
			
			// Actualiza la informacion
			$db->where($condicion)->update('inv_monedas', $moneda);
			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'u',
				'nivel' => 'l',
				'direccion' => '?/monedas/guardar',
				'detalle' => 'Se actualizo la moneda con identificador numero ' . $id_moneda,
				'usuario_id' => $_SESSION[user]['id_user']			
			);			
			$db->insert('sys_procesos', $data) ;
			
			// Instancia la variable de notificacion
			$_SESSION[temporary] = array(
				'alert' => 'success',
				'title' => 'Actualizacion satisfactoria!',
				'message' => 'El registro se actualizo correctamente.'
			);
		} else {
			// Guarda la informacion
			$id = $db->insert('inv_monedas', $moneda);
			// Guarda en el historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/monedas/guardar',
				'detalle' => 'Se inserto la moneda con identificador numero ' . $id ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);			
			$db->insert('sys_procesos', $data) ; 
			
			// Instancia la variable de notificacion
			$_SESSION[temporary] = array(
				'alert' => 'success',
				'title' => 'Adicion satisfactoria!',
				'message' => 'El registro se guardo correctamente.'
			);
		}
		
		// Redirecciona a la pagina principal
		redirect('?/monedas/listar');
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