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
	if (isset($_POST['id_producto']) && isset($_POST['precio']) && isset($_POST['contado']) && isset($_POST['mayor']) && isset($_POST['cantidad'])  ) {

		// Obtiene los datos del producto
		$id_producto = trim($_POST['id_producto']);
		$id_unidad = $db->select('*')->from('inv_productos')->where('id_producto',$id_producto)->fetch_first()['unidad_id'];
		$precio = trim($_POST['precio']);
		$nuevo_contado = trim($_POST['contado']);
		$nuevo_mayor = trim($_POST['mayor']);
		$nuevo_cantidad = trim($_POST['cantidad']);

		// Instancia el producto
		$producto = array(
			'precio' => $precio,
			'fecha_registro' => date('Y-m-d'),
			'hora_registro' => date('H:i:s'),
			'producto_id' => $id_producto,
			'unidad_id' => $id_unidad,
			'empleado_id' => $_user['persona_id'],
			'precio_contado' => $nuevo_contado,
			'precio_mayor' => $nuevo_mayor,
		);

		// Guarda la informacion
		$db->insert('inv_precios', $producto);

		// Actualiza la informacion
		$db->where('id_producto', $id_producto)->update('inv_productos', array('precio_actual' => $precio, 'precio_contado' => $nuevo_contado, 'precio_mayor' => $nuevo_mayor, 'cantidad_mayor' => $nuevo_cantidad));

		// Envia respuesta
		echo json_encode($producto);
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