<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene el id_ingreso
$id_ingreso = (sizeof($params) > 0) ? $params[0] : 0; ///////////////AQUÍ LLEGA EGRESO NO INGRESO // REVISAR

// Obtiene el ingreso
$ingreso = $db->from('inv_ingresos')->where('id_ingreso', $id_ingreso)->fetch_first();

// Verifica si el ingreso existe
if ($ingreso) {


	// Instancia el user
	$user = array(
		'transitorio' => 0,
        'des_transitorio' => 'se cambio el estado'
	);

	// Genera la condicion
	$condicion = array('id_ingreso' => $id_ingreso);

	// Actualiza la informacion
	$db->where($condicion)->update('inv_ingresos', $user);
	// Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'u',
		'nivel' => 'l',
		'direccion' => '?/notas/activar',
		'detalle' => 'Se actualizo notas con identificador número ' . $id_ingreso ,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ;

	// Redirecciona a la pagina principal
	redirect('?/ingresos/listar');
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>