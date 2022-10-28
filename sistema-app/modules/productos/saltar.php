<?php

/**
 * FunctionPHP - Framework Functional PHP
 * 
 * @package  FunctionPHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */

// Obtiene los parametros
$tipo = (isset($params[0])) ? $params[0] : 0;
$id_producto = (isset($params[1])) ? $params[1] : 0;

// Obtiene el producto
$producto = $db->select('id_producto')->from('inv_productos')->where('id_producto', $id_producto)->fetch_first();

// Verifica si existen el producto
if ($producto) {
	// Verifica si es antes o despues
	if ($tipo == 'antes') {
		$id_producto = $db->query("select ifnull(max(id_producto), (select max(id_producto) from inv_productos)) as id_producto from inv_productos where id_producto < '$id_producto'")->fetch_first();
		$id_producto = $id_producto['id_producto'];
	} else {
		$id_producto = $db->query("select ifnull(min(id_producto), (select min(id_producto) from inv_productos)) as id_producto from inv_productos where id_producto > '$id_producto'")->fetch_first();
		$id_producto = $id_producto['id_producto'];
	}

	// Redirecciona la pagina
	redirect('?/productos/ver/' . $id_producto);
} else {
	// Error 404
	require_once not_found();
	exit;
}

?>