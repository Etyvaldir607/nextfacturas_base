<?php

/**
 * SimplePHP - Simple Framework PHP
 * 
 * @package  SimplePHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene el id_producto
$id_producto = (sizeof($params) > 0) ? $params[0] : 0;

// Obtiene el producto
$producto = $db->from('inv_productos')->where('id_producto', $id_producto)->fetch_first();

// Verifica si el producto existe
if ($producto) {
	// Obtiene el nombre de la imagen
	$imagen = $producto['imagen'];

	// Verifica si esta almacenada el avatar en la base de datos
	if ($imagen != '') {
		// Verifica si el avatar esta almacenada en la carpeta de profiles
		if (file_exists(files . '/productos/' . $imagen)) {
			// Elimina el archivo
			unlink(files . '/productos/' . $imagen);
		}
	}

	// Elimina el imagen del producto
	$db->where('id_producto', $id_producto)->update('inv_productos', array('imagen' => ''));
	
	// Guarda Historial
	$data = array(
		'fecha_proceso' => date("Y-m-d"),
		'hora_proceso' => date("H:i:s"), 
		'proceso' => 'u',
		'nivel' => 'l',
		'direccion' => '?/productos/suprimir',
		'detalle' => 'Se actualizo inventario de producto con identificador numero ' . $id_producto,
		'usuario_id' => $_SESSION[user]['id_user']			
	);			
	$db->insert('sys_procesos', $data) ;

	// Define el mensaje de exito
	$_SESSION[temporary] = array(
		'alert' => 'success',
		'title' => 'Eliminación satisfactoria!',
		'message' => 'La imagen del producto fue eliminada correctamente.'
	);

	// Redirecciona a la pagina principal
	redirect('?/productos/ver/' . $id_producto);
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>