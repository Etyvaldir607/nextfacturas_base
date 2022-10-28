<?php

/**
 * FunctionPHP - Framework Functional PHP
 *
 * @package  FunctionPHP
 * @author   Fabio Choque
 */

// Define las cabeceras
header('Content-Type: application/json');

// Verifica la peticion post
if (is_post()) {
    // Verifica la existencia de datos
    if (isset($_POST['id_user']) && isset($_POST['id_cliente']) && isset($_POST['id_egreso'])) {
        // Importa la configuracion para el manejo de la base de datos
        require config . '/database.php';
        $cliente_id = $_POST['id_cliente'];
        $id_egreso = $_POST['id_egreso'];

        // Obtiene los usuarios que cumplen la condicion
        $usuario = $db->from('sys_users')->join('sys_empleados','persona_id = id_empleado')->where('id_user',$_POST['id_user'])->fetch_first();
        $emp = $usuario['id_empleado'];

        //buscar si es vendedor o distribuidor
        $productos = $db->query('SELECT DISTINCT b.*,c.id_producto, c.promocion, c.cantidad_mayor, c.precio_mayor, c.codigo, c.nombre_factura as nombre, c.precio_sugerido, c.precio_sugerido as stock, d.categoria, e.unidad, c.unidad_id AS unidad_idp, b.unidad_id AS unidad_ide, e.unidad AS total, a.id_egreso, a.plan_de_pagos
            FROM gps_asigna_distribucion g
            LEFT JOIN (SELECT x.* FROM gps_asigna_distribucion y LEFT JOIN gps_rutas x ON y.ruta_id = x.id_ruta WHERE y.distribuidor_id = '. $emp .') f ON g.ruta_id = f.id_ruta
            LEFT JOIN sys_empleados w ON f.empleado_id = w.id_empleado
            LEFT JOIN inv_egresos a ON f.id_ruta = a.ruta_id
            LEFT JOIN inv_egresos_detalles b ON a.id_egreso = b.egreso_id
            LEFT JOIN inv_productos c ON b.producto_id = c.id_producto
            LEFT JOIN inv_categorias d ON c.categoria_id = d.id_categoria
            LEFT JOIN inv_unidades e ON c.unidad_id = e.id_unidad
            WHERE a.estadoe = 2 AND a.cliente_id = '. $cliente_id .' AND a.id_egreso = '. $id_egreso .' AND g.distribuidor_id = '. $emp .' AND g.estado = 1 AND a.grupo = "" AND b.promocion_id != 1 and (a.fecha_egreso <= w.fecha OR a.fecha_egreso < CURDATE()) ')->fetch();

        $total = number_format(0, 2, '.', '');
        $egresos = array();
        if($productos){
            foreach ($productos as $nro => $producto) {
                if($producto['unidad_idp']!=$producto['unidad_ide']){
                    $unidad = $db->select('*')->from('inv_asignaciones a')->join('inv_unidades b', 'a.unidad_id = b.id_unidad')->where('producto_id',$producto['id_producto'])->where('unidad_id',$producto['unidad_ide'])->fetch_first();
                    $productos[$nro]['unidad'] = $unidad['unidad'];
                    $productos[$nro]['cantidad_mayor'] = $unidad['cantidad_mayor'];
                    $productos[$nro]['precio_mayor'] = $unidad['precio_mayor'];
                    $productos[$nro]['precio'] = $producto['precio'];
                    $productos[$nro]['stock'] = $producto['cantidad'];                    //$productos[$nro]['cantidad'] = $producto['cantidad'];
                    $productos[$nro]['lote'] = $producto['lote'];
                    $productos[$nro]['vencimiento'] = $producto['vencimiento'];
                    $productos[$nro]['cantidad'] = $producto['cantidad']/$unidad['cantidad_unidad'];
                    $productos[$nro]['total'] = number_format(($unidad['otro_precio'] * ($producto['cantidad']/$unidad['cantidad_unidad'])), 2, '.', '');
                    $total = $total + $productos[$nro]['total'];
                    $productos[$nro]['id_detalle'] = (int)$producto['id_detalle'];
                    $productos[$nro]['egreso_id'] = (int)$producto['egreso_id'];
                }
                else{
                    $productos[$nro]['lote'] = $producto['lote'];
                    $productos[$nro]['vencimiento'] = $producto['vencimiento'];
                    $productos[$nro]['stock'] = $producto['cantidad'];
                    $productos[$nro]['total'] = number_format(($producto['precio'] * $producto['cantidad']), 2, '.', '');
                    $total = $total + $productos[$nro]['total'];
                    $productos[$nro]['id_detalle'] = (int)$producto['id_detalle'];
                    $productos[$nro]['egreso_id'] = (int)$producto['egreso_id'];
                }
                $egresos[$nro] = $productos[$nro]['id_egreso'];
                
                if($producto['plan_de_pagos'] == 'si'){
                    $estado_venta = 'Credito';
                }else{
                    $estado_venta = 'Contado';
                }
            }
            $respuesta = array(
                'estado' => 'd',
                'total' => number_format($total, 2, '.', ''),
                'estado_venta' => $estado_venta,
                'egresos' => $egresos,
                'cliente' => $productos
            );
            echo json_encode($respuesta);
        }else{
            // Instancia el objeto
            $respuesta = array(
                'estado' => 'no exite productos'
            );
            // Devuelve los resultados
            echo json_encode($respuesta);
        }
    } else {
        // Devuelve los resultados
        echo json_encode(array('estado' => 'no llega el id usuario'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'no llega ningun dato'));
}

?>