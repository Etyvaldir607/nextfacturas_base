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
	if (isset($_POST['id_proforma'])) {
		// Obtiene el id_proforma
		$id_proforma = trim($_POST['id_proforma']);

		// Obtiene la proforma
		$proforma = $db->select('i.*, a.almacen, a.principal, e.nombres, e.paterno, e.materno')
					   ->from('inv_proformas i')
					   ->join('inv_almacenes a', 'i.almacen_id = a.id_almacen', 'left')
					   ->join('sys_empleados e', 'i.empleado_id = e.id_empleado', 'left')
					   ->where('id_proforma', $id_proforma)
					   ->fetch_first();

		// Verifica si existe la proforma
		if ($proforma || $proforma['empleado_id'] != $_user['persona_id']) {
			// Obtiene los detalles
			$detalles = $db->select('d.*, p.codigo, p.nombre, p.nombre_factura')
						   ->from('inv_proformas_detalles d')
						   ->join('inv_productos p', 'd.producto_id = p.id_producto', 'left')
						   ->where('d.proforma_id', $id_proforma)
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
				'nit_ci' => $proforma['nit_ci'],
				'nombre_cliente' => $proforma['nombre_cliente'],
				'monto_total' => $proforma['monto_total'],
				'nombres' => $nombres,
				'cantidades' => $cantidades,
				'precios' => $precios,
				'descuentos' => $descuentos,
				'nro_factura' => $proforma['nro_proforma'],
				'nro_autorizacion' => 0,
				'codigo_control' => 0,
				'fecha_emision' => $proforma['fecha_proforma'],
				'fecha_limite' => 0,
				'leyenda' => 0,
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