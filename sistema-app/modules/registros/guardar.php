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
	if (isset($_POST['busqueda']) && isset($_POST['precio']) && isset($_POST['costo']) && isset($_POST['cantidad'])) {
		// Obtiene los datos del almacén
		$id_producto = trim($_POST['busqueda']);
		$precio = trim($_POST['precio']);
		$costo = trim($_POST['costo']);
		$cantidad = trim($_POST['cantidad']);
		$codigo = (isset($_POST['codigo'])) ? $_POST['codigo'] : '';
		$nombre = (isset($_POST['nombre'])) ? $_POST['nombre'] : '';
		$categoria_id = (isset($_POST['categoria_id'])) ? $_POST['categoria_id'] : '';
		$unidad_id = (isset($_POST['unidad_id'])) ? $_POST['unidad_id'] : '';

		// Obtiene el almacen principal
		$almacen = $db->from('inv_almacenes')->where('principal', 'S')->fetch_first();
		$id_almacen = ($almacen) ? $almacen['id_almacen'] : 0;

		if ($codigo != '' && $nombre != '' && $categoria_id != '' && $unidad_id != '' && $precio != '') {
			$producto = array(
				'codigo' => $codigo,
				'nombre' => $nombre,
				'nombre_factura' => $nombre,
				'fecha_registro' => date('Y-m-d'),
				'hora_registro' => date('H:i:s'),
				'cantidad_minima' => 20,
				'precio_actual' => $precio,
				'unidad_id' => $unidad_id,
				'categoria_id' => $categoria_id
			);

			$id_producto = $db->insert('inv_productos', $producto);
			
			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"), 
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/registros/guardar',
				'detalle' => 'Se creo producto con identificador numero ' . $id_producto ,
				'usuario_id' => $_SESSION[user]['id_user']			
			);

			$db->insert('sys_procesos', $data) ; 
		}

		var_dump($id_producto);

		$precio_objeto = array(
			'precio' => $precio,
			'fecha_registro' => date('Y-m-d'),
			'hora_registro' => date('H:i:s'),
			'producto_id' => $id_producto,
			'empleado_id' => $_user['persona_id']
		);

		$id_precio = $db->insert('inv_precios', $precio_objeto);
		
		// Guarda Historial
		$data = array(
			'fecha_proceso' => date("Y-m-d"),
			'hora_proceso' => date("H:i:s"), 
			'proceso' => 'c',
			'nivel' => 'l',
			'direccion' => '?/registros/guardar',
			'detalle' => 'Se creo precio con identificador numero ' . $id_precio ,
			'usuario_id' => $_SESSION[user]['id_user']			
		);

		$db->insert('sys_procesos', $data) ; 

		var_dump($producto);

		// Instancia el ingreso
		$ingreso = array(
			'fecha_ingreso' => date('Y-m-d'),
			'hora_ingreso' => date('H:i:s'),
			'tipo' => 'Compra',
			'descripcion' => 'Compra de productos',
			'monto_total' => '0.00',
			'nombre_proveedor' => $_institucion['nombre'],
			'nro_registros' => '1',
			'almacen_id' => $id_almacen,
			'empleado_id' => $_user['persona_id']
		);

		// Guarda la informacion
		$ingreso_id = $db->insert('inv_ingresos', $ingreso);
		
		// Guarda Historial
		$data = array(
			'fecha_proceso' => date("Y-m-d"),
			'hora_proceso' => date("H:i:s"), 
			'proceso' => 'c',
			'nivel' => 'l',
			'direccion' => '?/registros/guardar',
			'detalle' => 'Se creo ingreso con identificador numero ' . $ingreso_id ,
			'usuario_id' => $_SESSION[user]['id_user']			
		);

		$db->insert('sys_procesos', $data) ; 

		var_dump($ingreso);

		// Forma el detalle
		$detalle = array(
			'cantidad' => $cantidad,
			'costo' => $costo,
			'producto_id' => $id_producto,
			'ingreso_id' => $ingreso_id
		);

		// Guarda la informacion
		$id_detalle = $db->insert('inv_ingresos_detalles', $detalle);
		
		// Guarda Historial
		$data = array(
			'fecha_proceso' => date("Y-m-d"),
			'hora_proceso' => date("H:i:s"), 
			'proceso' => 'c',
			'nivel' => 'l',
			'direccion' => '?/registros/guardar',
			'detalle' => 'Se creo ingreso detalle con identificador numero ' . $id_detalle ,
			'usuario_id' => $_SESSION[user]['id_user']			
		);
		$db->insert('sys_procesos', $data) ; 

		var_dump($detalle);
		
		// Redirecciona a la pagina principal
		redirect('?/registros/crear');
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