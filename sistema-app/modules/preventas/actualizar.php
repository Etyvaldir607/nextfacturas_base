<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Verifica si es una peticion ajax
if (is_ajax()) {
	// Verifica la existencia de los datos enviados
	if (isset($_POST['id_producto'])) {
		// Obtiene los datos del producto
		$id_producto = trim($_POST['id_producto']);

		// Obtiene el almacen principal
		$almacen = $db->from('inv_almacenes')->where('principal', 'S')->fetch_first();
		$id_almacen = ($almacen) ? $almacen['id_almacen'] : 0;

		// Obtiene el producto
		$producto = $db->query("select p.id_producto, p.precio_actual, ifnull(e.cantidad_ingresos, 0) as cantidad_ingresos, ifnull(s.cantidad_egresos, 0) as cantidad_egresos from inv_productos p left join (select d.producto_id, sum(d.cantidad) as cantidad_ingresos from inv_ingresos_detalles d left join inv_ingresos i on i.id_ingreso = d.ingreso_id where i.almacen_id = $id_almacen group by d.producto_id) as e on e.producto_id = p.id_producto left join (select d.producto_id, sum(d.cantidad) as cantidad_egresos from inv_egresos_detalles d left join inv_egresos e on e.id_egreso = d.egreso_id where e.almacen_id = $id_almacen group by d.producto_id) as s on s.producto_id = p.id_producto where p.id_producto = $id_producto")->fetch_first();

		// Instancia el producto
		$producto = array(
			'id_producto' => $producto['id_producto'],
			'precio' => $producto['precio_actual'],
			'stock' => $producto['cantidad_ingresos'] - $producto['cantidad_egresos']
		);
		
		// Envia respuesta
		if ($id_almacen != 0) {
			echo json_encode($producto);
		} else {
			echo json_encode(null);
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