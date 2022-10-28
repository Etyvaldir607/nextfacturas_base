<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: application/json; charset=utf-8');
header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
date_default_timezone_set('America/La_Paz');

// Define las cabeceras
header('Content-Type: application/json');

// Verifica la peticion post
if (is_post()) {
    // Verifica la existencia de datos
    if (isset($_POST['id_user']) && isset($_POST['id_egreso']) ) {
        // Importa la configuracion para el manejo de la base de datos
        require config . '/database.php';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );

        try {   
            //Se abre nueva transacci贸n.
            $db->autocommit(false);
            $db->beginTransaction();
    
            // $cliente_id = $_POST['id_cliente'];
            $id_egreso = $_POST['id_egreso'];
            $tipo_pago = $_POST['plan_de_pagos'];
    
            // Obtiene los usuarios que cumplen la condicion
            $usuario = $db->from('sys_users')
                          ->join('sys_empleados','persona_id = id_empleado')
                          ->where('id_user',$_POST['id_user'])
                          ->fetch_first();
            $emp = $usuario['id_empleado'];
    
                                    //buscar si es vendedor o distribuidor
                                    // $productos = $db->query('SELECT DISTINCT b.*,c.id_producto, c.promocion, c.cantidad_mayor, c.precio_mayor, c.codigo, c.nombre_factura as nombre, c.precio_sugerido, c.precio_sugerido as stock, d.categoria, e.unidad, c.unidad_id AS unidad_idp, b.unidad_id AS unidad_ide, e.unidad AS total, a.id_egreso, a.plan_de_pagos
                                    //     FROM gps_asigna_distribucion g
                                    //     LEFT JOIN (SELECT x.* FROM gps_asigna_distribucion y LEFT JOIN gps_rutas x ON y.ruta_id = x.id_ruta WHERE y.distribuidor_id = '. $emp .') f ON g.ruta_id = f.id_ruta
                                    //     LEFT JOIN sys_empleados w ON f.empleado_id = w.id_empleado
                                    //     LEFT JOIN inv_egresos a ON f.id_ruta = a.ruta_id
                                    //     LEFT JOIN inv_egresos_detalles b ON a.id_egreso = b.egreso_id
                                    //     LEFT JOIN inv_productos c ON b.producto_id = c.id_producto
                                    //     LEFT JOIN inv_categorias d ON c.categoria_id = d.id_categoria
                                    //     LEFT JOIN inv_unidades e ON c.unidad_id = e.id_unidad
                                    //     WHERE a.estadoe = 2 AND a.cliente_id = '. $cliente_id .' AND a.id_egreso = '. $id_egreso .' AND g.distribuidor_id = '. $emp .' AND g.estado = 1 AND a.grupo = "" AND b.promocion_id != 1 and (a.fecha_egreso <= w.fecha OR a.fecha_egreso < CURDATE()) ')->fetch();
            
            $productos = $db->query("SELECT b.*, SUM(b.cantidad)as cantidad,
                                            c.id_producto, c.promocion, c.codigo, c.nombre_factura as nombre, 
                                            c.precio_sugerido, c.cantidad_mayor, c.precio_mayor, c.precio_contado, c.precio_actual,
                                            e.unidad, a.id_egreso, a.plan_de_pagos
                                    
                                    FROM inv_asignaciones_clientes g
                                    LEFT JOIN sys_empleados w ON w.id_empleado = g.distribuidor_id
                                    LEFT JOIN inv_egresos a ON g.egreso_id = a.id_egreso
                                    
                                    LEFT JOIN inv_egresos_detalles b ON a.id_egreso = b.egreso_id
                                    
                                    LEFT JOIN inv_productos c ON b.producto_id = c.id_producto
                                    LEFT JOIN inv_unidades e ON c.unidad_id = e.id_unidad
                                    WHERE a.estadoe = 2
                                    AND a.id_egreso = ". $id_egreso ."
                                    AND g.distribuidor_id = ". $emp ."
    
                                    AND g.estado = 1
                                    and (a.fecha_egreso <= w.fecha OR a.fecha_egreso <= CURDATE())
                                    
                                    GROUP BY b.producto_id, b.precio, b.lote, b.vencimiento
                                    
                                    ")->fetch();
    
    // AND a.cliente_id = ". $cliente_id ."
                                    
            $total = number_format(0, 2, '.', '');
            $egresos = array();
            if($productos){
                foreach ($productos as $nro => $producto) {
                    // if($producto['unidad_idp']!=$producto['unidad_ide']){
                    //     $unidad = $db->select('*')
                    //                  ->from('inv_asignaciones a')
                    //                  ->join('inv_unidades b', 'a.unidad_id = b.id_unidad')
                    //                  ->where('producto_id',$producto['id_producto'])
                    //                  ->where('unidad_id',$producto['unidad_ide'])
                    //                  ->fetch_first();
                        
                    //     $productos[$nro]['unidad'] = $unidad['unidad'];
                    //     $productos[$nro]['cantidad_mayor'] = $unidad['cantidad_mayor'];
                    //     $productos[$nro]['precio_mayor'] = $unidad['precio_mayor'];
                    //     $productos[$nro]['precio'] = $producto['precio'];
                    //     $productos[$nro]['stock'] = $producto['cantidad'];                    //$productos[$nro]['cantidad'] = $producto['cantidad'];
                    //     $productos[$nro]['lote'] = $producto['lote'];
                    //     $productos[$nro]['vencimiento'] = $producto['vencimiento'];
                    //     $productos[$nro]['cantidad'] = $producto['cantidad']/$unidad['cantidad_unidad'];
                    //     $productos[$nro]['total'] = number_format(($unidad['otro_precio'] * ($producto['cantidad']/$unidad['cantidad_unidad'])), 2, '.', '');
                    //     $total = $total + $productos[$nro]['total'];
                    //     $productos[$nro]['id_detalle'] = (int)$producto['id_detalle'];
                    //     $productos[$nro]['egreso_id'] = (int)$producto['egreso_id'];
                    // }
                    // else{
                    
                    
                        if($tipo_pago=="Contado" && $productos[$nro]['precio']>0){
                            $productos[$nro]['precio']=$producto['precio_contado'];
                        }
                        if($tipo_pago=="Credito" && $productos[$nro]['precio']>0){
                            $productos[$nro]['precio']=$producto['precio_actual'];
                        }
                    
                    
                        $productos[$nro]['lote'] = $producto['lote'];
                        
                        $venx=explode("-",$producto['vencimiento']);
                        $productos[$nro]['vencimiento']=$venx[2]."/".$venx[1]."/".$venx[0];

                        $productos[$nro]['stock'] = $producto['cantidad'];
                        $productos[$nro]['total'] = number_format(($productos[$nro]['precio'] * $producto['cantidad']), 2, '.', '');
                        $total = $total + $productos[$nro]['total'];
                        $productos[$nro]['id_detalle'] = (int)$producto['id_detalle'];
                        $productos[$nro]['egreso_id'] = (int)$producto['egreso_id'];
                    //}
                    $egresos[$nro] = $productos[$nro]['id_egreso'];
                    
                    if($producto['plan_de_pagos'] == 'si'){
                        $estado_venta = 'si';
                    }else{
                        $estado_venta = 'no';
                    }
                    
                    
                    // unset($productos[$nro]['plan_de_pagos']);
                    // unset($productos[$nro]['unidad_idp']);
                    // unset($productos[$nro]['unidad_ide']);
                    //unset($productos[$nro]['stock']);

                }
                
                $cuotas = $db->query("  SELECT pd.fecha
                                        FROM inv_pagos p
                                        LEFT JOIN inv_pagos_detalles pd ON p.id_pago = pd.pago_id
                                        WHERE movimiento_id = ". $id_egreso ."
                                            AND p.tipo = 'Egreso'
                                        ")->fetch();
        
                if($cuotas){
                    $cuotas_value="si";
                }else{
                    $cuotas_value="no";
                }
        
                $db->commit();
                
                $respuesta = array(
                    'estado' => 's',
                    'total' => number_format($total, 2, '.', ''),
                    'estado_venta' => $estado_venta,
                    'egresos' => $egresos,
                    'cliente' => $productos,
                    'cuotas' => $cuotas_value
                );
                echo json_encode($respuesta);
            }else{
                $db->commit();
                
                // Instancia el objeto
                $respuesta = array(
                    'estado' => 'n', 'msg'=>'no existe productos'
                );
                // Devuelve los resultados
                echo json_encode($respuesta);
            }
        } catch (Exception $e) {
            $status = false;
            $error = $e->getMessage();

            //Se devuelve el error en mensaje json
            echo json_encode(array('estado' => 'n', 'msg'=>$error));

            //se cierra transaccion
            $db->rollback();
        }    
    } else {
        // Devuelve los resultados
        echo json_encode(array('estado' => 'n', 'msg'=>'no llega el id usuario'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'n', 'msg'=>'no llega ningun dato'));
}

?>