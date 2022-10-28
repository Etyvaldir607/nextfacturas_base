<?php
// Define las cabeceras
header('Content-Type: application/json');

// Verifica la peticion post
if (is_post()) {
    // Verifica la existencia de datos
    if (isset($_POST['id_user'])) {
        // Importa la configuracion para el manejo de la base de datos
        require config . '/database.php';

        // Obtiene los datos
        $id_usuario = trim($_POST['id_user']);

        // Obtiene los usuarios que cumplen la condicion
        $usuario = $db->query(" select id_user, id_empleado 
                                from sys_users 
                                LEFT JOIN sys_empleados ON persona_id = id_empleado 
                                where  id_user = '$id_usuario' and active = '1' 
                                limit 1
                            ")->fetch_first();

        // Verifica la existencia del usuario
        if ($usuario) {
            $totalentrega =  $db->select('ifnull(SUM(monto_total),0) as total, COUNT(cliente_id) as cont')
                                ->from('tmp_egresos')
                                ->where('distribuidor_id',$usuario['id_empleado'])
                                ->where('distribuidor_estado','ENTREGA')
                                ->where('estado',3)
                                ->fetch_first();
                                
            $totaldevuelto = $db->query("select ifnull(SUM(monto_total),0) as total
                                         from inv_egresos
                                         where  preventa='devolucion'
                                                AND
                                                nro_nota IN (select nro_nota
                                                             from tmp_egresos
                                                             where   distribuidor_id='".$usuario['id_empleado']."' AND
                                                                     estado=3) 
                                        ")
                                ->fetch_first();

            $totaldescuento = $db->select('ifnull( SUM(descripcion_venta),0) as descuento')
                                 ->from('tmp_egresos')
                                 ->where('distribuidor_id',$usuario['id_empleado'])
                                 ->where('distribuidor_estado','ENTREGA')
                                 ->where('estado',3)
                                 ->fetch_first();

            $fecha_hora_salida = $db->query("SELECT MAX(fecha_hora_salida)as fecha_hora_salida
                                             from inv_asignaciones_clientes
                                             where distribuidor_id='".$usuario['id_empleado']."'
                                            ")
                                    ->fetch_first();

            if(!$fecha_hora_salida){
                $fecha_hora_salida['fecha_hora_salida']="2099-12-31";    
            }
            
            $egresos =$db->query("select e.*, cl.cliente
                                  from inv_egresos e
                                  left join inv_clientes cl ON e.cliente_id = cl.id_cliente
                                  where e.id_egreso IN(
                                            SELECT egreso_id
                                            FROM inv_asignaciones_clientes
                                            WHERE fecha_hora_salida='".$fecha_hora_salida['fecha_hora_salida']."' and estado='A' and estado_pedido='reasignado'
                                        )
                                ")
                          ->fetch();

            foreach($egresos as $nro5 => $egreso){
                $detalles =  $db->select('b.nombre_factura as unidad, SUM(a.cantidad) cantidad, a.precio as nombre_factura')
                                ->from('inv_egresos_detalles a')
                                ->join('inv_productos b','a.producto_id = b.id_producto')
                                ->join('inv_unidades c','a.unidad_id = c.id_unidad')
                                ->where('a.egreso_id',$egreso['id_egreso'])
                                ->group_by('producto_id, precio, lote, vencimiento')
                                ->fetch();
                                
                $egresos[$nro5]['detalles'] = $detalles;
                $egresos[$nro5]['cobro'] = 111;
                $egresos[$nro5]['deuda_anterior'] = 222;
                
                $egresos[$nro5]['distribuidor_fecha']=$egresos[$nro5]['motivo_id'];
                $egresos[$nro5]['distribuidor_hora']="";

                $egresos[$nro5]['nombre_cliente']=$egresos[$nro5]['cliente'];
            }

            // Instancia el objeto
            if(count($egresos)>0){
                $respuesta = array(
                    'estado' => 's',
                    'nro_clientes' => $totalentrega['cont'],
                    'total_entregas' => $totalentrega['total'],
                    'total_devueltos' => $totaldevuelto['total'],
                    'total_descuentos' => $totaldescuento['descuento'],
                    'total_cobros' => 111,
                    'total_cobros_anteriores' => 222,
                    'cliente' => $egresos
                );
            }else{
                $respuesta = array(
                    'estado' => 'n',
                    'nro_clientes' => 0,
                    'total_entregas' => $totalentrega['total'],
                    'total_devueltos' => $totaldevuelto['total'],
                    'total_descuentos' => $totaldescuento['descuento'],
                    'total_cobros' => 111,
                    'total_cobros_anteriores' => 222,
                    'cliente' => 0
                );
            }
            
            // Devuelve los resultados
            echo json_encode($respuesta);
        } else {
            // Devuelve los resultados
            echo json_encode(array('estado' => 'n'));
        }
    } else {
        // Devuelve los resultados
        echo json_encode(array('estado' => 'n'));
    }
} else {
    // Devuelve los resultados
    echo json_encode(array('estado' => 'n'));
}

?>