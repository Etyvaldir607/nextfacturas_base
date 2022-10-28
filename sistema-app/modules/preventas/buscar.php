<?php

/**
 * FunctionPHP - Framework Functional PHP
 * @package  FunctionPHP
 * @author   Wilfredo Nina <wilnicho@hotmail.com>
 */
if (is_ajax() && is_post()) {
	if (isset($params)) {
		if (isset($_POST['busqueda'])) {
			$busqueda = trim($_POST['busqueda']);
			$id_almacen=$_POST['id_almacen'];
			$almacen = $db->from('inv_almacenes')->where('id_almacen=',$id_almacen)->fetch_first();
			$id_almacen=($almacen)?$id_almacen:0;
            $productos = $db->query("SELECT p.id_producto,p.asignacion_rol, p.descuento ,p.promocion,
						z.id_asignacion, z.unidad_id, z.unidade, z.cantidad2,z.id_roles,
						p.descripcion,p.imagen,p.codigo,p.nombre_factura as nombre,p.nombre_factura,p.cantidad_minima,p.precio_actual,
						IFNULL(e.cantidad_ingresos, 0) AS cantidad_ingresos, IFNULL(s.cantidad_egresos, 0) AS cantidad_egresos,
						u.unidad, u.sigla, c.categoria
					FROM inv_productos p
					LEFT JOIN (
						SELECT d.producto_id, SUM(d.cantidad) AS cantidad_ingresos
						FROM inv_ingresos_detalles d
						LEFT JOIN inv_ingresos i ON i.id_ingreso = d.ingreso_id
						WHERE i.almacen_id = '{$id_almacen}' GROUP BY d.producto_id
					) AS e ON e.producto_id = p.id_producto
					LEFT JOIN (
						SELECT d.producto_id, SUM(d.cantidad) AS cantidad_egresos
						FROM inv_egresos_detalles d
						LEFT JOIN inv_egresos e ON e.id_egreso = d.egreso_id
						WHERE e.almacen_id = '{$id_almacen}' AND e.preventa != NULL
						GROUP BY d.producto_id
					) AS s ON s.producto_id = p.id_producto
					LEFT JOIN inv_unidades u ON u.id_unidad = p.unidad_id
					LEFT JOIN inv_categorias c ON c.id_categoria = p.categoria_id
					LEFT JOIN (
						SELECT w.producto_id,
							GROUP_CONCAT(w.id_asignacion SEPARATOR '|') AS id_asignacion,
							GROUP_CONCAT(w.unidad_id SEPARATOR '|') AS unidad_id,
							GROUP_CONCAT(w.cantidad_unidad,')',w.unidad,':',w.otro_precio SEPARATOR '&') AS unidade,
							GROUP_CONCAT(w.cantidad_unidad SEPARATOR '*') AS cantidad2,
							GROUP_CONCAT(w.id_roles)AS id_roles
						FROM (
							SELECT q.*,u.*,s.id_roles
							FROM inv_asignaciones q
							LEFT JOIN inv_unidades u ON q.unidad_id = u.id_unidad
							LEFT JOIN (
								SELECT apr.asignacion_id,GROUP_CONCAT(apr.rol_id,'|',apr.asignacion_id)AS id_roles
								FROM inv_asignaciones_por_roles AS apr
								LEFT JOIN sys_roles AS r ON r.id_rol=apr.rol_id
								GROUP BY apr.asignacion_id
							)AS s ON s.asignacion_id=q.id_asignacion
							ORDER BY u.unidad DESC
						) w
						GROUP BY w.producto_id
					) z ON p.id_producto = z.producto_id
					WHERE p.codigo LIKE '%{$busqueda}%' OR p.nombre_factura LIKE '%{$busqueda}%' OR c.categoria LIKE '%{$busqueda}%'
					ORDER BY p.nombre ASC")->fetch();
			echo json_encode($productos);
		} else {
			// Error 401
			require_once bad_request();
			exit;
		}
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