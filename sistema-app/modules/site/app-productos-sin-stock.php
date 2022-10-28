<?php

if(true) {
    if (true) {
        require config . '/database.php';
        $productos = $db->query("SELECT p.id_producto, p.promocion, z.id_asignacion, z.unidad_id, z.unidade, z.cantidad2, p.descripcion, p.precio_sugerido, p.imagen, p.codigo, p.nombre, p.nombre_factura, p.cantidad_minima, p.precio_actual, p.precio_sugerido, IFNULL(e.cantidad_ingresos, 0) AS cantidad_ingresos, IFNULL(s.cantidad_egresos, 0) AS cantidad_egresos, u.unidad, u.sigla, c.categoria
                    FROM inv_productos p
                    LEFT JOIN (SELECT d.producto_id, SUM(d.cantidad) AS cantidad_ingresos
                           FROM inv_ingresos_detalles d
                           LEFT JOIN inv_ingresos i ON i.id_ingreso = d.ingreso_id
                           WHERE i.almacen_id = 1 GROUP BY d.producto_id) AS e ON e.producto_id = p.id_producto
                    LEFT JOIN (SELECT d.producto_id, SUM(IF(e.tipo = 'Preventa' && e.preventa = 'habilitado', d.cantidad, IF(e.tipo='No venta' && e.estadoe = 4, d.cantidad, IF(e.tipo NOT IN ('Preventa', 'No venta'), d.cantidad, 0)))) AS cantidad_egresos
                           FROM inv_egresos_detalles d LEFT JOIN inv_egresos e ON e.id_egreso = d.egreso_id
                           WHERE e.almacen_id = 1 GROUP BY d.producto_id) AS s ON s.producto_id = p.id_producto
                    LEFT JOIN inv_unidades u ON u.id_unidad = p.unidad_id LEFT JOIN inv_categorias c ON c.id_categoria = p.categoria_id
                    LEFT JOIN (SELECT w.producto_id, GROUP_CONCAT(w.id_asignacion SEPARATOR '|') AS id_asignacion, GROUP_CONCAT(w.unidad_id SEPARATOR '|') AS unidad_id, GROUP_CONCAT(w.unidad,':',w.otro_precio SEPARATOR '&') AS unidade, GROUP_CONCAT(w.cantidad_unidad SEPARATOR '*') AS cantidad2
                       FROM (SELECT *
                            FROM inv_asignaciones q
                                  LEFT JOIN inv_unidades u ON q.unidad_id = u.id_unidad
                                         ORDER BY u.unidad DESC) w GROUP BY w.producto_id ) z ON p.id_producto = z.producto_id")->fetch();

        $nro = 0;
        foreach($productos as $ct => $producto){
            if($producto['promocion']=='si')
            {
                $producto['promocion']='EN PROMOCIÓN';
            }
            if(!$producto['promocion']){
                $promocion = '';
                $promo = array();
            }
            if($producto['cantidad_minima'] >= ($producto['cantidad_ingresos']-$producto['cantidad_egresos'])){
                $datos[$nro] = array(
                    'id_producto' => (int)$producto['id_producto'],
                    'descripcion' => $producto['descripcion'],
                    //'imagen' => ($producto['imagen'] == '') ? url1 . imgs . '/image.jpg' : url1. productos . '/' . $producto['imagen'],
                    'imagen' => ($producto['imagen'] == '') ? url1 . imgs2 . '/image.jpg' : url1. productos2 . '/' . $producto['imagen'],
                    'codigo' => $producto['codigo'],
                    'nombre' => $producto['nombre_factura'],
                    'promocion' => $producto['promocion'],
                    'nombre_factura' => $producto['nombre_factura'],
                    'cantidad_minima' => $producto['cantidad_minima'],
                    'stock' => $producto['cantidad_ingresos']-$producto['cantidad_egresos'],
                    'categoria' => $producto['categoria'],
                    'precio_sugerido' => $producto['precio_sugerido']
                );
                $nro = $nro + 1;
            }
        }
        if($productos){
            $respuesta = array(
                'estado' => 's',
                'producto' => $datos
            );
            echo json_encode($respuesta);
        }else{
            echo json_encode(array('estado' => 'no se encuentran productos'));
        }
    } else {
        echo json_encode(array('estado' => 'no llego uno de los datos'));
    }
}else{
    echo json_encode(array('estado' => 'no llego los datos'));
}
?>