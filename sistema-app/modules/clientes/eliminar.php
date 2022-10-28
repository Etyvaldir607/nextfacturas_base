<?php

// Obtiene el id_empleado
$id_cliente = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el empleado
$cliente = $db->from('inv_clientes')->where('id_cliente', $id_cliente)->fetch_first();

// Verifica si el empleado existe
if ($cliente) {
	// Elimina el empleado
	$cliente = array(
        'estado' => "no"
    );
    $db->where('id_cliente',$id_cliente)->update('inv_clientes', $cliente);
	
	//Guarda en el historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'd',
		'nivel' => 'l',
		'direccion' => '?/clientes/eliminar',
		'detalle' => 'Se elimino cliente con identificador numero ' . $id_cliente ,
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
	redirect('?/clientes/listar');
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>