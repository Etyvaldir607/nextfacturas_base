<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com> 
 */
//var_dump('cvhvgh');exit();
// Verifica si es una peticion post
if (is_post()) { 
//var_dump($_POST);exit();
	// Verifica la existencia de los datos enviados
	if (isset($_POST['id_ingreso']) && isset($_POST['cambio']) && isset($_POST['monto_total']) && isset($_POST['costos'])) {
        //   var_dump($_POST);exit();

		// Obtiene los datos del producto
		$id_ingreso = $_POST['id_ingreso'];
		$cambio = trim($_POST['cambio']);
		$monto_total = trim($_POST['monto_total']);
		$nro_registros = trim($_POST['nro_registros']);

		//Datos del detalle de ingreso/compra
		$cantidades = (isset($_POST['cantidades'])) ? $_POST['cantidades']: array();
		$productos = (isset($_POST['productos'])) ? $_POST['productos']: array();
		$costos = (isset($_POST['costos'])) ? $_POST['costos']: array();
		$id_detalle = (isset($_POST['id_detalle'])) ? $_POST['id_detalle']: array();
		$bolivianos = (isset($_POST['bolivianos'])) ? $_POST['bolivianos']: array();
		$estimados = (isset($_POST['estimados'])) ? $_POST['estimados']: array();

		// Instancia el ingreso
		$ingreso = array(
			'monto_total' => $monto_total * $cambio,
			'tipo_cambio' => $cambio,
			'nro_registros' => $nro_registros
		);

		// Guarda la informacion
		$db->where('id_ingreso', $id_ingreso)->update('inv_ingresos', $ingreso);
		
		// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'u',
				'nivel' => 'l',
				'direccion' => '?/ingresos/guardar-cambio',
				'detalle' => 'Se actualizo ingreso con identificador numero ' . $id_ingreso ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);			
			$db->insert('sys_procesos', $data) ; 
		

		foreach ($productos as $nro => $elemento) {
			//var_dump($productos);exit();
			// Forma el detalle
			$detalle = array(
				'cantidad' => (isset($cantidades[$nro])) ? $cantidades[$nro]: 0,
				'costo' => (isset($bolivianos[$nro])) ? $bolivianos[$nro]: 0,
			);

			// Guarda la informacion
			$db->where('id_detalle', $id_detalle[$nro])->update('inv_ingresos_detalles', $detalle);
			
			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'u',
				'nivel' => 'l',
				'direccion' => '?/ingresos/guardar-cambio',
				'detalle' => 'Se actualizo inventario ingreso detalle con identificador numero ' .$id_detalle[$nro] ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);			
			$db->insert('sys_procesos', $data) ; 
			
			
			
            //var_dump($id_detalle[$nro]);exit();
			// Define el valor
			$valor = (isset($estimados[$nro])) ? $estimados[$nro]: 0;

			// Instancia el producto
			$precio = array(
				'precio' => $valor,
				'fecha_registro' => date('Y-m-d'),
				'hora_registro' => date('H:i:s'),
				'producto_id' => $productos[$nro],
				'empleado_id' => $_user['persona_id']
			);

			// Guarda la informacion
			$id = $db->insert('inv_precios', $precio);
			
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/ingresos/guardar-cambio',
				'detalle' => 'Se creó inventario precio con identificador numero ' . $id ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);
			
			$db->insert('sys_procesos', $data) ; 
			
			

			// Actualiza la informacion
			$db->where('id_producto', $productos[$nro])->update('inv_productos', array('precio_actual' => $valor));
			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'u',
				'nivel' => 'l',
				'direccion' => '?/ingresos/guardar-cambio',
				'detalle' => 'Se actualizo inventario producto con identificador numero ' . $productos[$nro] ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);
			
			$db->insert('sys_procesos', $data) ; 

			
		}
		
		// Redirecciona a la pagina principal
		redirect('?/ingresos/ver/'.$id_ingreso);
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