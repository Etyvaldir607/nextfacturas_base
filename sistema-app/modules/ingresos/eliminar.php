<?php


// Obtiene el id_ingreso
$id_ingreso = (isset($params[0])) ? $params[0] : 0;

// Obtiene el ingreso
$ingreso = $db->select('i.*, a.principal')
			  ->from('inv_ingresos i')
			  ->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')
			  ->where('i.id_ingreso', $id_ingreso)
			  ->fetch_first();

//Obtiene detalle de la compra
$detalles = $db->query("SELECT * FROM inv_ingresos_detalles WHERE ingreso_id = " . $ingreso['id_ingreso'])->fetch();

//var_dump($detalles);
//echo "<br>";
//obtiene los ids del array
$keys = array_keys($detalles);

foreach ($detalles as $key => $value) {	
	$consulta .= "(SELECT SUM(d.cantidad) as sum_lotes, d.* FROM `inv_egresos_detalles` d  WHERE d.lote='". $value['lote'] ."' AND d.vencimiento='" . $value['vencimiento'] . "' GROUP BY d.producto_id) ";
	if ($key < end($keys)) {
		$consulta .= " UNION ";
	}
}

//se realiza la consulta mysql
$verifica = $db->query($consulta)->fetch(); 
//echo "<br>";
//var_dump($consulta);
//var_dump($verifica);
//exit();

//verifica si la variable esta vacia
if (!$verifica) {
	// Verifica si el ingreso existe
	if ($ingreso) {
		// Elimina el ingreso
	    $db->delete()->from('inv_ingresos')->where('id_ingreso', $id_ingreso)->limit(1)->execute();
		
		// Guarda Historial
		$data = array(
			'fecha_proceso' => date("Y-m-d"),
			'hora_proceso' => date("H:i:s"), 
			'proceso' => 'u',
			'nivel' => 'l',
			'direccion' => '?/ingresos/eliminar',
			'detalle' => 'Se elimino ingreso con identificador numero' . $id_ingreso ,
			'usuario_id' => $_SESSION[user]['id_user']			
		);			
		$db->insert('sys_procesos', $data) ;
		
		// Elimina los detalles
		$db->delete()->from('inv_ingresos_detalles')->where('ingreso_id', $id_ingreso)->execute();
		// Guarda Historial
		$data = array(
			'fecha_proceso' => date("Y-m-d"),
			'hora_proceso' => date("H:i:s"), 
			'proceso' => 'u',
			'nivel' => 'l',
			'direccion' => '?/ingresos/eliminar',
			'detalle' => 'Se elimino ingreso detalle con identificador numero' . $id_ingreso ,
			'usuario_id' => $_SESSION[user]['id_user']			
		);			
		$db->insert('sys_procesos', $data) ;

		// Verifica si fue el ingreso eliminado
		if ($db->affected_rows) {
			// Instancia variable de notificacion
			$_SESSION[temporary] = array(
				'alert' => 'success',
				'title' => 'Eliminacion satisfactoria!',
				'message' => 'El ingreso y todo su detalle fue eliminado correctamente.'
			);
		}

		// Redirecciona a la pagina principal
		redirect('?/ingresos/listar');
	} else {
		// Error 404
		require_once not_found();
		exit;
	}
}else{
	// Verifica si fue el ingreso eliminado
		if ($db->affected_rows) {
			// Instancia variable de notificacion
			$_SESSION[temporary] = array(
				'alert' => 'danger',
				'title' => 'Eliminacion no realizada!',
				'message' => 'El ingreso contiene items que ya fueron utilizados en movimientos de la empresa. La eliminación no puede ser realizada. '
			);
		}

		// Redirecciona a la pagina principal
		redirect('?/ingresos/listar');
}

?>