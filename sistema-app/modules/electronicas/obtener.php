<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Verifica si es una peticion ajax y post
if (is_ajax() && is_post()) {
	// Verifica la existencia de los datos enviados
	if (isset($_POST['id_venta'])) {
		// Obtiene el id_venta
		$id_venta = trim($_POST['id_venta']);

		// Obtiene la venta
		$venta = $db->select('i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno')
					->from('inv_egresos i')
					->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')
					->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')
					->where('id_egreso', $id_venta)
					->fetch_first();

		// Verifica si existe la venta
		if ($venta && $venta['empleado_id'] == $_user['persona_id']) {
			// Obtiene la dosificacion
			$dosificacion = $db->from('inv_dosificaciones')
							   ->where('id_dosificacion', $venta['dosificacion_id'])
							   ->fetch_first();

			// Obtiene los detalles
			$detalles = $db->select('d.*, p.codigo, p.nombre, p.nombre_factura')
						   ->from('inv_egresos_detalles d')
						   ->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')
						   ->where('d.egreso_id', $id_venta)
						   ->order_by('id_detalle asc')
						   ->fetch();

			// Instancia los detalles
			$nombres = array();
			$cantidades = array();
			$precios = array();
			$descuentos = array();

			// Recorre los detalles
			foreach ($detalles as $nro => $detalle) {
				// Almacena los detalles
				array_push($nombres, str_replace("*", "'", $detalle['nombre_factura']));
				array_push($cantidades, $detalle['cantidad']);
				array_push($precios, $detalle['precio']);
				array_push($descuentos, $detalle['descuento']);
			}

			// Instancia la respuesta
			$respuesta = array(
				'nit_ci' => $venta['nit_ci'],
				'nombre_cliente' => $venta['nombre_cliente'],
				'monto_total' => $venta['monto_total'],
				'nombres' => $nombres,
				'cantidades' => $cantidades,
				'precios' => $precios,
				'descuentos' => $descuentos,
				'nro_factura' => $venta['nro_factura'],
				'nro_autorizacion' => $venta['nro_autorizacion'],
				'codigo_control' => $venta['codigo_control'],
				'fecha_emision' => $venta['fecha_egreso'],
				'fecha_limite' => $venta['fecha_limite'],
				'leyenda' => $dosificacion['leyenda'],
				'nit' => $_institution['nit'],
				'sucursal' => name_project,
				'impresora' => $_terminal['impresora']
			);

			// Envia respuesta
			echo json_encode($respuesta);
		} else {
			// Envia respuesta
			echo 'error';
		}		
	} else {
		// Envia respuesta
		echo 'error';
	}
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>