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
	if (isset($_POST['id_producto']) && isset($_POST['codigo']) && isset($_POST['codigo_barras']) && isset($_POST['nombre']) && isset($_POST['nombre_factura']) && isset($_POST['cantidad_minima']) && 
	    isset($_POST['precio_actual']) && isset($_POST['unidad_id']) && isset($_POST['categoria_id']) && isset($_POST['ubicacion']) && isset($_POST['descripcion'])) {
		// Obtiene los datos del producto
		$id_producto = trim($_POST['id_producto']);
		$codigo = trim($_POST['codigo']);
		$codigo_barras = trim($_POST['codigo_barras']);
		$nombre = trim($_POST['nombre']);
		$color = trim($_POST['color']);
		// $fecha_ven = trim($_POST['ven_fecha']);
		$nombre_factura = trim($_POST['nombre_factura']);
		$cantidad_minima = trim($_POST['cantidad_minima']);

        $codigo_sanitario = trim($_POST['codigo_sanitario']);

		$precio_actual =    ($_POST['precio_actual']) ? trim($_POST['precio_actual']) : '0.00';
		$precio_sugerido =  ($_POST['precio_sugerido']) ? trim($_POST['precio_sugerido']) : '0.00';

		$unidad_id = trim($_POST['unidad_id']);
		$categoria_id = trim($_POST['categoria_id']);
		$ubicacion = trim($_POST['ubicacion']);
		$descripcion = trim($_POST['descripcion']);
		$descuento=     ($_POST['descuento_contado']) ? trim($_POST['descuento_contado']) : '0.00';
		$precio_contado=($_POST['precio_contado']) ? trim($_POST['precio_contado']) : '0.00';
		$proveedor_id = trim($_POST['proveedor_id']);
		// Josema:: agregando precios
		$cantidad_mayor = ($_POST['cantidad_mayor']) ? trim($_POST['cantidad_mayor']) : '0.00';
		$precio_mayor=    ($_POST['precio_mayor']) ? trim($_POST['precio_mayor']) : '0.00';
		// Josema:: agregando precios

		$roles=trim($_POST['roles']);
		// $contenedor = trim($_POST['contenedor']);
		// $dui = trim($_POST['dui']);
		$roles=explode(',',$roles);
		$Aux='';
		for($i=0;$i<count($roles);++$i):
			$Aux.="rol='{$roles[$i]}' OR ";
		endfor;
		$Aux=rtrim($Aux,' OR ');
		$roles=$db->query("SELECT GROUP_CONCAT(id_rol)AS id_rol FROM sys_roles WHERE {$Aux}")->fetch_first()['id_rol'];
		// Instancia el producto
		$producto = array(
			'codigo' => $codigo,
			'codigo_barras' => 'CB' . $codigo_barras,
			'nombre' => $nombre,
			'nombre_factura' => $nombre_factura,
			
			'precio_actual' => $precio_actual,
			'precio_sugerido' => $precio_sugerido,
			'precio_contado' => $precio_contado,
			'cantidad_mayor' => $cantidad_mayor,
			'precio_mayor' => $precio_mayor,
			
			'codigo_sanitario'=>$codigo_sanitario,
			
			'cantidad_minima' => $cantidad_minima,
			'ubicacion' => $ubicacion,
			'descripcion' => $descripcion,
			'unidad_id' => $unidad_id,
			'categoria_id' => $categoria_id,
			// Josema:: agregando precios
			'proveedor_id' => $proveedor_id,
			// Josema:: agregando precios
// 			'descuento'=>$descuento,
			'asignacion_rol'=>$roles,
			'visible'=>'s',
			'codigo_sin'			=> (int)filter_input(INPUT_POST, 'codigo_sin'),
			'codigo_actividad'		=> (int)filter_input(INPUT_POST, 'codigo_actividad'),
			'unidad_medida_siat'	=> (int)filter_input(INPUT_POST, 'unidad_medida_siat'),
		);

		// Verifica si es creacion o modificacion
		if ($id_producto > 0) {
			// Genera la condicion
			$condicion = array('id_producto' => $id_producto);

			// Actualiza la informacion
			$db->where($condicion)->update('inv_productos', $producto);
			
			// Guarda Historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"),
				'proceso' => 'u',
				'nivel' => 'l',
				'direccion' => '?/productos/guardar',
				'detalle' => 'Se actualizó inventario de producto con identificador número ' . $id_producto,
				'usuario_id' => $_SESSION[user]['id_user']
			);
			$db->insert('sys_procesos', $data);

			// Instancia la variable de notificacion
			$_SESSION[temporary] = array(
				'alert' => 'success',
				'title' => 'Actualización satisfactoria!',
				'message' => 'El registro se actualizó correctamente.'
			);
		} else {
			// adiciona la fecha y hora de creacion
			$producto['fecha_registro'] = date('Y-m-d');
			$producto['hora_registro'] = date('H:i:s');
			$producto['imagen'] = '';

			// Guarda la informacion
			$id_producto = $db->insert('inv_productos', $producto);

			// Guarda en el historial
			$data = array(
				'fecha_proceso' => date("Y-m-d"),
				'hora_proceso' => date("H:i:s"),
				'proceso' => 'c',
				'nivel' => 'l',
				'direccion' => '?/productos/guardar',
				'detalle' => 'Se inserto el producto con identificador numero ' . $id_producto,
				'usuario_id' => $_SESSION[user]['id_user']
			);
			$db->insert('sys_procesos', $data);

			// Instancia la variable de notificacion
			$_SESSION[temporary] = array(
				'alert' => 'success',
				'title' => 'Adición satisfactoria!',
				'message' => 'El registro se guardó correctamente.'
			);
		}

		// Redirecciona a la pagina principal
		redirect('?/productos/ver/' . $id_producto);
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
